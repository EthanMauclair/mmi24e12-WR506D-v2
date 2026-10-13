<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\InvitationEntree;
use App\Entity\Membre;
use App\State\MembreProcessor;
use App\State\MembreProvider;

/**
 * Un membre d'un logement : propriétaire, co-propriétaire, ou invité en attente (H13).
 * Seul le pseudo de la personne est exposé, jamais son e-mail (S-05).
 */
#[ApiResource(
    shortName: 'Membre',
    operations: [
        new GetCollection(uriTemplate: '/logements/{id}/membres', uriVariables: ['id'], provider: MembreProvider::class, name: 'membres_du_logement'),
        new GetCollection(uriTemplate: '/moi/invitations', provider: MembreProvider::class, name: 'mes_invitations'),
        new Get(uriTemplate: '/membres/{id}', provider: MembreProvider::class),
        new Post(uriTemplate: '/logements/{id}/membres', uriVariables: ['id'], input: InvitationEntree::class, read: false, processor: MembreProcessor::class, name: 'membre_inviter'),
        new Post(uriTemplate: '/membres/{id}/acceptation', input: false, provider: MembreProvider::class, processor: MembreProcessor::class, status: 200, name: 'membre_accepter'),
        new Delete(uriTemplate: '/membres/{id}', provider: MembreProvider::class, processor: MembreProcessor::class),
    ],
)]
final class MembreRessource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    /** @var array{pseudo: string} */
    public array $utilisateur = ['pseudo' => ''];

    /** @var array{id: int|null, nom: string} */
    public array $logement = ['id' => null, 'nom' => ''];

    public string $role = '';

    public string $statut = '';

    public ?\DateTimeImmutable $arriveLe = null;

    public static function depuis(Membre $membre): self
    {
        $ressource = new self();
        $ressource->id = $membre->getId();
        $ressource->utilisateur = ['pseudo' => $membre->getUtilisateur()->getPseudo()];
        $ressource->logement = ['id' => $membre->getLogement()->getId(), 'nom' => $membre->getLogement()->getNom()];
        $ressource->role = $membre->getRole()->value;
        $ressource->statut = $membre->getStatut()->value;
        $ressource->arriveLe = $membre->getArriveLe();

        return $ressource;
    }
}
