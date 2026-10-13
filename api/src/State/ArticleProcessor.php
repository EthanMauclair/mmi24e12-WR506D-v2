<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ArticleRessource;
use App\Dto\ArticleEntree;
use App\Dto\ArticleModification;
use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\Utilisateur;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Security\AccesArticle;
use App\Security\AccesLogement;
use App\Security\ArticleVoter;
use App\Service\PhotoArticles;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * POST /articles : tout membre du logement du rangement choisi (A-11, S-13) ; l'auteur est l'utilisateur connecté (A-15).
 * PATCH, DELETE /articles/{id} : membres majeurs, ou l'auteur s'il est mineur (A-10, A-11, S-02, S-06).
 *
 * @implements ProcessorInterface<ArticleEntree|ArticleModification|ArticleRessource, ArticleRessource|null>
 */
final class ArticleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly AccesArticle $accesArticle,
        private readonly CategorieRepository $categories,
        private readonly ArticleRepository $articles,
        private readonly PhotoArticles $photos,
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $horloge,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?ArticleRessource
    {
        $moi = $this->acces->utilisateur();

        if ($operation instanceof Post) {
            \assert($data instanceof ArticleEntree);

            return ArticleRessource::depuis($this->creer($data, $moi), vueMembre: true);
        }

        $article = $this->accesArticle->article($uriVariables['id'], ArticleVoter::MODIFIER);

        if ($operation instanceof DeleteOperationInterface) {
            $photo = $article->getPhoto();
            $article->getStockage()->liberer($article);
            $this->em->remove($article);
            $this->em->flush();
            $this->photos->supprimer($photo);

            return null;
        }

        \assert($data instanceof ArticleModification);
        $this->modifier($article, $data);
        $this->em->flush();

        return ArticleRessource::depuis($article, vueMembre: true);
    }

    private function creer(ArticleEntree $entree, Utilisateur $moi): Article
    {
        $stockage = $this->accesArticle->rangement((int) $entree->stockage);
        $categorie = $this->categorie((int) $entree->categorie);
        if (!$this->photos->appartientA($entree->photo, $moi) || $this->articles->photoDejaUtilisee($entree->photo)) {
            throw new UnprocessableEntityHttpException('photo : envoyez d\'abord la photo avec POST /api/reconnaissances.');
        }

        $article = Article::creer($moi, $stockage, $categorie, $entree->photo, $this->horloge->now());
        $article->decrire($categorie, $entree->marque, $entree->couleur, $entree->note);
        $article->marquerPersonnel($entree->estPersonnel);
        $this->em->persist($article);
        $this->em->flush();

        return $article;
    }

    private function modifier(Article $article, ArticleModification $modification): void
    {
        if ($modification->contient('stockage') && null !== $modification->stockage) {
            $article->deplacerVers($this->accesArticle->rangement($modification->stockage));
        }

        $article->decrire(
            $modification->contient('categorie') && null !== $modification->categorie
                ? $this->categorie($modification->categorie)
                : $article->getCategorie(),
            $modification->contient('marque') ? $modification->marque : $article->getMarque(),
            $modification->contient('couleur') ? $modification->couleur : $article->getCouleur(),
            $modification->contient('note') ? $modification->note : $article->getNote(),
        );

        if ($modification->contient('estPersonnel') && null !== $modification->estPersonnel) {
            $article->marquerPersonnel($modification->estPersonnel);
        }
    }

    private function categorie(int $id): Categorie
    {
        return $this->categories->find($id)
            ?? throw new UnprocessableEntityHttpException('categorie : catégorie inconnue (voir GET /api/categories).');
    }
}
