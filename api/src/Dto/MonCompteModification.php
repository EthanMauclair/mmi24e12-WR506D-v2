<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Corps de PATCH /api/moi. Un champ absent n'est pas modifié. */
final class MonCompteModification
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public readonly ?string $email = null,

        #[Assert\Length(min: 12, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
        public readonly ?string $motDePasse = null,

        public readonly ?bool $estPrive = null,
    ) {
    }
}
