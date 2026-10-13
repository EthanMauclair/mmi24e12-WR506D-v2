<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\CategorieRessource;
use App\Repository\CategorieRepository;

/**
 * GET /categories : toutes les catégories, par ordre alphabétique.
 *
 * @implements ProviderInterface<CategorieRessource>
 */
final class CategorieProvider implements ProviderInterface
{
    public function __construct(
        private readonly CategorieRepository $categories,
    ) {
    }

    /** @return list<CategorieRessource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(CategorieRessource::depuis(...), $this->categories->parNom());
    }
}
