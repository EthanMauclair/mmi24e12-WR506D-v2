<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NiveauRelation;
use App\Enum\StatutRelation;
use App\Exception\RegleMetierException;
use App\Repository\RelationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Le « suiveur » suit le « suivi », comme abonné ou comme ami (F-15 à F-18).
 *
 * Une seule table pour les deux niveaux (Q-08). On suit une personne, pas un logement (D-013).
 * Invariants : I-17 (pas de relation avec soi-même ; une seule par couple, garantie par la
 * contrainte d'unicité), I-18 (acceptée d'office vers un compte public), A-16 / I-21 (un compte
 * mineur est toujours privé, donc un abonné doit y être accepté).
 */
#[ORM\Entity(repositoryClass: RelationRepository::class)]
#[ORM\UniqueConstraint(name: 'relation_unique', columns: ['suiveur_id', 'suivi_id'])]
class Relation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $suiveur;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $suivi;

    #[ORM\Column(length: 20, enumType: NiveauRelation::class)]
    private NiveauRelation $niveau;

    #[ORM\Column(length: 20, enumType: StatutRelation::class)]
    private StatutRelation $statut;

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    private function __construct(
        Utilisateur $suiveur,
        Utilisateur $suivi,
        NiveauRelation $niveau,
        StatutRelation $statut,
        \DateTimeImmutable $maintenant,
    ) {
        if ($suiveur === $suivi) {
            throw new RegleMetierException('I-17', 'On ne se suit pas soi-même.');
        }
        $this->suiveur = $suiveur;
        $this->suivi = $suivi;
        $this->niveau = $niveau;
        $this->statut = $statut;
        $this->creeLe = $maintenant;
    }

    /** F-15, I-18 : acceptée d'office si le compte suivi est public, en attente s'il est privé (dont tout compte mineur). */
    public static function abonner(Utilisateur $suiveur, Utilisateur $suivi, \DateTimeImmutable $maintenant): self
    {
        $statut = $suivi->estPrive() ? StatutRelation::EnAttente : StatutRelation::Acceptee;

        return new self($suiveur, $suivi, NiveauRelation::Abonne, $statut, $maintenant);
    }

    /** F-17 : c'est la personne suivie qui ajoute son ami ; la relation est donc acceptée d'emblée. */
    public static function ajouterAmi(Utilisateur $moi, Utilisateur $ami, \DateTimeImmutable $maintenant): self
    {
        return new self($ami, $moi, NiveauRelation::Ami, StatutRelation::Acceptee, $maintenant);
    }

    /** F-16 : la personne suivie accepte la demande. */
    public function accepter(): void
    {
        $this->statut = StatutRelation::Acceptee;
    }

    /** F-17 sur une relation existante : un abonné, ou une demande en attente, devient ami. */
    public function devenirAmi(): void
    {
        if ($this->estAmi()) {
            throw new RegleMetierException('I-17', 'Cette personne fait déjà partie de vos amis.');
        }
        $this->niveau = NiveauRelation::Ami;
        $this->statut = StatutRelation::Acceptee;
    }

    /** F-18 : un ami retiré redevient abonné ; il garde les droits d'un abonné accepté (cas 4). */
    public function retrograder(): void
    {
        $this->niveau = NiveauRelation::Abonne;
    }

    public function estAcceptee(): bool
    {
        return StatutRelation::Acceptee === $this->statut;
    }

    public function estAmi(): bool
    {
        return NiveauRelation::Ami === $this->niveau;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSuiveur(): Utilisateur
    {
        return $this->suiveur;
    }

    public function getSuivi(): Utilisateur
    {
        return $this->suivi;
    }

    public function getNiveau(): NiveauRelation
    {
        return $this->niveau;
    }

    public function getStatut(): StatutRelation
    {
        return $this->statut;
    }

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }
}
