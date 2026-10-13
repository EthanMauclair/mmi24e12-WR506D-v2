<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RoleMembre;
use App\Enum\StatutMembre;
use App\Repository\MembreRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lien entre un utilisateur et un logement.
 *
 * Une seule table pour propriétaire et co-propriétaires (D-013) : transférer un logement
 * revient à changer un rôle. La contrainte d'unicité garantit I-07.
 */
#[ORM\Entity(repositoryClass: MembreRepository::class)]
#[ORM\UniqueConstraint(name: 'membre_unique', columns: ['utilisateur_id', 'logement_id'])]
class Membre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\ManyToOne(inversedBy: 'membres')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Logement $logement;

    #[ORM\Column(length: 20, enumType: RoleMembre::class)]
    private RoleMembre $role;

    #[ORM\Column(length: 20, enumType: StatutMembre::class)]
    private StatutMembre $statut;

    /** Date d'acceptation : sert à désigner le « premier arrivé » (Q-20). Null tant que l'invitation n'est pas acceptée. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $arriveLe;

    /** Réservé à Logement, qui garantit les invariants I-05 à I-07. */
    public function __construct(
        Utilisateur $utilisateur,
        Logement $logement,
        RoleMembre $role,
        StatutMembre $statut,
        ?\DateTimeImmutable $arriveLe,
    ) {
        $this->utilisateur = $utilisateur;
        $this->logement = $logement;
        $this->role = $role;
        $this->statut = $statut;
        $this->arriveLe = $arriveLe;
    }

    public function accepter(\DateTimeImmutable $maintenant): void
    {
        if (StatutMembre::Accepte === $this->statut) {
            return;
        }
        $this->statut = StatutMembre::Accepte;
        $this->arriveLe = $maintenant;
    }

    /** Réservé à Logement lors d'un transfert de propriété (D-011). */
    public function devenirProprietaire(): void
    {
        $this->role = RoleMembre::Proprietaire;
    }

    public function estAccepte(): bool
    {
        return StatutMembre::Accepte === $this->statut;
    }

    public function estProprietaire(): bool
    {
        return RoleMembre::Proprietaire === $this->role;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getLogement(): Logement
    {
        return $this->logement;
    }

    public function getRole(): RoleMembre
    {
        return $this->role;
    }

    public function getStatut(): StatutMembre
    {
        return $this->statut;
    }

    public function getArriveLe(): ?\DateTimeImmutable
    {
        return $this->arriveLe;
    }
}
