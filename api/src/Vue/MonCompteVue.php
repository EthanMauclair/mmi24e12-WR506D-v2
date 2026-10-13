<?php

declare(strict_types=1);

namespace App\Vue;

use App\Entity\Utilisateur;

/**
 * Schéma MonCompte du contrat : la seule vue qui contient l'e-mail et la date de naissance,
 * réservée au titulaire du compte. Les champs sont listés un par un : rien n'est exposé par défaut (S-05).
 */
final class MonCompteVue
{
    /** @return array<string, mixed> */
    public static function depuis(Utilisateur $utilisateur, \DateTimeImmutable $aujourdhui): array
    {
        $referent = $utilisateur->getReferent();

        return [
            'id' => $utilisateur->getId(),
            'pseudo' => $utilisateur->getPseudo(),
            'email' => $utilisateur->getEmail(),
            'dateNaissance' => $utilisateur->getDateNaissance()->format('Y-m-d'),
            'estMineur' => $utilisateur->estMineur($aujourdhui),
            'estPrive' => $utilisateur->estPrive(),
            'referent' => null === $referent ? null : ['pseudo' => $referent->getPseudo()],
        ];
    }
}
