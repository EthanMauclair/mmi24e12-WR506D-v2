<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Corps commun à la création et au renommage d'un logement, d'une pièce ou d'un rangement. */
final class NomEntree
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    public string $nom = '';
}
