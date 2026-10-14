<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Article;
use App\Entity\Utilisateur;
use App\Repository\RelationRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits sur un article (docs/06-modele-autorisation.md).
 *
 * @extends Voter<string, Article>
 */
final class ArticleVoter extends Voter
{
    /** A-01, A-03, A-04, A-16. */
    public const VOIR = 'ARTICLE_VOIR';
    /** A-10, A-11 : modifier, déplacer, marquer personnel, supprimer. */
    public const MODIFIER = 'ARTICLE_MODIFIER';

    public function __construct(
        private readonly ClockInterface $horloge,
        private readonly RelationRepository $relations,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Article && \in_array($attribute, [self::VOIR, self::MODIFIER], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $moi = $token->getUser();
        $moi = $moi instanceof Utilisateur ? $moi : null;
        $logement = $subject->getLogement();
        $estMembre = null !== $moi && $logement->estMembreAccepte($moi);

        return match ($attribute) {
            // A-01 : un membre voit tout le logement, articles personnels compris.
            self::VOIR => $estMembre || $this->visiblePourUnVisiteur($subject, $moi),
            // A-10 : un membre majeur modifie tous les articles ; A-11 : un mineur, seulement les siens.
            self::MODIFIER => $estMembre
                && ($logement->estMembreMajeur($moi, $this->horloge->now()) || $subject->getAuteur() === $moi),
            default => false,
        };
    }

    /**
     * A-03 : un article non personnel est visible si le compte de son auteur est public, ou si
     * l'on a une relation acceptée (abonné ou ami) avec cet auteur ; jamais avec le propriétaire
     * du logement à sa place (D-013).
     * A-04 : un article personnel ne l'est jamais hors du logement (tenues et liens : lots suivants).
     * A-16 : un compte mineur est toujours privé (I-21) : son abonné doit avoir été accepté.
     */
    private function visiblePourUnVisiteur(Article $article, ?Utilisateur $moi): bool
    {
        if ($article->estPersonnel()) {
            return false;
        }
        $auteur = $article->getAuteur();

        return !$auteur->estPrive() || (null !== $moi && $this->relations->estAcceptee($moi, $auteur));
    }
}
