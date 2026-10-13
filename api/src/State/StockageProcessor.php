<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\StockageRessource;
use App\Dto\NomEntree;
use App\Security\AccesLogement;
use App\Security\LogementVoter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * POST /pieces/{id}/stockages : tout membre (A-11).
 * PATCH, DELETE /stockages/{id} : membres majeurs (A-10) ; un rangement ne se supprime que vide (I-10).
 *
 * @implements ProcessorInterface<NomEntree|StockageRessource, StockageRessource|null>
 */
final class StockageProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?StockageRessource
    {
        if ($operation instanceof Post) {
            \assert($data instanceof NomEntree);
            $stockage = $this->acces->piece($uriVariables['id'], LogementVoter::AJOUTER)->ajouterStockage($data->nom);
            $this->em->flush();

            return StockageRessource::depuis($stockage);
        }

        $stockage = $this->acces->stockage($uriVariables['id'], LogementVoter::MODIFIER);

        if ($operation instanceof DeleteOperationInterface) {
            $stockage->getPiece()->retirerStockage($stockage);
            $this->em->flush();

            return null;
        }

        \assert($data instanceof NomEntree);
        $stockage->renommer($data->nom);
        $this->em->flush();

        return StockageRessource::depuis($stockage);
    }
}
