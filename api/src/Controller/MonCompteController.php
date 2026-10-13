<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\MonCompteModification;
use App\Entity\Utilisateur;
use App\Exception\RegleMetierException;
use App\Repository\MembreRepository;
use App\Repository\UtilisateurRepository;
use App\Vue\MonCompteVue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * GET, PATCH, DELETE /api/moi. Réservé à l'utilisateur connecté (security.yaml, access_control).
 * On n'agit jamais sur un autre compte que celui du jeton : aucun identifiant n'est lu dans l'URL.
 */
#[Route('/api/moi', name: 'moi_')]
final class MonCompteController
{
    public function __construct(
        private readonly ClockInterface $horloge,
    ) {
    }

    #[Route('', name: 'lire', methods: ['GET'])]
    public function lire(#[CurrentUser] Utilisateur $moi): JsonResponse
    {
        return new JsonResponse(MonCompteVue::depuis($moi, $this->horloge->now()));
    }

    /** F-02 : public / privé ; un compte mineur reste privé (I-21). */
    #[Route('', name: 'modifier', methods: ['PATCH'])]
    public function modifier(
        #[CurrentUser] Utilisateur $moi,
        #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])] MonCompteModification $modification,
        UtilisateurRepository $utilisateurs,
        UserPasswordHasherInterface $hacheur,
        EntityManagerInterface $em,
    ): JsonResponse {
        $aujourdhui = $this->horloge->now();

        if (null !== $modification->email && mb_strtolower($modification->email) !== $moi->getEmail()) {
            if (null !== $utilisateurs->findOneBy(['email' => mb_strtolower($modification->email)])) {
                return new JsonResponse(
                    ['title' => 'Requête refusée', 'status' => 409, 'detail' => 'Cet e-mail est déjà utilisé.'],
                    Response::HTTP_CONFLICT,
                    ['Content-Type' => 'application/problem+json'],
                );
            }
            $moi->changerEmail($modification->email);
        }
        if (null !== $modification->motDePasse) {
            $moi->definirMotDePasseHache($hacheur->hashPassword($moi, $modification->motDePasse));
        }
        if (null !== $modification->estPrive) {
            $moi->definirPrive($modification->estPrive, $aujourdhui);
        }

        $em->flush();

        return new JsonResponse(MonCompteVue::depuis($moi, $aujourdhui));
    }

    /** F-03 : refusé tant que je possède un logement (I-04). Mes comptes mineurs sont supprimés avec moi (I-03). */
    #[Route('', name: 'supprimer', methods: ['DELETE'])]
    public function supprimer(
        #[CurrentUser] Utilisateur $moi,
        MembreRepository $membres,
        EntityManagerInterface $em,
    ): Response {
        if ($membres->estProprietaireDUnLogement($moi)) {
            throw new RegleMetierException('I-04', 'Supprimez ou léguez d\'abord vos logements.');
        }

        $em->remove($moi);
        $em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
