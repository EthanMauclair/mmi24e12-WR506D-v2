<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\Logement;
use App\Entity\Utilisateur;
use App\Enum\RoleMembre;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogementTest extends TestCase
{
    private \DateTimeImmutable $aujourdhui;
    private Utilisateur $camille;
    private Utilisateur $leo;
    private Utilisateur $nina;
    private Utilisateur $jules;

    protected function setUp(): void
    {
        $this->aujourdhui = new \DateTimeImmutable('2026-10-13');
        $this->camille = Utilisateur::creerMajeur('camille', 'camille@exemple.fr', new \DateTimeImmutable('1995-04-02'), false, $this->aujourdhui);
        $this->leo = Utilisateur::creerMajeur('leo', 'leo@exemple.fr', new \DateTimeImmutable('1994-06-10'), false, $this->aujourdhui);
        $this->nina = Utilisateur::creerMajeur('nina', 'nina@exemple.fr', new \DateTimeImmutable('1990-01-20'), true, $this->aujourdhui);
        $this->jules = Utilisateur::creerMineur($this->camille, 'jules', 'jules@exemple.fr', new \DateTimeImmutable('2011-03-05'), $this->aujourdhui);
    }

    #[Test]
    public function i05_le_createur_est_l_unique_proprietaire(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);

        self::assertSame($this->camille, $logement->getProprietaire());
        self::assertCount(1, $logement->getMembres());
        self::assertTrue($logement->estMembreAccepte($this->camille));
    }

    #[Test]
    public function i08_un_mineur_ne_cree_pas_de_logement(): void
    {
        $this->expectExceptionMessage('I-08');
        Logement::creer('Chambre de Jules', $this->jules, $this->aujourdhui);
    }

    #[Test]
    public function un_invite_n_a_aucun_droit_tant_qu_il_n_a_pas_accepte(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $membre = $logement->inviter($this->leo, $this->camille, $this->aujourdhui);

        self::assertFalse($logement->estMembreAccepte($this->leo));
        self::assertSame(RoleMembre::Coproprietaire, $membre->getRole());

        $membre->accepter($this->aujourdhui);
        self::assertTrue($logement->estMembreAccepte($this->leo));
    }

    #[Test]
    public function i07_on_n_invite_pas_deux_fois_la_meme_personne(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $logement->inviter($this->leo, $this->camille, $this->aujourdhui);

        $this->expectExceptionMessage('I-07');
        $logement->inviter($this->leo, $this->camille, $this->aujourdhui);
    }

    #[Test]
    public function a10_un_non_membre_n_invite_personne(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);

        $this->expectExceptionMessage('A-10');
        $logement->inviter($this->nina, $this->leo, $this->aujourdhui);
    }

    #[Test]
    public function h18_un_mineur_ne_rejoint_que_le_logement_de_son_referent(): void
    {
        $logementDeCamille = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $logementDeCamille->inviter($this->jules, $this->camille, $this->aujourdhui)->accepter($this->aujourdhui);
        self::assertTrue($logementDeCamille->estMembreAccepte($this->jules));

        $logementDeNina = Logement::creer('Maison Annecy', $this->nina, $this->aujourdhui);
        $this->expectExceptionMessage('H18');
        $logementDeNina->inviter($this->jules, $this->nina, $this->aujourdhui);
    }

    #[Test]
    public function i09_sans_coproprietaire_majeur_le_logement_doit_etre_supprime(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $logement->inviter($this->jules, $this->camille, $this->aujourdhui)->accepter($this->aujourdhui);
        $logement->inviter($this->leo, $this->camille, $this->aujourdhui); // invitation jamais acceptée

        self::assertNull($logement->retirerProprietaire($this->aujourdhui), 'Ni un mineur (I-06) ni un invité ne reprennent le logement.');
        self::assertSame($this->camille, $logement->getProprietaire(), 'Rien n\'est modifié : c\'est à l\'appelant de supprimer le logement.');
    }

    #[Test]
    public function i09_le_coproprietaire_majeur_arrive_en_premier_reprend_le_logement(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $logement->inviter($this->nina, $this->camille, $this->aujourdhui)->accepter(new \DateTimeImmutable('2026-10-20'));
        $logement->inviter($this->leo, $this->camille, $this->aujourdhui)->accepter(new \DateTimeImmutable('2026-10-15'));

        $nouveau = $logement->retirerProprietaire(new \DateTimeImmutable('2026-11-01'));

        self::assertSame($this->leo, $nouveau, 'Léo a accepté avant Nina.');
        self::assertSame($this->leo, $logement->getProprietaire());
        self::assertNull($logement->membreDe($this->camille));
        self::assertCount(1, array_filter($logement->getMembres()->toArray(), fn ($m) => $m->estProprietaire()), 'I-05 : un seul propriétaire.');
    }

    #[Test]
    public function d011_seuls_les_articles_du_proprietaire_sont_supprimes(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $logement->inviter($this->leo, $this->camille, $this->aujourdhui)->accepter($this->aujourdhui);
        $armoire = $logement->ajouterPiece('Chambre')->ajouterStockage('Grande armoire');
        $pull = new Categorie('Pull');

        Article::creer($this->camille, $armoire, $pull, 'photos/1.jpg', $this->aujourdhui);
        $articleDeLeo = Article::creer($this->leo, $armoire, $pull, 'photos/2.jpg', $this->aujourdhui);

        $logement->retirerProprietaire($this->aujourdhui);

        self::assertSame([$articleDeLeo], array_values($armoire->getArticles()->toArray()));
        self::assertCount(1, $logement->getPieces(), 'Les pièces et les stockages restent.');
    }

    #[Test]
    public function a17_retirer_un_coproprietaire_supprime_ses_articles(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $membreLeo = $logement->inviter($this->leo, $this->camille, $this->aujourdhui);
        $membreLeo->accepter($this->aujourdhui);
        $armoire = $logement->ajouterPiece('Chambre')->ajouterStockage('Grande armoire');
        $pull = new Categorie('Pull');
        $articleDeCamille = Article::creer($this->camille, $armoire, $pull, 'photos/1.jpg', $this->aujourdhui);
        Article::creer($this->leo, $armoire, $pull, 'photos/2.jpg', $this->aujourdhui);

        $logement->retirerMembre($membreLeo, $this->camille);

        self::assertNull($logement->membreDe($this->leo));
        self::assertSame([$articleDeCamille], array_values($armoire->getArticles()->toArray()));
    }

    #[Test]
    public function a17_un_coproprietaire_ne_retire_pas_un_autre_coproprietaire(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $logement->inviter($this->leo, $this->camille, $this->aujourdhui)->accepter($this->aujourdhui);
        $membreNina = $logement->inviter($this->nina, $this->camille, $this->aujourdhui);
        $membreNina->accepter($this->aujourdhui);

        $this->expectExceptionMessage('A-17');
        $logement->retirerMembre($membreNina, $this->leo);
    }

    #[Test]
    public function on_ne_retire_pas_d_un_logement_le_membre_d_un_autre_logement(): void
    {
        $appartement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $studio = Logement::creer('Studio', $this->nina, $this->aujourdhui);
        $membreDuStudio = $studio->inviter($this->leo, $this->nina, $this->aujourdhui);

        $this->expectException(\InvalidArgumentException::class);
        $appartement->retirerMembre($membreDuStudio, $this->camille);
    }

    #[Test]
    public function i10_une_piece_ou_un_rangement_ne_se_supprime_que_vide(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $chambre = $logement->ajouterPiece('Chambre');
        $armoire = $chambre->ajouterStockage('Grande armoire');
        $commode = $chambre->ajouterStockage('Commode');
        $entree = $logement->ajouterPiece('Entrée');
        Article::creer($this->camille, $armoire, new Categorie('Pull'), 'photos/1.jpg', $this->aujourdhui);

        $chambre->retirerStockage($commode);
        $logement->retirerPiece($entree);
        self::assertSame([$armoire], $chambre->getStockages()->getValues(), 'Un rangement vide se supprime.');
        self::assertSame([$chambre], $logement->getPieces()->getValues(), 'Une pièce vide se supprime.');

        $this->expectException(RegleMetierException::class);
        $this->expectExceptionMessage('I-10');
        $chambre->retirerStockage($armoire);
    }

    #[Test]
    public function i10_une_piece_dont_un_rangement_contient_un_article_ne_se_supprime_pas(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $chambre = $logement->ajouterPiece('Chambre');
        Article::creer($this->camille, $chambre->ajouterStockage('Armoire'), new Categorie('Pull'), 'photos/1.jpg', $this->aujourdhui);

        try {
            $logement->retirerPiece($chambre);
            self::fail('I-10 : une pièce non vide ne se supprime pas.');
        } catch (RegleMetierException $e) {
            self::assertSame('I-10', $e->invariant);
        }
        self::assertSame([$chambre], $logement->getPieces()->getValues(), 'La pièce est toujours là.');
    }

    #[Test]
    public function i06_un_mineur_arrive_en_premier_ne_devient_jamais_proprietaire(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $logement->inviter($this->jules, $this->camille, $this->aujourdhui)->accepter(new \DateTimeImmutable('2026-01-01'));
        $logement->inviter($this->leo, $this->camille, $this->aujourdhui)->accepter(new \DateTimeImmutable('2026-06-01'));

        self::assertSame($this->leo, $logement->retirerProprietaire($this->aujourdhui), 'Le plus ancien majeur, pas le plus ancien tout court.');
        self::assertSame($this->leo, $logement->getProprietaire());
        self::assertSame(RoleMembre::Coproprietaire, $logement->membreDe($this->jules)?->getRole());
    }

    #[Test]
    public function a15_le_proprietaire_ne_quitte_son_logement_qu_en_le_supprimant(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $proprietaire = $logement->membreDe($this->camille);
        self::assertNotNull($proprietaire);

        $this->expectExceptionMessage('A-15');
        $logement->retirerMembre($proprietaire, $this->camille);
    }

    #[Test]
    public function f05_on_renomme_le_logement(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);

        $logement->renommer('Appartement Paris');

        self::assertSame('Appartement Paris', $logement->getNom());
    }

    #[Test]
    public function f06_on_renomme_une_piece_et_un_rangement(): void
    {
        $chambre = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui)->ajouterPiece('Chambre');
        $armoire = $chambre->ajouterStockage('Armoire');

        $chambre->renommer('Chambre de Jules');
        $armoire->renommer('Grande armoire');

        self::assertSame('Chambre de Jules', $chambre->getNom());
        self::assertSame('Grande armoire', $armoire->getNom());
    }

    #[Test]
    public function la_date_de_creation_du_logement_est_conservee(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);

        self::assertEquals($this->aujourdhui, $logement->getCreeLe());
    }

    #[Test]
    public function accepter_deux_fois_une_invitation_garde_la_date_d_arrivee(): void
    {
        $logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $invitation = $logement->inviter($this->leo, $this->camille, $this->aujourdhui);
        $invitation->accepter($this->aujourdhui);

        $invitation->accepter(new \DateTimeImmutable('2026-12-01'));

        self::assertEquals($this->aujourdhui, $invitation->getArriveLe(), 'L\'ancienneté sert à désigner le prochain propriétaire (I-09).');
    }
}
