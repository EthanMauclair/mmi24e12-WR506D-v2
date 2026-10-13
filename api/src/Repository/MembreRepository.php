<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Membre;
use App\Entity\Utilisateur;
use App\Enum\RoleMembre;
use App\Enum\StatutMembre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membre>
 */
class MembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membre::class);
    }

    /** I-04 : un propriétaire ne peut pas supprimer son compte. */
    public function estProprietaireDUnLogement(Utilisateur $utilisateur): bool
    {
        return null !== $this->createQueryBuilder('m')
            ->select('m.id')
            ->where('m.utilisateur = :utilisateur')
            ->andWhere('m.role = :role')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('role', RoleMembre::Proprietaire)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return list<Membre> mes adhésions acceptées, avec leur logement */
    public function adhesionsAcceptees(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('m')
            ->addSelect('l')
            ->join('m.logement', 'l')
            ->where('m.utilisateur = :utilisateur')
            ->andWhere('m.statut = :statut')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('statut', StatutMembre::Accepte)
            ->orderBy('l.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Membre> */
    public function invitationsEnAttente(Utilisateur $utilisateur): array
    {
        return $this->findBy(['utilisateur' => $utilisateur, 'statut' => StatutMembre::Invite]);
    }
}
