<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Corps de POST /api/moi/amis (F-17). */
final class AmiEntree
{
    #[Assert\NotBlank]
    public string $pseudo = '';
}
