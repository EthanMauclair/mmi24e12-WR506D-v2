<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\AccesArticle;
use App\Service\PhotoArticles;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/articles/{id}/photo : mêmes règles que la lecture de l'article (404 sinon).
 * Les photos ne sont jamais servies depuis un dossier public (D-015).
 */
final class PhotoArticleController
{
    #[Route('/api/articles/{id}/photo', name: 'article_photo', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, AccesArticle $acces, PhotoArticles $photos): Response
    {
        $article = $acces->article($id);

        return new Response($photos->lire($article->getPhoto()), Response::HTTP_OK, [
            'Content-Type' => 'image/jpeg',
            // Réponse propre à celui qui la demande : pas de cache partagé (proxy, CDN).
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
