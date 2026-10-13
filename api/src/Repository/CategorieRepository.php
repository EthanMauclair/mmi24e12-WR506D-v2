<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categorie>
 */
class CategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }

    /**
     * La comparaison suit l'interclassement MySQL (utf8mb4_0900_ai_ci) : sans casse ni accents,
     * donc « vinyle » ou « VINYLE » retrouvent « Vinyle ».
     */
    public function nommee(string $nom): ?Categorie
    {
        return $this->findOneBy(['nom' => $nom]);
    }
}
