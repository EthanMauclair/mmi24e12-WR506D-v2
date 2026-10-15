<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\RoleMembre;
use App\Enum\StatutMembre;
use App\Exception\RegleMetierException;
use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un logement et ses membres. Porte les règles de propriété et de co-propriété.
 *
 * Invariants garantis ici : I-05, I-06, I-07, I-08, I-09.
 */
#[ORM\Entity(repositoryClass: LogementRepository::class)]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $nom;

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    /** @var Collection<int, Membre> */
    #[ORM\OneToMany(targetEntity: Membre::class, mappedBy: 'logement', cascade: ['persist'], orphanRemoval: true)]
    private Collection $membres;

    /** @var Collection<int, Piece> */
    #[ORM\OneToMany(targetEntity: Piece::class, mappedBy: 'logement', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $pieces;

    private function __construct(string $nom, \DateTimeImmutable $maintenant)
    {
        $this->nom = $nom;
        $this->creeLe = $maintenant;
        $this->membres = new ArrayCollection();
        $this->pieces = new ArrayCollection();
    }

    /** F-05. Le créateur devient l'unique propriétaire (I-05) ; un mineur ne crée pas de logement (I-08). */
    public static function creer(string $nom, Utilisateur $createur, \DateTimeImmutable $maintenant): self
    {
        if ($createur->estMineur($maintenant)) {
            throw new RegleMetierException('I-08', 'Un compte mineur ne peut pas créer de logement.');
        }

        $logement = new self($nom, $maintenant);
        $logement->membres->add(new Membre($createur, $logement, RoleMembre::Proprietaire, StatutMembre::Accepte, $maintenant));

        return $logement;
    }

    public function renommer(string $nom): void
    {
        $this->nom = $nom;
    }

    /**
     * F-11. Invite un co-propriétaire, qui devra accepter (H13).
     *
     * Hypothèse H18 : un mineur ne peut être invité que dans un logement dont son référent est membre,
     * pour qu'il reste « rattaché à un compte majeur pour le logement » (compte rendu, § 5).
     */
    public function inviter(Utilisateur $invite, Utilisateur $parQui, \DateTimeImmutable $maintenant): Membre
    {
        if (!$this->estMembreMajeur($parQui, $maintenant)) {
            throw new RegleMetierException('A-10', 'Seuls le propriétaire et les co-propriétaires majeurs invitent.');
        }
        if (null !== $this->membreDe($invite)) {
            throw new RegleMetierException('I-07', 'Cette personne est déjà membre ou invitée.');
        }
        if ($invite->estMineur($maintenant)
            && (null === $invite->getReferent() || !$this->estMembreAccepte($invite->getReferent()))) {
            throw new RegleMetierException('H18', 'Un compte mineur ne rejoint que le logement de son référent.');
        }

        $membre = new Membre($invite, $this, RoleMembre::Coproprietaire, StatutMembre::Invite, null);
        $this->membres->add($membre);

        return $membre;
    }

    /**
     * F-05, D-011, I-09. Le propriétaire supprime son logement :
     * ses articles sont supprimés ; le co-propriétaire majeur arrivé en premier devient propriétaire.
     *
     * @return Utilisateur|null le nouveau propriétaire, ou null si le logement doit être supprimé entièrement
     */
    public function retirerProprietaire(\DateTimeImmutable $maintenant): ?Utilisateur
    {
        $ancien = $this->getMembreProprietaire();

        $successeur = null;
        foreach ($this->membres as $membre) {
            if ($membre === $ancien
                || !$membre->estAccepte()
                || $membre->getUtilisateur()->estMineur($maintenant)) {
                continue;
            }
            if (null === $successeur || $membre->getArriveLe() < $successeur->getArriveLe()) {
                $successeur = $membre;
            }
        }

        if (null === $successeur) {
            return null;
        }

        foreach ($this->pieces as $piece) {
            foreach ($piece->getStockages() as $stockage) {
                $stockage->retirerArticlesDe($ancien->getUtilisateur());
            }
        }
        $this->membres->removeElement($ancien);
        $successeur->devenirProprietaire();

        return $successeur->getUtilisateur();
    }

    /**
     * F-11. Le propriétaire retire un co-propriétaire (ou annule une invitation),
     * ou un co-propriétaire quitte le logement de lui-même (A-17).
     * Dans les deux cas, ses articles rangés dans le logement sont supprimés (réponse client, H19).
     */
    public function retirerMembre(Membre $membre, Utilisateur $parQui): void
    {
        if ($membre->getLogement() !== $this) {
            throw new \InvalidArgumentException('Ce membre n\'appartient pas à ce logement.');
        }
        if ($membre->estProprietaire()) {
            throw new RegleMetierException('A-15', 'Le propriétaire ne quitte son logement qu\'en le supprimant.');
        }
        if ($membre->getUtilisateur() !== $parQui && $this->getProprietaire() !== $parQui) {
            throw new RegleMetierException('A-17', 'Seul le propriétaire retire un co-propriétaire.');
        }

        foreach ($this->pieces as $piece) {
            foreach ($piece->getStockages() as $stockage) {
                $stockage->retirerArticlesDe($membre->getUtilisateur());
            }
        }
        $this->membres->removeElement($membre);
    }

    /** Une pièce ne se supprime que vide : un article est toujours rangé (I-10). */
    public function retirerPiece(Piece $piece): void
    {
        foreach ($piece->getStockages() as $stockage) {
            if (!$stockage->getArticles()->isEmpty()) {
                throw new RegleMetierException('I-10', 'Videz d\'abord les rangements de cette pièce.');
            }
        }
        $this->pieces->removeElement($piece);
    }

    public function ajouterPiece(string $nom): Piece
    {
        $piece = new Piece($this, $nom);
        $this->pieces->add($piece);

        return $piece;
    }

    public function membreDe(Utilisateur $utilisateur): ?Membre
    {
        foreach ($this->membres as $membre) {
            if ($membre->getUtilisateur() === $utilisateur) {
                return $membre;
            }
        }

        return null;
    }

    public function estMembreAccepte(Utilisateur $utilisateur): bool
    {
        return true === $this->membreDe($utilisateur)?->estAccepte();
    }

    public function estMembreMajeur(Utilisateur $utilisateur, \DateTimeImmutable $aujourdhui): bool
    {
        return $this->estMembreAccepte($utilisateur) && !$utilisateur->estMineur($aujourdhui);
    }

    public function getProprietaire(): Utilisateur
    {
        return $this->getMembreProprietaire()->getUtilisateur();
    }

    private function getMembreProprietaire(): Membre
    {
        foreach ($this->membres as $membre) {
            if ($membre->estProprietaire()) {
                return $membre;
            }
        }

        // Inatteignable : creer() pose le propriétaire et le transfert en garde toujours un (I-05, I-09).
        throw new \LogicException('I-05 violé : logement sans propriétaire.'); // @codeCoverageIgnore
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }

    /** @return Collection<int, Membre> */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    /** @return Collection<int, Piece> */
    public function getPieces(): Collection
    {
        return $this->pieces;
    }
}
