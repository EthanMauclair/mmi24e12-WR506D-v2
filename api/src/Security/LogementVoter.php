<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Logement;
use App\Entity\Utilisateur;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur un logement, son arborescence et ses membres (docs/06-modele-autorisation.md).
 *
 * @extends Voter<string, Logement>
 */
final class LogementVoter extends Voter
{
    /** A-01 : voir le logement, ses pièces et ses stockages. */
    public const VOIR = 'LOGEMENT_VOIR';
    /** A-11 : ajouter une pièce ou un rangement (y compris un co-propriétaire mineur). */
    public const AJOUTER = 'LOGEMENT_AJOUTER';
    /** A-10, A-11 : renommer, supprimer une pièce ou un rangement, inviter (majeurs uniquement). */
    public const MODIFIER = 'LOGEMENT_MODIFIER';
    /** A-09 : supprimer le logement (propriétaire uniquement). */
    public const SUPPRIMER = 'LOGEMENT_SUPPRIMER';

    public function __construct(
        private readonly ClockInterface $horloge,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Logement
            && \in_array($attribute, [self::VOIR, self::AJOUTER, self::MODIFIER, self::SUPPRIMER], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }

        return match ($attribute) {
            self::VOIR, self::AJOUTER => $subject->estMembreAccepte($utilisateur),
            self::MODIFIER => $subject->estMembreMajeur($utilisateur, $this->horloge->now()),
            self::SUPPRIMER => $subject->getProprietaire() === $utilisateur,
            default => false,
        };
    }
}
