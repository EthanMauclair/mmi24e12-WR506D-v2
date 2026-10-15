<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Utilisateur;
use App\Enum\StatutMembre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        ?int $piece = null,
        ?int $stockage = null,
    ): array {
        $requete = $this->requetePaginee($page)
            ->join('l.membres', 'm', 'WITH', 'm.utilisateur = :moi AND m.statut = :accepte')
            ->setParameter('moi', $moi)
            ->setParameter('accepte', StatutMembre::Accepte);

        if (null !== $texte) {
            $requete->andWhere('a.marque LIKE :texte OR a.couleur LIKE :texte OR a.note LIKE :texte')
                ->setParameter('texte', self::contient($texte));
        }
        if (null !== $logement) {
            $requete->andWhere('l.id = :logement')->setParameter('logement', $logement);
        }
        if (null !== $piece) {
            $requete->andWhere('p.id = :piece')->setParameter('piece', $piece);
        }
        if (null !== $stockage) {
            $requete->andWhere('s.id = :stockage')->setParameter('stockage', $stockage);
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

    /**
     * F-21, F-27 : les articles non personnels dont cette personne est l'auteur, les plus récents d'abord.
     * La décision de montrer ou non ces articles (compte public ou privé) revient à l'appelant.
     *
     * @return list<Article>
     */
    public function visiblesDe(Utilisateur $auteur, ?int $logement = null, ?int $categorie = null, int $page = 1): array
    {
        $requete = $this->requetePaginee($page)
            ->andWhere('a.auteur = :auteur')
            ->andWhere('a.estPersonnel = false')
            ->setParameter('auteur', $auteur);

        if (null !== $logement) {
            $requete->andWhere('l.id = :logement')->setParameter('logement', $logement);
        }
        if (null !== $categorie) {
            $requete->andWhere('c.id = :categorie')->setParameter('categorie', $categorie);
        }

        return $requete->getQuery()->getResult();
    }

    /** F-04 : nombre d'articles non personnels dont cette personne est l'auteur (mêmes règles que visiblesDe). */
    public function nombreVisiblesDe(Utilisateur $auteur): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.auteur = :auteur')
            ->andWhere('a.estPersonnel = false')
            ->setParameter('auteur', $auteur)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Une photo ne sert qu'à un seul article. */
    public function photoDejaUtilisee(string $photo): bool
    {
        return null !== $this->findOneBy(['photo' => $photo]);
    }

    /** Article, emplacement et catégorie chargés en une requête ; 30 par page. */
    private function requetePaginee(int $page): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->addSelect('s', 'p', 'l', 'c')
            ->join('a.stockage', 's')
            ->join('s.piece', 'p')
            ->join('p.logement', 'l')
            ->join('a.categorie', 'c')
            ->orderBy('a.creeLe', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setFirstResult(($page - 1) * self::PAR_PAGE)
            ->setMaxResults(self::PAR_PAGE);
    }

    private static function contient(string $texte): string
    {
        return '%'.addcslashes($texte, '%_\\').'%';
    }
}
