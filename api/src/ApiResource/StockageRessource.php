<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\NomEntree;
use App\Entity\Stockage;
use App\State\StockageProcessor;
use App\State\StockageProvider;

/** Un rangement (armoire, commode…). Mêmes règles qu'une pièce. */
#[ApiResource(
    shortName: 'Stockage',
    operations: [
        new Get(uriTemplate: '/stockages/{id}', provider: StockageProvider::class),
        new Post(uriTemplate: '/pieces/{id}/stockages', uriVariables: ['id'], input: NomEntree::class, read: false, processor: StockageProcessor::class),
        new Patch(uriTemplate: '/stockages/{id}', input: NomEntree::class, provider: StockageProvider::class, processor: StockageProcessor::class),
        new Delete(uriTemplate: '/stockages/{id}', provider: StockageProvider::class, processor: StockageProcessor::class),
    ],
)]
final class StockageRessource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $nom = '';

    public static function depuis(Stockage $stockage): self
    {
        $ressource = new self();
        $ressource->id = $stockage->getId();
        $ressource->nom = $stockage->getNom();

        return $ressource;
    }
}
