<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\RegleMetierException;
use App\Repository\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un vêtement, des chaussures, un objet…
 *
 * Invariants garantis ici : I-10 (toujours rangé dans un stockage), I-11 (l'auteur est membre du logement).
 * L'auteur est fixé à la création et n'a pas de méthode pour le changer (A-15).
 */
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Index(name: 'article_categorie_idx', columns: ['categorie_id'])]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Stockage $stockage;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $auteur;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Categorie $categorie;

    #[ORM\Column(length: 80, nullable: true)]
    #[Assert\Length(max: 80)]
    private ?string $marque = null;

    #[ORM\Column(length: 40, nullable: true)]
    #[Assert\Length(max: 40)]
    private ?string $couleur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $note = null;

    /** Chemin de la photo dans le stockage Flysystem, jamais une URL publique (D-015). */
    #[ORM\Column(length: 255)]
    private string $photo;

    #[ORM\Column]
    private bool $estPersonnel = false;

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    private function __construct(
        Utilisateur $auteur,
        Stockage $stockage,
        Categorie $categorie,
        string $photo,
        \DateTimeImmutable $maintenant,
    ) {
        $this->auteur = $auteur;
        $this->stockage = $stockage;
        $this->categorie = $categorie;
        $this->photo = $photo;
        $this->creeLe = $maintenant;
    }

    /** F-07. L'auteur est toujours l'utilisateur connecté, jamais une valeur envoyée par le client. */
    public static function creer(
        Utilisateur $auteur,
        Stockage $stockage,
        Categorie $categorie,
        string $photo,
        \DateTimeImmutable $maintenant,
    ): self {
        self::verifierQueAuteurEstMembre($auteur, $stockage);

        $article = new self($auteur, $stockage, $categorie, $photo, $maintenant);
        $stockage->accueillir($article);

        return $article;
    }

    /** F-09. On ne déplace un article que dans un logement dont son auteur est membre (I-11). */
    public function deplacerVers(Stockage $stockage): void
    {
        if ($stockage === $this->stockage) {
            return;
        }
        self::verifierQueAuteurEstMembre($this->auteur, $stockage);

        $this->stockage->liberer($this);
        $this->stockage = $stockage;
        $stockage->accueillir($this);
    }

    public function decrire(Categorie $categorie, ?string $marque, ?string $couleur, ?string $note): void
    {
        $this->categorie = $categorie;
        $this->marque = self::texteOuNull($marque);
        $this->couleur = self::texteOuNull($couleur);
        $this->note = self::texteOuNull($note);
    }

    /** F-10. */
    public function marquerPersonnel(bool $estPersonnel): void
    {
        $this->estPersonnel = $estPersonnel;
    }

    private static function verifierQueAuteurEstMembre(Utilisateur $auteur, Stockage $stockage): void
    {
        if (!$stockage->getLogement()->estMembreAccepte($auteur)) {
            throw new RegleMetierException('I-11', 'L\'auteur d\'un article doit être membre du logement où il est rangé.');
        }
    }

    private static function texteOuNull(?string $texte): ?string
    {
        $texte = null === $texte ? null : trim($texte);

        return '' === $texte ? null : $texte;
    }

    public function getLogement(): Logement
    {
        return $this->stockage->getLogement();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStockage(): Stockage
    {
        return $this->stockage;
    }

    public function getAuteur(): Utilisateur
    {
        return $this->auteur;
    }

    public function getCategorie(): Categorie
    {
        return $this->categorie;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getPhoto(): string
    {
        return $this->photo;
    }

    public function estPersonnel(): bool
    {
        return $this->estPersonnel;
    }

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }
}
