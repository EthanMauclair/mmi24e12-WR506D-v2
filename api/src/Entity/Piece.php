<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PieceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PieceRepository::class)]
class Piece
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pieces')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Logement $logement;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $nom;

    /** @var Collection<int, Stockage> */
    #[ORM\OneToMany(targetEntity: Stockage::class, mappedBy: 'piece', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $stockages;

    /** Créée par Logement::ajouterPiece(). */
    public function __construct(Logement $logement, string $nom)
    {
        $this->logement = $logement;
        $this->nom = $nom;
        $this->stockages = new ArrayCollection();
    }

    public function renommer(string $nom): void
    {
        $this->nom = $nom;
    }

    public function ajouterStockage(string $nom): Stockage
    {
        $stockage = new Stockage($this, $nom);
        $this->stockages->add($stockage);

        return $stockage;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogement(): Logement
    {
        return $this->logement;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    /** @return Collection<int, Stockage> */
    public function getStockages(): Collection
    {
        return $this->stockages;
    }
}
