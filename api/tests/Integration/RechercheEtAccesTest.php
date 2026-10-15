<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Article;
use App\Entity\Logement;
use App\Entity\Utilisateur;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Security\AccesLogement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests d'intégration : Symfony démarré, vraie base de test, sans requête HTTP.
 * Ils vérifient ce qu'une requête HTTP ne permet pas d'atteindre, ou ce qui demande beaucoup de données.
 */
final class RechercheEtAccesTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    #[Test]
    public function f26_la_recherche_est_paginee_par_30_du_plus_recent_au_plus_ancien(): void
    {
        $camille = Utilisateur::creerMajeur('camille', 'camille@exemple.fr', new \DateTimeImmutable('-30 years'), false, new \DateTimeImmutable());
        $camille->definirMotDePasseHache('inutile-ici');
        $logement = Logement::creer('Appartement Lyon', $camille, new \DateTimeImmutable());
        $armoire = $logement->ajouterPiece('Chambre')->ajouterStockage('Grande armoire');
        $pull = static::getContainer()->get(CategorieRepository::class)->nommee('Pull');
        self::assertNotNull($pull);
        $this->em->persist($camille);
        $this->em->persist($logement);
        for ($jour = 1; $jour <= 31; ++$jour) {
            $this->em->persist(Article::creer($camille, $armoire, $pull, \sprintf('photos/%d.jpg', $jour), new \DateTimeImmutable(\sprintf('2026-01-%02d', $jour))));
        }
        $this->em->flush();
        $articles = static::getContainer()->get(ArticleRepository::class);

        $page1 = $articles->rechercher($camille);
        $page2 = $articles->rechercher($camille, page: 2);

        self::assertCount(ArticleRepository::PAR_PAGE, $page1);
        self::assertSame('2026-01-31', $page1[0]->getCreeLe()->format('Y-m-d'), 'Le plus récent d\'abord.');
        self::assertCount(1, $page2);
        self::assertSame('2026-01-01', $page2[0]->getCreeLe()->format('Y-m-d'));
    }

    #[Test]
    public function s01_un_identifiant_non_numerique_est_introuvable_sans_interroger_la_base(): void
    {
        $acces = static::getContainer()->get(AccesLogement::class);

        foreach (['logement', 'piece', 'stockage'] as $ressource) {
            try {
                $acces->{$ressource}('1 OR 1=1');
                self::fail($ressource.' : un identifiant trafiqué doit être refusé.');
            } catch (NotFoundHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
