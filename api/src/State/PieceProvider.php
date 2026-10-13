<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\PieceRessource;
use App\Security\AccesLogement;

/**
 * Une pièce, visible des seuls membres de son logement (404 sinon).
 *
 * @implements ProviderInterface<PieceRessource>
 */
final class PieceProvider implements ProviderInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return PieceRessource::depuis($this->acces->piece($uriVariables['id']));
    }
}
