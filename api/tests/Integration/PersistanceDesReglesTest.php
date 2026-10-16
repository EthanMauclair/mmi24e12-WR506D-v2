<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Article;
use App\Entity\Logement;
use App\Entity\Membre;
use App\Entity\Piece;
use App\Entity\Relation;
use App\Entity\Stockage;
use App\Entity\Utilisateur;
use App\Enum\RoleMembre;
use App\Enum\StatutMembre;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Les règles métier qui ne tiennent que grâce à la base : clés étrangères en cascade,
 * suppression des orphelins par Doctrine, contraintes d'unicité.
 * Les tests unitaires vérifient la décision prise par l'entité ; ceux-ci vérifient qu'elle est
 * bien écrite en base. Après chaque action, relire() vide la mémoire de Doctrine : les assertions
 * portent sur ce que contient vraiment la base de test.
 */
final class PersistanceDesReglesTest extends IntegrationTestCase
{
    #[Test]
    public function i03_supprimer_un_referent_supprime_ses_comptes_mineurs_avec_leurs_articles(): void
    {
        $sophie = $this->majeur('sophie');
        $camille = $this->majeur('camille');
        $jules = $this->mineur($camille, 'jules');
        $logement = $this->logement($sophie);
        $this->rejoindre($logement, $camille);
        $this->rejoindre($logement, $jules);
        $articleDeJules = $this->article($jules, $this->armoire($logement))->getId();
        $articleDeSophie = $this->article($sophie, $this->armoire($logement))->getId();
        $ids = ['camille' => $camille->getId(), 'jules' => $jules->getId(), 'logement' => $logement->getId()];

        $this->em->remove($camille);
        $this->em->flush();
        $this->relire();

        self::assertNull($this->em->find(Utilisateur::class, $ids['jules']), 'Le compte mineur disparaît avec son référent.');
        self::assertNull($this->em->find(Article::class, $articleDeJules), 'Ses articles aussi.');
        self::assertNotNull($this->em->find(Article::class, $articleDeSophie), 'Les articles des autres restent.');
        $logement = $this->em->find(Logement::class, $ids['logement']);
        self::assertNotNull($logement);
        self::assertCount(1, $logement->getMembres(), 'Il ne reste que la propriétaire.');
    }

    #[Test]
    public function i09_le_co_proprietaire_majeur_arrive_en_premier_devient_proprietaire_et_les_articles_de_l_ancien_disparaissent(): void
    {
        $camille = $this->majeur('camille');
        $leo = $this->majeur('leo');
        $nora = $this->majeur('nora');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $nora, new \DateTimeImmutable('2026-03-01'));
        $this->rejoindre($logement, $leo, new \DateTimeImmutable('2026-01-01'));
        $armoire = $this->armoire($logement);
        $articleDeCamille = $this->article($camille, $armoire)->getId();
        $articleDeLeo = $this->article($leo, $armoire)->getId();
        $id = $logement->getId();

        self::assertSame($leo, $logement->retirerProprietaire(new \DateTimeImmutable()));
        $this->em->flush();
        $this->relire();

        $logement = $this->em->find(Logement::class, $id);
        self::assertNotNull($logement);
        self::assertSame('leo', $logement->getProprietaire()->getPseudo());
        self::assertCount(2, $logement->getMembres(), 'Camille n\'est plus membre.');
        self::assertNull($this->em->find(Article::class, $articleDeCamille), 'Les articles de l\'ancienne propriétaire sont supprimés (D-011).');
        self::assertNotNull($this->em->find(Article::class, $articleDeLeo));
    }

    #[Test]
    public function i09_sans_co_proprietaire_majeur_le_logement_est_supprime_entierement(): void
    {
        $camille = $this->majeur('camille');
        $jules = $this->mineur($camille, 'jules');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $jules);
        $armoire = $this->armoire($logement);
        $article = $this->article($jules, $armoire)->getId();
        $ids = [$logement->getId(), $armoire->getPiece()->getId(), $armoire->getId()];

        self::assertNull($logement->retirerProprietaire(new \DateTimeImmutable()), 'Un mineur ne devient jamais propriétaire (I-06).');
        $this->em->remove($logement);
        $this->em->flush();
        $this->relire();

        self::assertNull($this->em->find(Logement::class, $ids[0]));
        self::assertNull($this->em->find(Piece::class, $ids[1]), 'Les pièces partent avec le logement.');
        self::assertNull($this->em->find(Stockage::class, $ids[2]), 'Les rangements aussi.');
        self::assertNull($this->em->find(Article::class, $article), 'Et les articles qu\'ils contenaient.');
        self::assertNotNull($this->em->find(Utilisateur::class, $jules->getId()), 'Les comptes, eux, restent.');
    }

    #[Test]
    public function a17_quitter_un_logement_supprime_ses_articles_et_seulement_les_siens(): void
    {
        $camille = $this->majeur('camille');
        $leo = $this->majeur('leo');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $leo);
        $armoire = $this->armoire($logement);
        $articleDeLeo = $this->article($leo, $armoire)->getId();
        $articleDeCamille = $this->article($camille, $armoire)->getId();
        $membreLeo = $logement->membreDe($leo);
        self::assertNotNull($membreLeo);
        $idMembre = $membreLeo->getId();

        $logement->retirerMembre($membreLeo, $leo);
        $this->em->flush();
        $this->relire();

        self::assertNull($this->em->find(Membre::class, $idMembre));
        self::assertNull($this->em->find(Article::class, $articleDeLeo), 'H19 : ses articles quittent le logement avec lui.');
        self::assertNotNull($this->em->find(Article::class, $articleDeCamille));
    }

    #[Test]
    public function i10_une_piece_et_un_rangement_vides_sont_effaces_de_la_base(): void
    {
        $camille = $this->majeur('camille');
        $logement = $this->logement($camille);
        $piece = $logement->ajouterPiece('Entrée');
        $placard = $piece->ajouterStockage('Placard');
        $tiroir = $piece->ajouterStockage('Tiroir');
        $this->em->flush();
        $ids = [$piece->getId(), $placard->getId(), $tiroir->getId()];

        $piece->retirerStockage($tiroir);
        $this->em->flush();
        self::assertNull($this->em->find(Stockage::class, $ids[2]), 'Rangement vide supprimé.');

        $logement->retirerPiece($piece);
        $this->em->flush();
        $this->relire();

        self::assertNull($this->em->find(Piece::class, $ids[0]));
        self::assertNull($this->em->find(Stockage::class, $ids[1]), 'Ses rangements partent avec elle.');
    }

    #[Test]
    public function i10_deplacer_un_article_le_range_ailleurs_sans_le_dupliquer(): void
    {
        $camille = $this->majeur('camille');
        $logement = $this->logement($camille);
        $armoire = $this->armoire($logement);
        $commode = $armoire->getPiece()->ajouterStockage('Commode');
        $this->em->flush();
        $article = $this->article($camille, $armoire);

        $article->deplacerVers($commode);
        $this->em->flush();
        $this->relire();

        $article = $this->em->find(Article::class, $article->getId());
        self::assertNotNull($article);
        self::assertSame('Commode', $article->getStockage()->getNom());
        $armoire = $this->em->find(Stockage::class, $armoire->getId());
        self::assertNotNull($armoire);
        self::assertCount(0, $armoire->getArticles());
    }

    #[Test]
    public function i07_la_base_refuse_un_second_membre_pour_la_meme_personne(): void
    {
        $camille = $this->majeur('camille');
        $logement = $this->logement($camille);
        $this->em->persist(new Membre($camille, $logement, RoleMembre::Coproprietaire, StatutMembre::Accepte, new \DateTimeImmutable()));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }

    #[Test]
    public function i17_la_base_refuse_une_seconde_relation_pour_le_meme_couple(): void
    {
        $sophie = $this->majeur('sophie');
        $camille = $this->majeur('camille');
        $this->em->persist(Relation::abonner($sophie, $camille, new \DateTimeImmutable()));
        $this->em->flush();
        $this->em->persist(Relation::abonner($sophie, $camille, new \DateTimeImmutable()));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }

    #[Test]
    public function f01_la_base_refuse_un_pseudo_deja_pris(): void
    {
        $this->majeur('camille');
        $homonyme = Utilisateur::creerMajeur('camille', 'autre@exemple.fr', new \DateTimeImmutable('-30 years'), false, new \DateTimeImmutable());
        $homonyme->definirMotDePasseHache('inutile-ici');
        $this->em->persist($homonyme);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->flush();
    }
}
