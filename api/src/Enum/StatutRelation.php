<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutRelation: string
{
    /** Demande vers un compte privé (I-18) : ne donne aucun droit tant qu'elle n'est pas acceptée. */
    case EnAttente = 'EN_ATTENTE';
    case Acceptee = 'ACCEPTEE';
}
