<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Corps de POST /api/articles (étape 2 du parcours P2). Pas de champ « auteur » :
 * l'auteur est toujours l'utilisateur connecté, et un champ non prévu est refusé (A-15).
 */
final class ArticleEntree
{
    /** Chemin renvoyé par POST /api/reconnaissances. */
    #[Assert\NotBlank]
    public string $photo = '';

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $stockage = null;

    #[Assert\NotNull]
    #[Assert\Positive]
    public ?int $categorie = null;

    #[Assert\Length(max: 80)]
    public ?string $marque = null;

    #[Assert\Length(max: 40)]
    public ?string $couleur = null;

    #[Assert\Length(max: 2000)]
    public ?string $note = null;

    public bool $estPersonnel = false;
}
