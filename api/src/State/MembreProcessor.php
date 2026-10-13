<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\MembreRessource;
use App\Dto\InvitationEntree;
use App\Repository\UtilisateurRepository;
use App\Security\AccesLogement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * - inviter : un membre majeur invite une personne par son pseudo (F-11, A-10, I-07, H18) ;
 * - accepter : seule la personne invitée accepte son invitation (H13) ;
 * - DELETE : retirer un co-propriétaire, ou quitter le logement (H19).
 *
 * @implements ProcessorInterface<InvitationEntree|MembreRessource|null, MembreRessource|null>
 */
final class MembreProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly MembreProvider $membres,
        private readonly UtilisateurRepository $utilisateurs,
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $horloge,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?MembreRessource
    {
        $moi = $this->acces->utilisateur();
        $maintenant = $this->horloge->now();

        if ('membre_inviter' === $operation->getName()) {
            \assert($data instanceof InvitationEntree);
            $logement = $this->acces->logement($uriVariables['id']);
            $invite = $this->utilisateurs->findOneBy(['pseudo' => $data->pseudo])
                ?? throw new NotFoundHttpException('Aucun compte ne porte ce pseudo.');

            $membre = $logement->inviter($invite, $moi, $maintenant);
            $this->em->flush();

            return MembreRessource::depuis($membre);
        }

        $membre = $this->membres->membreVisible($uriVariables['id']);

        if ($operation instanceof DeleteOperationInterface) {
            $membre->getLogement()->retirerMembre($membre, $moi, $maintenant);
            $this->em->flush();

            return null;
        }

        if ($membre->getUtilisateur() !== $moi) {
            throw new AccessDeniedHttpException('Seule la personne invitée peut accepter.');
        }
        $membre->accepter($maintenant);
        $this->em->flush();

        return MembreRessource::depuis($membre);
    }
}
