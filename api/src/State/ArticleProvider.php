<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ArticleRessource;
use App\Entity\Utilisateur;
use App\Repository\ArticleRepository;
use App\Repository\UtilisateurRepository;
use App\Security\AccesArticle;
use App\Security\AccesLogement;
use App\Security\AccesProfil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GET /articles : recherche dans les logements dont je suis membre (F-26), personnels compris (A-01).
 * GET /utilisateurs/{pseudo}/articles : les articles visibles d'une personne (F-21, F-27, A-03).
 * GET /articles/{id} : vue membre ou vue publique selon qui demande ; 404 si invisible (S-01).
 *
 * @implements ProviderInterface<ArticleRessource>
 */
final class ArticleProvider implements ProviderInterface
{
    public function __construct(
        private readonly AccesArticle $accesArticle,
        private readonly AccesLogement $acces,
        private readonly ArticleRepository $articles,
        private readonly Security $securite,
        private readonly UtilisateurRepository $utilisateurs,
        private readonly AccesProfil $accesProfil,
    ) {
    }

    /** @return ArticleRessource|list<ArticleRessource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return isset($uriVariables['pseudo'])
                ? $this->articlesDe((string) $uriVariables['pseudo'], $context['filters'] ?? [])
                : $this->rechercher($context['filters'] ?? []);
        }

        $article = $this->accesArticle->article($uriVariables['id']);
        $moi = $this->securite->getUser();
        $vueMembre = $moi instanceof Utilisateur && $article->getLogement()->estMembreAccepte($moi);

        return ArticleRessource::depuis($article, $vueMembre);
    }

    /**
     * @param array<string, mixed> $filtres
     *
     * @return list<ArticleRessource>
     */
    private function rechercher(array $filtres): array
    {
        $trouves = $this->articles->rechercher(
            $this->acces->utilisateur(),
            texte: self::texte($filtres, 'q'),
            logement: self::entier($filtres, 'logement'),
            categorie: self::entier($filtres, 'categorie'),
            couleur: self::texte($filtres, 'couleur'),
            marque: self::texte($filtres, 'marque'),
            page: max(1, self::entier($filtres, 'page') ?? 1),
            piece: self::entier($filtres, 'piece'),
            stockage: self::entier($filtres, 'stockage'),
        );

        return array_map(static fn ($article) => ArticleRessource::depuis($article, vueMembre: true), $trouves);
    }

    /**
     * Uniquement les articles dont cette personne est l'auteur (D-013), jamais les personnels (A-04),
     * toujours sous la forme publique (contrat : ArticlePublic). Compte privé : liste vide (S-04),
     * sauf pour la personne elle-même et pour ses abonnés et amis acceptés (A-03).
     *
     * @param array<string, mixed> $filtres
     *
     * @return list<ArticleRessource>
     */
    private function articlesDe(string $pseudo, array $filtres): array
    {
        $auteur = $this->utilisateurs->findOneBy(['pseudo' => $pseudo])
            ?? throw new NotFoundHttpException('Ressource introuvable.');
        if (!$this->accesProfil->voitLesArticlesDe($auteur)) {
            return [];
        }

        $trouves = $this->articles->visiblesDe(
            $auteur,
            logement: self::entier($filtres, 'logement'),
            categorie: self::entier($filtres, 'categorie'),
            page: max(1, self::entier($filtres, 'page') ?? 1),
        );

        return array_map(static fn ($article) => ArticleRessource::depuis($article, vueMembre: false), $trouves);
    }

    /** @param array<string, mixed> $filtres */
    private static function texte(array $filtres, string $cle): ?string
    {
        $valeur = $filtres[$cle] ?? null;

        return \is_string($valeur) && '' !== trim($valeur) ? trim($valeur) : null;
    }

    /** @param array<string, mixed> $filtres */
    private static function entier(array $filtres, string $cle): ?int
    {
        $valeur = $filtres[$cle] ?? null;

        return is_numeric($valeur) ? (int) $valeur : null;
    }
}
