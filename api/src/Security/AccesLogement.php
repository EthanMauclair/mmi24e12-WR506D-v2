<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Logement;
use App\Entity\Piece;
use App\Entity\Stockage;
use App\Entity\Utilisateur;
use App\Repository\LogementRepository;
use App\Repository\PieceRepository;
use App\Repository\StockageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Charge un logement (ou une pièce, un rangement) en appliquant la règle D-014 :
 * ce qu'on n'a pas le droit de voir répond 404, ce qu'on voit sans pouvoir le modifier répond 403.
 */
final class AccesLogement
{
    public function __construct(
        private readonly Security $securite,
        private readonly LogementRepository $logements,
        private readonly PieceRepository $pieces,
        private readonly StockageRepository $stockages,
    ) {
    }

    public function utilisateur(): Utilisateur
    {
        $utilisateur = $this->securite->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw new UnauthorizedHttpException('Bearer', 'Connexion requise.');
        }

        return $utilisateur;
    }

    /** @param LogementVoter::VOIR|LogementVoter::AJOUTER|LogementVoter::MODIFIER|LogementVoter::SUPPRIMER $droit */
    public function logement(mixed $id, string $droit = LogementVoter::VOIR): Logement
    {
        $logement = $this->logements->find($this->entier($id));

        return $this->verifier($logement, $droit);
    }

    /** @param LogementVoter::VOIR|LogementVoter::AJOUTER|LogementVoter::MODIFIER|LogementVoter::SUPPRIMER $droit */
    public function piece(mixed $id, string $droit = LogementVoter::VOIR): Piece
    {
        $piece = $this->pieces->find($this->entier($id));
        $this->verifier($piece?->getLogement(), $droit);

        return $piece;
    }

    /** @param LogementVoter::VOIR|LogementVoter::AJOUTER|LogementVoter::MODIFIER|LogementVoter::SUPPRIMER $droit */
    public function stockage(mixed $id, string $droit = LogementVoter::VOIR): Stockage
    {
        $stockage = $this->stockages->find($this->entier($id));
        $this->verifier($stockage?->getLogement(), $droit);

        return $stockage;
    }

    public function exiger(Logement $logement, string $droit): void
    {
        if (!$this->securite->isGranted($droit, $logement)) {
            throw new AccessDeniedHttpException('Action non autorisée sur ce logement.');
        }
    }

    private function verifier(?Logement $logement, string $droit): Logement
    {
        if (null === $logement || !$this->securite->isGranted(LogementVoter::VOIR, $logement)) {
            throw new NotFoundHttpException('Ressource introuvable.');
        }
        $this->exiger($logement, $droit);

        return $logement;
    }

    private function entier(mixed $id): int
    {
        if (!is_numeric($id)) {
            throw new NotFoundHttpException('Ressource introuvable.');
        }

        return (int) $id;
    }
}
