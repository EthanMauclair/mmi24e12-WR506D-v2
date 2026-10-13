<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\LogementRessource;
use App\Dto\NomEntree;
use App\Entity\Logement;
use App\Security\AccesLogement;
use App\Security\LogementVoter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * POST : je crée un logement et j'en deviens propriétaire (I-05 ; refusé à un mineur, I-08).
 * PATCH : renommer (membres majeurs, A-10).
 * DELETE : propriétaire uniquement (A-09) ; transfert au co-propriétaire majeur arrivé en premier (D-011, I-09).
 *
 * @implements ProcessorInterface<NomEntree|LogementRessource, LogementRessource|null>
 */
final class LogementProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $horloge,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?LogementRessource
    {
        $moi = $this->acces->utilisateur();
        $maintenant = $this->horloge->now();

        if ($operation instanceof Post) {
            \assert($data instanceof NomEntree);
            $logement = Logement::creer($data->nom, $moi, $maintenant);
            $this->em->persist($logement);
            $this->em->flush();

            return LogementRessource::depuis($logement, $moi, avecPieces: true);
        }

        if ($operation instanceof DeleteOperationInterface) {
            $logement = $this->acces->logement($uriVariables['id'], LogementVoter::SUPPRIMER);
            if (null === $logement->retirerProprietaire($maintenant)) {
                $this->em->remove($logement);
            }
            $this->em->flush();

            return null;
        }

        \assert($data instanceof NomEntree);
        $logement = $this->acces->logement($uriVariables['id'], LogementVoter::MODIFIER);
        $logement->renommer($data->nom);
        $this->em->flush();

        return LogementRessource::depuis($logement, $moi, avecPieces: true);
    }
}
