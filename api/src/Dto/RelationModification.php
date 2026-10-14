<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Corps de PATCH /api/relations/{id} : accepter une demande (F-16), rétrograder un ami ou
 * élever un abonné (F-17, F-18). Seules les propriétés envoyées sont initialisées.
 */
final class RelationModification
{
    #[Assert\Choice(choices: ['ACCEPTEE'], message: 'Seule la valeur « ACCEPTEE » est possible.')]
    public ?string $statut;

    #[Assert\Choice(choices: ['ABONNE', 'AMI'], message: 'Valeurs possibles : « ABONNE » ou « AMI ».')]
    public ?string $niveau;

    /**
     * D-004 : garder (par défaut) ou supprimer les commentaires d'un ami rétrogradé.
     * Accepté dès maintenant pour respecter le contrat ; sans effet tant que les commentaires (lot 5) n'existent pas.
     */
    public ?bool $supprimerCommentaires;

    public function contient(string $champ): bool
    {
        return (new \ReflectionProperty($this, $champ))->isInitialized($this);
    }
}
