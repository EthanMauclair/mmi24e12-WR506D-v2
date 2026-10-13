<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\PieceRessource;
use App\Dto\NomEntree;
use App\Security\AccesLogement;
use App\Security\LogementVoter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * POST /logements/{id}/pieces : tout membre, y compris mineur (A-11).
 * PATCH, DELETE /pieces/{id} : membres majeurs (A-10) ; une pièce ne se supprime que vide (I-10).
 *
 * @implements ProcessorInterface<NomEntree|PieceRessource, PieceRessource|null>
 */
final class PieceProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?PieceRessource
    {
        if ($operation instanceof Post) {
            \assert($data instanceof NomEntree);
            $piece = $this->acces->logement($uriVariables['id'], LogementVoter::AJOUTER)->ajouterPiece($data->nom);
            $this->em->flush();

            return PieceRessource::depuis($piece);
        }

        $piece = $this->acces->piece($uriVariables['id'], LogementVoter::MODIFIER);

        if ($operation instanceof DeleteOperationInterface) {
            $piece->getLogement()->retirerPiece($piece);
            $this->em->flush();

            return null;
        }

        \assert($data instanceof NomEntree);
        $piece->renommer($data->nom);
        $this->em->flush();

        return PieceRessource::depuis($piece);
    }
}
