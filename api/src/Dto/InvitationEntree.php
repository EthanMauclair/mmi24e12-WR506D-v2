<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Corps de POST /api/logements/{id}/membres. */
final class InvitationEntree
{
    #[Assert\NotBlank]
    public string $pseudo = '';
}
