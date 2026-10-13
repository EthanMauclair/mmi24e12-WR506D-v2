<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Utilisateur;
use App\Enum\StatutMembre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    /** Contrat d'API : 30 éléments par page. */
    public const PAR_PAGE = 30;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * F-26 : les articles des logements dont je suis membre accepté (A-01), les plus récents d'abord.
     * Les comparaisons de texte suivent l'interclassement MySQL : sans casse ni accents.
     *
     * @return list<Article>
     */
    public function rechercher(
        Utilisateur $moi,
        ?string $texte = null,
        ?int $logement = null,
        ?int $categorie = null,
        ?string $couleur = null,
        ?string $marque = null,
        int $page = 1,
    ): array {
        $requete = $this->createQueryBuilder('a')
            ->addSelect('s', 'p', 'l', 'c')
            ->join('a.stockage', 's')
            ->join('s.piece', 'p')
            ->join('p.logement', 'l')
            ->join('a.categorie', 'c')
            ->join('l.membres', 'm', 'WITH', 'm.utilisateur = :moi AND m.statut = :accepte')
            ->setParameter('moi', $moi)
            ->setParameter('accepte', StatutMembre::Accepte)
            ->orderBy('a.creeLe', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setFirstResult(($page - 1) * self::PAR_PAGE)
            ->setMaxResults(self::PAR_PAGE);

        if (null !== $texte) {
            $requete->andWhere('a.marque LIKE :texte OR a.couleur LIKE :texte OR a.note LIKE :texte')
                ->setParameter('texte', self::contient($texte));
        }
        if (null !== $logement) {
            $requete->andWhere('l.id = :logement')->setParameter('logement', $logement);
        }
        if (null !== $categorie) {
            $requete->andWhere('c.id = :categorie')->setParameter('categorie', $categorie);
        }
        if (null !== $couleur) {
            $requete->andWhere('a.couleur LIKE :couleur')->setParameter('couleur', self::contient($couleur));
        }
        if (null !== $marque) {
            $requete->andWhere('a.marque LIKE :marque')->setParameter('marque', self::contient($marque));
        }

        return $requete->getQuery()->getResult();
    }

    /** Une photo ne sert qu'à un seul article. */
    public function photoDejaUtilisee(string $photo): bool
    {
        return null !== $this->findOneBy(['photo' => $photo]);
    }

    private static function contient(string $texte): string
    {
        return '%'.addcslashes($texte, '%_\\').'%';
    }
}
