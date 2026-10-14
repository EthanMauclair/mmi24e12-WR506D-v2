<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Service\PhotoArticles;
use App\Service\PhotoRefuseeException;
use App\Service\ReconnaissanceArticles;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * POST /api/reconnaissances (F-07, étape 1 du parcours P2) : la photo est enregistrée et son
 * identifiant renvoyé, à reprendre dans POST /api/articles.
 *
 * La proposition (catégorie, marque, couleur) vient d'OpenRouter (D-023). Elle n'est pas
 * enregistrée (H6) et reste vide si la reconnaissance échoue : la saisie se fait alors à la main.
 */
final class ReconnaissanceController
{
    #[Route('/api/reconnaissances', name: 'reconnaissance', methods: ['POST'])]
    public function __invoke(#[CurrentUser] Utilisateur $moi, Request $requete, PhotoArticles $photos, ReconnaissanceArticles $reconnaissance): JsonResponse
    {
        $fichier = $requete->files->get('photo');
        if (!$fichier instanceof UploadedFile) {
            return $this->refus(Response::HTTP_UNPROCESSABLE_ENTITY, 'Le champ « photo » est obligatoire.');
        }
        if (\in_array($fichier->getError(), [\UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE], true)
            || ($fichier->isValid() && $fichier->getSize() > PhotoArticles::TAILLE_MAX)) {
            return $this->refus(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'La photo dépasse 10 Mo.');
        }
        if (!$fichier->isValid()) {
            return $this->refus(Response::HTTP_UNPROCESSABLE_ENTITY, 'La photo n\'a pas pu être reçue.');
        }

        try {
            $chemin = $photos->enregistrer($fichier->getPathname(), $moi);
        } catch (PhotoRefuseeException $exception) {
            return $this->refus(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }

        return new JsonResponse(
            ['photo' => $chemin, 'proposition' => $reconnaissance->proposer($photos->lire($chemin))],
            Response::HTTP_CREATED,
        );
    }

    private function refus(int $statut, string $detail): JsonResponse
    {
        return new JsonResponse(
            ['title' => 'Photo refusée', 'status' => $statut, 'detail' => $detail, 'violations' => [['propertyPath' => 'photo', 'message' => $detail]]],
            $statut,
            ['Content-Type' => 'application/problem+json'],
        );
    }
}
