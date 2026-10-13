<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/** Un rangement (armoire, commode, penderie…) dans une pièce. */
#[ORM\Entity(repositoryClass: StockageRepository::class)]
class Stockage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stockages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Piece $piece;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $nom;

    /** @var Collection<int, Article> */
    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'stockage', orphanRemoval: true)]
    private Collection $articles;

    /** Créé par Piece::ajouterStockage(). */
    public function __construct(Piece $piece, string $nom)
    {
        $this->piece = $piece;
        $this->nom = $nom;
        $this->articles = new ArrayCollection();
    }

    public function renommer(string $nom): void
    {
        $this->nom = $nom;
    }

    /** Appelé par Article lors de sa création ou de son déplacement. */
    public function accueillir(Article $article): void
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
        }
    }

    /** Appelé par Article lors de son déplacement : sans suppression de l'article. */
    public function liberer(Article $article): void
    {
        $this->articles->removeElement($article);
    }

    /** D-011 : supprime les articles d'un auteur (orphanRemoval les efface en base). */
    public function retirerArticlesDe(Utilisateur $auteur): void
    {
        foreach ($this->articles->toArray() as $article) {
            if ($article->getAuteur() === $auteur) {
                $this->articles->removeElement($article);
            }
        }
    }

    public function getLogement(): Logement
    {
        return $this->piece->getLogement();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPiece(): Piece
    {
        return $this->piece;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    /** @return Collection<int, Article> */
    public function getArticles(): Collection
    {
        return $this->articles;
    }
}
