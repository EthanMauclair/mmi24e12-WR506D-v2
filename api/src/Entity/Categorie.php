<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Liste fixe, extensible par l'administrateur uniquement (D-013).
 * Sert au remplissage par la photo et aux filtres (F-26, F-27).
 */
#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60, unique: true)]
    private string $nom;

    public function __construct(string $nom)
    {
        $this->nom = $nom;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }
}
