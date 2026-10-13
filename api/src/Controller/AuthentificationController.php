<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\InscriptionEntree;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Vue\MonCompteVue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * F-01. La connexion elle-même (POST /api/auth/connexion) est gérée par le pare-feu « connexion »
 * (security.yaml) et LexikJWT : aucun code ici.
 */
#[Route('/api/auth')]
final class AuthentificationController
{
    public function __construct(
        private readonly ClockInterface $horloge,
    ) {
    }

    #[Route('/inscription', name: 'auth_inscription', methods: ['POST'])]
    public function inscription(
        #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])] InscriptionEntree $entree,
        UtilisateurRepository $utilisateurs,
        UserPasswordHasherInterface $hacheur,
        EntityManagerInterface $em,
    ): JsonResponse {
        if ($utilisateurs->pseudoOuEmailExiste($entree->pseudo, $entree->email)) {
            return $this->probleme(Response::HTTP_CONFLICT, 'Ce pseudo ou cet e-mail est déjà utilisé.');
        }

        $maintenant = $this->horloge->now();
        $utilisateur = Utilisateur::creerMajeur(
            $entree->pseudo,
            $entree->email,
            new \DateTimeImmutable($entree->dateNaissance),
            $entree->estPrive,
            $maintenant,
        );
        $utilisateur->definirMotDePasseHache($hacheur->hashPassword($utilisateur, $entree->motDePasse));

        $em->persist($utilisateur);
        $em->flush();

        return new JsonResponse(MonCompteVue::depuis($utilisateur, $maintenant), Response::HTTP_CREATED);
    }

    /**
     * Supprime le cookie du jeton. Le jeton lui-même reste valable jusqu'à son expiration (1 h) :
     * limite connue, levée avec les jetons de rafraîchissement.
     */
    #[Route('/deconnexion', name: 'auth_deconnexion', methods: ['POST'])]
    public function deconnexion(): Response
    {
        $reponse = new Response(null, Response::HTTP_NO_CONTENT);
        $reponse->headers->setCookie(Cookie::create('BEARER', null, 1, '/', null, true, true, false, Cookie::SAMESITE_LAX));

        return $reponse;
    }

    private function probleme(int $statut, string $detail): JsonResponse
    {
        return new JsonResponse(['title' => 'Requête refusée', 'status' => $statut, 'detail' => $detail], $statut, ['Content-Type' => 'application/problem+json']);
    }
}
