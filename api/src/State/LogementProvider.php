<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\LogementRessource;
use App\Repository\MembreRepository;
use App\Security\AccesLogement;

/**
 * GET /logements : les logements dont je suis membre.
 * GET /logements/{id} : le détail, réservé aux membres (404 sinon).
 *
 * @implements ProviderInterface<LogementRessource>
 */
final class LogementProvider implements ProviderInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly MembreRepository $membres,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        $moi = $this->acces->utilisateur();

        if ($operation instanceof CollectionOperationInterface) {
            return array_map(
                fn ($membre) => LogementRessource::depuis($membre->getLogement(), $moi),
                $this->membres->adhesionsAcceptees($moi),
            );
        }

        return LogementRessource::depuis($this->acces->logement($uriVariables['id']), $moi, avecPieces: true);
    }
}
