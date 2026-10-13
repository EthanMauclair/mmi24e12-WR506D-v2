<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\MembreRessource;
use App\Entity\Membre;
use App\Repository\MembreRepository;
use App\Security\AccesLogement;
use App\Security\LogementVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * - GET /logements/{id}/membres : réservé aux membres du logement.
 * - GET /moi/invitations : mes invitations en attente.
 * - /membres/{id} : visible par la personne concernée et par les membres du logement (404 sinon).
 *
 * @implements ProviderInterface<MembreRessource>
 */
final class MembreProvider implements ProviderInterface
{
    public function __construct(
        private readonly AccesLogement $acces,
        private readonly MembreRepository $membres,
        private readonly Security $securite,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $moi = $this->acces->utilisateur();

        return match ($operation->getName()) {
            'membres_du_logement' => array_values(array_map(
                MembreRessource::depuis(...),
                $this->acces->logement($uriVariables['id'])->getMembres()->toArray(),
            )),
            'mes_invitations' => array_map(MembreRessource::depuis(...), $this->membres->invitationsEnAttente($moi)),
            default => MembreRessource::depuis($this->membreVisible($uriVariables['id'])),
        };
    }

    public function membreVisible(mixed $id): Membre
    {
        $membre = is_numeric($id) ? $this->membres->find((int) $id) : null;
        if (null === $membre
            || ($membre->getUtilisateur() !== $this->acces->utilisateur()
                && !$this->securite->isGranted(LogementVoter::VOIR, $membre->getLogement()))) {
            throw new NotFoundHttpException('Ressource introuvable.');
        }

        return $membre;
    }
}
