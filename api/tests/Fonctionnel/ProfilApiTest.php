<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Article;
use App\Entity\Logement;
use App\Entity\Relation;
use App\Entity\Stockage;
use App\Entity\Utilisateur;
use PHPUnit\Framework\Attributes\Test;

/**
 * Profil d'une personne (F-04) : ce qu'il montre, à qui, et des compteurs qui ne trahissent pas
 * ce que la liste des articles cache (A-03, S-05).
 */
final class ProfilApiTest extends ApiTestCase
{
    private Stockage $armoire;

    #[Test]
    public function f04_s05_un_profil_public_se_voit_sans_compte_sans_e_mail_ni_date(): void
    {
        $camille = $this->creerMajeur('camille');
        $this->ranger($camille, 2, personnels: 1);
        $this->suivre($this->creerMajeur('nora'), $camille);

        $this->json('GET', '/api/utilisateurs/camille');

        self::assertResponseIsSuccessful();
        $profil = $this->reponse();
        self::assertSame('camille', $profil['pseudo']);
        self::assertFalse($profil['estPrive']);
        self::assertSame(1, $profil['nombreAbonnes']);
        self::assertSame(2, $profil['nombreArticles'], 'Les articles personnels ne sont pas comptés (A-04).');
        self::assertArrayNotHasKey('email', $profil);
        self::assertArrayNotHasKey('dateNaissance', $profil);
        self::assertArrayNotHasKey('maRelation', $profil);
    }

    #[Test]
    public function a03_compte_prive_le_nombre_d_articles_est_cache_sauf_aux_abonnes_acceptes(): void
    {
        $camille = $this->creerMajeur('camille', estPrive: true);
        $this->ranger($camille, 3);
        $nora = $this->creerMajeur('nora');
        $leo = $this->creerMajeur('leo');
        $this->suivre($nora, $camille, accepter: true);
        $this->suivre($leo, $camille, accepter: false);

        $this->json('GET', '/api/utilisateurs/camille');
        self::assertArrayNotHasKey('nombreArticles', $this->reponse(), 'Visiteur sans compte.');

        $this->json('GET', '/api/utilisateurs/camille', jeton: $this->jetonDe($leo));
        $profil = $this->reponse();
        self::assertArrayNotHasKey('nombreArticles', $profil, 'Une demande en attente ne donne rien.');
        self::assertSame(1, $profil['nombreAbonnes'], 'Une demande en attente ne compte pas.');
        self::assertSame('EN_ATTENTE', $profil['maRelation']['statut']);

        $this->json('GET', '/api/utilisateurs/camille', jeton: $this->jetonDe($nora));
        $profil = $this->reponse();
        self::assertSame(3, $profil['nombreArticles']);
        self::assertSame(['id', 'suiveur', 'suivi', 'niveau', 'statut', 'creeLe'], array_keys($profil['maRelation']));
        self::assertSame('nora', $profil['maRelation']['suiveur']['pseudo']);
        self::assertSame('ACCEPTEE', $profil['maRelation']['statut']);
    }

    #[Test]
    public function f04_mon_propre_profil_prive_montre_mes_articles(): void
    {
        $camille = $this->creerMajeur('camille', estPrive: true);
        $this->ranger($camille, 2);

        $this->json('GET', '/api/utilisateurs/camille', jeton: $this->jetonDe($camille));

        self::assertSame(2, $this->reponse()['nombreArticles']);
        self::assertArrayNotHasKey('maRelation', $this->reponse());
    }

    #[Test]
    public function f04_un_pseudo_inconnu_repond_404(): void
    {
        $this->json('GET', '/api/utilisateurs/personne');

        self::assertResponseStatusCodeSame(404);
    }

    /** Crée un logement à cette personne et y range des articles, dont certains personnels. */
    private function ranger(Utilisateur $auteur, int $nombre, int $personnels = 0): void
    {
        $maintenant = new \DateTimeImmutable();
        $logement = Logement::creer('Appartement', $auteur, $maintenant);
        $this->armoire = $logement->ajouterPiece('Chambre')->ajouterStockage('Armoire');
        $this->em->persist($logement);

        $pull = $this->categorie('Pull');
        for ($i = 0; $i < $nombre + $personnels; ++$i) {
            $article = Article::creer($auteur, $this->armoire, $pull, \sprintf('photos/test-%d.jpg', $i), $maintenant);
            if ($i >= $nombre) {
                $article->marquerPersonnel(true);
            }
            $this->em->persist($article);
        }
        $this->em->flush();
    }

    private function suivre(Utilisateur $suiveur, Utilisateur $suivi, bool $accepter = true): void
    {
        $relation = Relation::abonner($suiveur, $suivi, new \DateTimeImmutable());
        if ($accepter && $suivi->estPrive()) {
            $relation->accepter();
        }
        $this->em->persist($relation);
        $this->em->flush();
    }
}
