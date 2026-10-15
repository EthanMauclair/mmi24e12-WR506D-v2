<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Utilisateur;
use App\Repository\RelationRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * A-03 appliqué à une personne plutôt qu'à un article : peut-on voir ses articles non personnels ?
 * Partagé par la liste de ses articles (F-21) et par son profil (F-04), pour que le compteur
 * d'articles ne révèle jamais ce que la liste cache.
 */
final class AccesProfil
{
    public function __construct(
        private readonly Security $securite,
        private readonly RelationRepository $relations,
    ) {
    }

    /** Compte public, soi-même, ou abonné / ami accepté ; une demande en attente ne donne rien. */
    public function voitLesArticlesDe(Utilisateur $auteur): bool
    {
        $moi = $this->securite->getUser();
        if (!$auteur->estPrive() || $moi === $auteur) {
            return true;
        }

        return $moi instanceof Utilisateur && $this->relations->estAcceptee($moi, $auteur);
    }
}
