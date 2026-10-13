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
use App\Entity\Piece;
use App\State\PieceProcessor;
use App\State\PieceProvider;

/**
 * Une pièce et ses rangements. Visible des seuls membres du logement (A-01, A-02).
 * Ajout : tout membre (A-11). Renommage et suppression : membres majeurs (A-10).
 */
#[ApiResource(
    shortName: 'Piece',
    operations: [
        new Get(uriTemplate: '/pieces/{id}', provider: PieceProvider::class),
        new Post(uriTemplate: '/logements/{id}/pieces', uriVariables: ['id'], input: NomEntree::class, read: false, processor: PieceProcessor::class),
        new Patch(uriTemplate: '/pieces/{id}', input: NomEntree::class, provider: PieceProvider::class, processor: PieceProcessor::class),
        new Delete(uriTemplate: '/pieces/{id}', provider: PieceProvider::class, processor: PieceProcessor::class),
    ],
)]
final class PieceRessource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $nom = '';

    /** @var list<array{id: int|null, nom: string}> */
    public array $stockages = [];

    public static function depuis(Piece $piece): self
    {
        $ressource = new self();
        $ressource->id = $piece->getId();
        $ressource->nom = $piece->getNom();
        $ressource->stockages = self::stockagesEnTableau($piece);

        return $ressource;
    }

    /** @return array{id: int|null, nom: string, stockages: list<array{id: int|null, nom: string}>} */
    public static function enTableau(Piece $piece): array
    {
        return ['id' => $piece->getId(), 'nom' => $piece->getNom(), 'stockages' => self::stockagesEnTableau($piece)];
    }

    /** @return list<array{id: int|null, nom: string}> */
    private static function stockagesEnTableau(Piece $piece): array
    {
        return array_values(array_map(
            static fn ($stockage) => ['id' => $stockage->getId(), 'nom' => $stockage->getNom()],
            $piece->getStockages()->toArray(),
        ));
    }
}
