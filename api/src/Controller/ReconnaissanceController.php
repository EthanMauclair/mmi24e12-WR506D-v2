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
 * Ajout par photo (F-07, parcours P2), en deux appels pour que personne n'attende le modèle (D-041) :
 * - POST /api/reconnaissances : la photo est enregistrée et son identifiant renvoyé tout de suite,
 *   à reprendre dans POST /api/articles ; la fiche s'ouvre aussitôt ;
 * - POST /api/reconnaissances/proposition : un modèle de vision lit cette photo et propose catégorie,
 *   marque et couleur (D-040). Cela prend quelques secondes, pendant lesquelles la fiche reste utilisable.
 *
 * La proposition n'est jamais enregistrée (H6) ; elle est vide si la reconnaissance échoue.
 */
final class ReconnaissanceController
{
    #[Route('/api/reconnaissances', name: 'reconnaissance', methods: ['POST'])]
    public function envoyer(#[CurrentUser] Utilisateur $moi, Request $requete, PhotoArticles $photos): JsonResponse
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

        return new JsonResponse(['photo' => $chemin], Response::HTTP_CREATED);
    }

    /** Seulement pour ses propres photos : celle d'un autre est introuvable (D-014). */
    #[Route('/api/reconnaissances/proposition', name: 'reconnaissance_proposition', methods: ['POST'])]
    public function proposer(#[CurrentUser] Utilisateur $moi, Request $requete, PhotoArticles $photos, ReconnaissanceArticles $reconnaissance): JsonResponse
    {
        $chemin = $requete->toArray()['photo'] ?? null;
        if (!\is_string($chemin) || !$photos->appartientA($chemin, $moi)) {
            return new JsonResponse(
                ['title' => 'Photo introuvable', 'status' => Response::HTTP_NOT_FOUND, 'detail' => 'Envoyez d\'abord la photo avec POST /api/reconnaissances.'],
                Response::HTTP_NOT_FOUND,
                ['Content-Type' => 'application/problem+json'],
            );
        }

        return new JsonResponse(['proposition' => $reconnaissance->proposer($photos->lire($chemin))]);
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
