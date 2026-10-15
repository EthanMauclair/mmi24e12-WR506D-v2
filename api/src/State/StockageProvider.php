<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\StockageRessource;
use App\Security\AccesLogement;

/**
 * Un rangement, visible des seuls membres de son logement (404 sinon).
 *
 * @implements ProviderInterface<StockageRessource>
 */
final class StockageProvider implements ProviderInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): StockageRessource
    {
        return StockageRessource::depuis($this->acces->stockage($uriVariables['id']));
    }
}
