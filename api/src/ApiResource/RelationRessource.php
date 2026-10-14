<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\AmiEntree;
use App\Dto\RelationModification;
use App\Entity\Relation;
use App\Entity\Utilisateur;
use App\State\RelationProcessor;
use App\State\RelationProvider;

/**
 * Une relation : le suiveur suit le suivi, comme abonné ou comme ami (F-15 à F-18).
 * Seuls les pseudos sont exposés, jamais l'e-mail ni la date de naissance (S-05).
 */
#[ApiResource(
    shortName: 'Relation',
    operations: [
        new GetCollection(uriTemplate: '/moi/demandes-abonnement', provider: RelationProvider::class, name: 'mes_demandes_abonnement'),
        new GetCollection(uriTemplate: '/moi/relations', provider: RelationProvider::class, name: 'mes_relations'),
        new Get(uriTemplate: '/relations/{id}', provider: RelationProvider::class),
        new Post(
            uriTemplate: '/utilisateurs/{pseudo}/abonnement',
            uriVariables: ['pseudo' => new Link(fromClass: Utilisateur::class, identifiers: ['pseudo'])],
            input: false,
            read: false,
            processor: RelationProcessor::class,
            name: 'abonnement_creer',
        ),
        new Delete(
            uriTemplate: '/utilisateurs/{pseudo}/abonnement',
            uriVariables: ['pseudo' => new Link(fromClass: Utilisateur::class, identifiers: ['pseudo'])],
            provider: RelationProvider::class,
            processor: RelationProcessor::class,
            name: 'abonnement_supprimer',
        ),
        new Post(uriTemplate: '/moi/amis', input: AmiEntree::class, read: false, processor: RelationProcessor::class, name: 'ami_ajouter'),
        new Patch(uriTemplate: '/relations/{id}', input: RelationModification::class, provider: RelationProvider::class, processor: RelationProcessor::class, name: 'relation_modifier'),
        new Delete(uriTemplate: '/relations/{id}', provider: RelationProvider::class, processor: RelationProcessor::class, name: 'relation_supprimer'),
    ],
)]
final class RelationRessource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    /** @var array{pseudo: string} */
    public array $suiveur = ['pseudo' => ''];

    /** @var array{pseudo: string} */
    public array $suivi = ['pseudo' => ''];

    public string $niveau = '';

    public string $statut = '';

    public ?\DateTimeImmutable $creeLe = null;

    public static function depuis(Relation $relation): self
    {
        $ressource = new self();
        $ressource->id = $relation->getId();
        $ressource->suiveur = ['pseudo' => $relation->getSuiveur()->getPseudo()];
        $ressource->suivi = ['pseudo' => $relation->getSuivi()->getPseudo()];
        $ressource->niveau = $relation->getNiveau()->value;
        $ressource->statut = $relation->getStatut()->value;
        $ressource->creeLe = $relation->getCreeLe();

        return $ressource;
    }
}
