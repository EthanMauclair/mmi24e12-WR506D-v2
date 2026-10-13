<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /** Connexion par e-mail ou par pseudo (contrat d'API : champ « identifiant »). */
    public function loadUserByIdentifier(string $identifier): ?Utilisateur
    {
        $identifiant = mb_strtolower(trim($identifier));

        return $this->createQueryBuilder('u')
            ->where('u.email = :identifiant OR u.pseudo = :identifiant')
            ->setParameter('identifiant', $identifiant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function pseudoOuEmailExiste(string $pseudo, string $email): bool
    {
        return null !== $this->createQueryBuilder('u')
            ->select('u.id')
            ->where('u.pseudo = :pseudo OR u.email = :email')
            ->setParameter('pseudo', $pseudo)
            ->setParameter('email', mb_strtolower($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
