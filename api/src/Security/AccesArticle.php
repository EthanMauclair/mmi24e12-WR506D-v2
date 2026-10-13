<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Article;
use App\Entity\Stockage;
use App\Repository\ArticleRepository;
use App\Repository\StockageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Charge un article en appliquant la règle D-014 : 404 si on ne le voit pas, 403 si on le voit
 * sans pouvoir le modifier.
 */
final class AccesArticle
{
    public function __construct(
        private readonly Security $securite,
        private readonly ArticleRepository $articles,
        private readonly StockageRepository $stockages,
    ) {
    }

    /** @param ArticleVoter::* $droit */
    public function article(mixed $id, string $droit = ArticleVoter::VOIR): Article
    {
        $article = is_numeric($id) ? $this->articles->find((int) $id) : null;
        if (null === $article || !$this->securite->isGranted(ArticleVoter::VOIR, $article)) {
            throw new NotFoundHttpException('Ressource introuvable.');
        }
        if (!$this->securite->isGranted($droit, $article)) {
            throw new AccessDeniedHttpException('Action non autorisée sur cet article.');
        }

        return $article;
    }

    /**
     * Le rangement où l'on crée ou déplace un article : il faut être membre de son logement (A-11).
     * Test S-13 : 403, que le rangement existe ou non, pour ne rien révéler.
     */
    public function rangement(int $id): Stockage
    {
        $stockage = $this->stockages->find($id);
        if (null === $stockage || !$this->securite->isGranted(LogementVoter::AJOUTER, $stockage->getLogement())) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas ranger d\'article dans ce stockage.');
        }

        return $stockage;
    }
}
