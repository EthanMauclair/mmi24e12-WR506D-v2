<?php

declare(strict_types=1);

namespace App\Enum;

enum RoleMembre: string
{
    case Proprietaire = 'PROPRIETAIRE';
    case Coproprietaire = 'COPROPRIETAIRE';
}
