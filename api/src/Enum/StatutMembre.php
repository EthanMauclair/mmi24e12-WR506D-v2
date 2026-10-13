<?php

declare(strict_types=1);

namespace App\Enum;

enum StatutMembre: string
{
    /** Invité, n'a pas encore accepté (H13) : n'a aucun droit sur le logement. */
    case Invite = 'INVITE';
    case Accepte = 'ACCEPTE';
}
