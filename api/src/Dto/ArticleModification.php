<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Corps de PATCH /api/articles/{id}. Ni « auteur » ni « photo » (A-15, test S-03).
 *
 * Les propriétés n'ont pas de valeur par défaut : seules celles présentes dans la requête sont
 * initialisées, ce qui distingue « non envoyé » de « vidé » (ex. "marque": null).
 */
final class ArticleModification
{
    #[Assert\Positive]
    public ?int $stockage;

    #[Assert\Positive]
    public ?int $categorie;

    #[Assert\Length(max: 80)]
    public ?string $marque;

    #[Assert\Length(max: 40)]
    public ?string $couleur;

    #[Assert\Length(max: 2000)]
    public ?string $note;

    public ?bool $estPersonnel;

    public function contient(string $champ): bool
    {
        return (new \ReflectionProperty($this, $champ))->isInitialized($this);
    }
}
