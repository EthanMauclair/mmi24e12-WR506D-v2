<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Relation;
use App\Entity\Utilisateur;
use App\Enum\NiveauRelation;
use App\Enum\StatutRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Relation>
 */
class RelationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Relation::class);
    }

    /** La relation de $suiveur vers $suivi, quel que soit son état (I-17 : une seule par couple). */
    public function entre(Utilisateur $suiveur, Utilisateur $suivi): ?Relation
    {
        return $this->findOneBy(['suiveur' => $suiveur, 'suivi' => $suivi]);
    }

    /** A-03 : abonné ou ami accepté ; une demande en attente ne donne aucun droit. */
    public function estAcceptee(Utilisateur $suiveur, Utilisateur $suivi): bool
    {
        return null !== $this->findOneBy(['suiveur' => $suiveur, 'suivi' => $suivi, 'statut' => StatutRelation::Acceptee]);
    }

    /** @return list<Relation> F-16 : les demandes en attente sur mon compte, les plus anciennes d'abord */
    public function demandesVers(Utilisateur $suivi): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('s')
            ->join('r.suiveur', 's')
            ->where('r.suivi = :suivi')
            ->andWhere('r.statut = :statut')
            ->setParameter('suivi', $suivi)
            ->setParameter('statut', StatutRelation::EnAttente)
            ->orderBy('r.creeLe', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Relation> mes abonnés et mes amis acceptés, par pseudo */
    public function accepteesVers(Utilisateur $suivi, ?NiveauRelation $niveau = null): array
    {
        $requete = $this->createQueryBuilder('r')
            ->addSelect('s')
            ->join('r.suiveur', 's')
            ->where('r.suivi = :suivi')
            ->andWhere('r.statut = :statut')
            ->setParameter('suivi', $suivi)
            ->setParameter('statut', StatutRelation::Acceptee)
            ->orderBy('s.pseudo', 'ASC');

        if (null !== $niveau) {
            $requete->andWhere('r.niveau = :niveau')->setParameter('niveau', $niveau);
        }

        return $requete->getQuery()->getResult();
    }
}
