<?php

declare(strict_types=1);

namespace App\Enum;

/** Une seule table, deux niveaux (Q-08) : rétrograder un ami revient à changer de niveau. */
enum NiveauRelation: string
{
    case Abonne = 'ABONNE';
    /** Mêmes droits de lecture qu'un abonné accepté (H9), et peut commenter (I-19). */
    case Ami = 'AMI';
}
