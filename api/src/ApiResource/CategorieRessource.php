<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Categorie;
use App\State\CategorieProvider;

/**
 * La liste fixe des catégories (D-013), publique : elle sert au formulaire d'ajout
 * d'un article et aux filtres (F-26, F-27). Lecture seule.
 */
#[ApiResource(
    shortName: 'Categorie',
    operations: [
        new GetCollection(uriTemplate: '/categories', provider: CategorieProvider::class),
    ],
)]
final class CategorieRessource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $nom = '';

    public static function depuis(Categorie $categorie): self
    {
        $ressource = new self();
        $ressource->id = $categorie->getId();
        $ressource->nom = $categorie->getNom();

        return $ressource;
    }
}
