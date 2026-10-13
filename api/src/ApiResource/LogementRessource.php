<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\NomEntree;
use App\Entity\Logement;
use App\Entity\Utilisateur;
use App\State\LogementProcessor;
use App\State\LogementProvider;

/**
 * Un logement tel que le voit un de ses membres (schémas Logement et LogementDetail du contrat).
 * Seuls les membres y ont accès : un non-membre reçoit 404 (A-01, D-014).
 */
#[ApiResource(
    shortName: 'Logement',
    operations: [
        new GetCollection(uriTemplate: '/logements', provider: LogementProvider::class),
        new Post(uriTemplate: '/logements', input: NomEntree::class, processor: LogementProcessor::class),
        new Get(uriTemplate: '/logements/{id}', provider: LogementProvider::class),
        new Patch(uriTemplate: '/logements/{id}', input: NomEntree::class, provider: LogementProvider::class, processor: LogementProcessor::class),
        new Delete(uriTemplate: '/logements/{id}', provider: LogementProvider::class, processor: LogementProcessor::class),
    ],
)]
final class LogementRessource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $nom = '';

    /** Mon rôle dans ce logement : PROPRIETAIRE ou COPROPRIETAIRE. */
    public string $monRole = '';

    /**
     * Pièces et rangements : uniquement dans la vue détaillée (GET /logements/{id}).
     * Tableaux simples, pour qu'API Platform les inclue au lieu de renvoyer des liens.
     *
     * @var list<array{id: int|null, nom: string, stockages: list<array{id: int|null, nom: string}>}>|null
     */
    public ?array $pieces = null;

    public static function depuis(Logement $logement, Utilisateur $moi, bool $avecPieces = false): self
    {
        $ressource = new self();
        $ressource->id = $logement->getId();
        $ressource->nom = $logement->getNom();
        $ressource->monRole = $logement->membreDe($moi)?->getRole()->value ?? '';
        if ($avecPieces) {
            $ressource->pieces = array_values(array_map(PieceRessource::enTableau(...), $logement->getPieces()->toArray()));
        }

        return $ressource;
    }
}
