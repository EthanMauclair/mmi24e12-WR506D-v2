<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** Corps de POST /api/auth/inscription (schéma InscriptionEntree du contrat). */
final class InscriptionEntree
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 30)]
        #[Assert\Regex(pattern: '/^[a-z0-9_.]+$/', message: 'Lettres minuscules, chiffres, « _ » et « . » uniquement.')]
        public readonly string $pseudo = '',

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 12, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
        public readonly string $motDePasse = '',

        #[Assert\NotBlank]
        #[Assert\Date(message: 'Date attendue au format AAAA-MM-JJ.')]
        public readonly string $dateNaissance = '',

        public readonly bool $estPrive = false,
    ) {
    }
}
