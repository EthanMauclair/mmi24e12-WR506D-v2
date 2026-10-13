<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\ArticleRessource;
use App\Entity\Utilisateur;
use App\Repository\ArticleRepository;
use App\Security\AccesArticle;
use App\Security\AccesLogement;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * GET /articles : recherche dans les logements dont je suis membre (F-26), personnels compris (A-01).
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
    ) {
    }

    /** @return ArticleRessource|list<ArticleRessource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->rechercher($context['filters'] ?? []);
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
        $texte = static fn (string $cle): ?string => isset($filtres[$cle]) && \is_string($filtres[$cle]) && '' !== trim($filtres[$cle]) ? trim($filtres[$cle]) : null;
        $entier = static fn (string $cle): ?int => isset($filtres[$cle]) && is_numeric($filtres[$cle]) ? (int) $filtres[$cle] : null;

        $trouves = $this->articles->rechercher(
            $this->acces->utilisateur(),
            texte: $texte('q'),
            logement: $entier('logement'),
            categorie: $entier('categorie'),
            couleur: $texte('couleur'),
            marque: $texte('marque'),
            page: max(1, $entier('page') ?? 1),
        );

        return array_map(static fn ($article) => ArticleRessource::depuis($article, vueMembre: true), $trouves);
    }
}
