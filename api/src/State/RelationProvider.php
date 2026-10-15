<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\RelationRessource;
use App\Entity\Relation;
use App\Entity\Utilisateur;
use App\Enum\NiveauRelation;
use App\Repository\RelationRepository;
use App\Repository\UtilisateurRepository;
use App\Security\AccesLogement;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * - GET /moi/demandes-abonnement : les demandes en attente sur mon compte (F-16).
 * - GET /moi/relations?niveau= : mes abonnés et mes amis acceptés (F-16, F-17).
 * - /utilisateurs/{pseudo}/abonnement : ma relation vers cette personne (404 si aucune).
 * - /relations/{id} : visible par le suiveur et le suivi uniquement (404 sinon, D-014).
 *
 * @implements ProviderInterface<RelationRessource>
 */
final class RelationProvider implements ProviderInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly RelationRepository $relations,
        private readonly UtilisateurRepository $utilisateurs,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        $moi = $this->acces->utilisateur();

        return match ($operation->getName()) {
            'mes_demandes_abonnement' => array_map(RelationRessource::depuis(...), $this->relations->demandesVers($moi)),
            'mes_relations' => array_map(
                RelationRessource::depuis(...),
                $this->relations->accepteesVers($moi, self::niveau($context['filters'] ?? [])),
            ),
            'abonnement_supprimer' => RelationRessource::depuis($this->monAbonnement((string) $uriVariables['pseudo'])),
            default => RelationRessource::depuis($this->relationVisible($uriVariables['id'])),
        };
    }

    public function relationVisible(mixed $id): Relation
    {
        $moi = $this->acces->utilisateur();
        $relation = is_numeric($id) ? $this->relations->find((int) $id) : null;
        if (null === $relation || ($relation->getSuiveur() !== $moi && $relation->getSuivi() !== $moi)) {
            throw new NotFoundHttpException('Ressource introuvable.');
        }

        return $relation;
    }

    /** Ma relation vers cette personne, abonné ou ami, acceptée ou en attente. */
    public function monAbonnement(string $pseudo): Relation
    {
        return $this->relations->entre($this->acces->utilisateur(), $this->personne($pseudo))
            ?? throw new NotFoundHttpException('Vous ne suivez pas cette personne.');
    }

    public function personne(string $pseudo): Utilisateur
    {
        return $this->utilisateurs->findOneBy(['pseudo' => $pseudo])
            ?? throw new NotFoundHttpException('Aucun compte ne porte ce pseudo.');
    }

    /** @param array<string, mixed> $filtres */
    private static function niveau(array $filtres): ?NiveauRelation
    {
        $valeur = $filtres['niveau'] ?? null;
        if (null === $valeur || '' === $valeur) {
            return null;
        }

        $niveau = \is_string($valeur) ? NiveauRelation::tryFrom($valeur) : null;

        return $niveau ?? throw new UnprocessableEntityHttpException('Valeurs possibles pour « niveau » : ABONNE ou AMI.');
    }
}
