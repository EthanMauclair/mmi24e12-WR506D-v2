<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\Relation;
use App\Entity\Utilisateur;
use App\State\ProfilProvider;

/**
 * Profil d'une personne (F-04, F-21), visible sans compte.
 * Jamais d'e-mail ni de date de naissance (S-05) : seul le pseudo identifie la personne.
 */
#[ApiResource(
    shortName: 'Profil',
    operations: [
        new Get(uriTemplate: '/utilisateurs/{pseudo}', provider: ProfilProvider::class),
    ],
)]
final class ProfilRessource
{
    #[ApiProperty(identifier: true)]
    public string $pseudo = '';

    public bool $estPrive = false;

    /** Abonnés et amis acceptés. */
    public int $nombreAbonnes = 0;

    /** Articles non personnels ; absent si on ne peut pas les voir (compte privé, A-03). */
    public ?int $nombreArticles = null;

    /**
     * Ma relation avec ce compte (acceptée ou en attente), si je suis connecté et que j'en ai une.
     *
     * @var array<string, mixed>|null
     */
    public ?array $maRelation = null;

    public static function depuis(
        Utilisateur $utilisateur,
        int $nombreAbonnes,
        ?int $nombreArticles,
        ?Relation $maRelation,
    ): self {
        $ressource = new self();
        $ressource->pseudo = $utilisateur->getPseudo();
        $ressource->estPrive = $utilisateur->estPrive();
        $ressource->nombreAbonnes = $nombreAbonnes;
        $ressource->nombreArticles = $nombreArticles;
        if (null !== $maRelation) {
            // Mêmes champs que le schéma Relation du contrat, en tableau pour qu'ils soient bien imbriqués.
            $relation = RelationRessource::depuis($maRelation);
            $ressource->maRelation = [
                'id' => $relation->id,
                'suiveur' => $relation->suiveur,
                'suivi' => $relation->suivi,
                'niveau' => $relation->niveau,
                'statut' => $relation->statut,
                'creeLe' => $relation->creeLe?->format(\DATE_ATOM),
            ];
        }

        return $ressource;
    }
}
