<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ProfilRessource;
use App\Entity\Utilisateur;
use App\Repository\ArticleRepository;
use App\Repository\RelationRepository;
use App\Repository\UtilisateurRepository;
use App\Security\AccesProfil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /utilisateurs/{pseudo} : le profil, avec ses compteurs (F-04).
 * Le nombre d'articles suit la même règle que leur liste (A-03) : absent si on ne peut pas les voir.
 *
 * @implements ProviderInterface<ProfilRessource>
 */
final class ProfilProvider implements ProviderInterface
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurs,
        private readonly RelationRepository $relations,
        private readonly ArticleRepository $articles,
        private readonly AccesProfil $acces,
        private readonly Security $securite,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProfilRessource
    {
        $personne = $this->utilisateurs->findOneBy(['pseudo' => (string) $uriVariables['pseudo']])
            ?? throw new NotFoundHttpException('Aucun compte ne porte ce pseudo.');
        $moi = $this->securite->getUser();

        return ProfilRessource::depuis(
            $personne,
            $this->relations->nombreAbonnes($personne),
            $this->acces->voitLesArticlesDe($personne) ? $this->articles->nombreVisiblesDe($personne) : null,
            $moi instanceof Utilisateur && $moi !== $personne ? $this->relations->entre($moi, $personne) : null,
        );
    }
}
