<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\Logement;
use App\Entity\Stockage;
use App\Entity\Utilisateur;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArticleTest extends TestCase
{
    private \DateTimeImmutable $aujourdhui;
    private Utilisateur $camille;
    private Utilisateur $nina;
    private Stockage $armoire;
    private Categorie $pull;

    protected function setUp(): void
    {
        $this->aujourdhui = new \DateTimeImmutable('2026-10-13');
        $this->camille = Utilisateur::creerMajeur('camille', 'camille@exemple.fr', new \DateTimeImmutable('1995-04-02'), false, $this->aujourdhui);
        $this->nina = Utilisateur::creerMajeur('nina', 'nina@exemple.fr', new \DateTimeImmutable('1990-01-20'), true, $this->aujourdhui);
        $this->armoire = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui)
            ->ajouterPiece('Chambre')
            ->ajouterStockage('Grande armoire');
        $this->pull = new Categorie('Pull');
    }

    #[Test]
    public function i10_un_article_cree_est_range_dans_son_stockage(): void
    {
        $article = Article::creer($this->camille, $this->armoire, $this->pull, 'photos/1.jpg', $this->aujourdhui);

        self::assertSame($this->armoire, $article->getStockage());
        self::assertTrue($this->armoire->getArticles()->contains($article));
        self::assertFalse($article->estPersonnel(), 'Un article n\'est pas personnel par défaut.');
    }

    #[Test]
    public function i11_on_ne_range_pas_un_article_chez_les_autres(): void
    {
        $this->expectExceptionMessage('I-11');
        Article::creer($this->nina, $this->armoire, $this->pull, 'photos/1.jpg', $this->aujourdhui);
    }

    #[Test]
    public function i11_on_ne_deplace_pas_un_article_dans_un_logement_dont_on_n_est_pas_membre(): void
    {
        $article = Article::creer($this->camille, $this->armoire, $this->pull, 'photos/1.jpg', $this->aujourdhui);
        $commodeDeNina = Logement::creer('Maison Annecy', $this->nina, $this->aujourdhui)
            ->ajouterPiece('Chambre')
            ->ajouterStockage('Commode');

        $this->expectExceptionMessage('I-11');
        $article->deplacerVers($commodeDeNina);
    }

    #[Test]
    public function deplacer_un_article_le_retire_de_son_ancien_stockage(): void
    {
        $article = Article::creer($this->camille, $this->armoire, $this->pull, 'photos/1.jpg', $this->aujourdhui);
        $penderie = $this->armoire->getLogement()->ajouterPiece('Entrée')->ajouterStockage('Penderie');

        $article->deplacerVers($penderie);

        self::assertFalse($this->armoire->getArticles()->contains($article));
        self::assertTrue($penderie->getArticles()->contains($article));
    }

    #[Test]
    public function les_champs_vides_sont_enregistres_comme_absents(): void
    {
        $article = Article::creer($this->camille, $this->armoire, $this->pull, 'photos/1.jpg', $this->aujourdhui);

        $article->decrire($this->pull, '  Maison Lune ', '', null);

        self::assertSame('Maison Lune', $article->getMarque());
        self::assertNull($article->getCouleur());
    }

    #[Test]
    public function deplacer_un_article_dans_son_propre_rangement_ne_change_rien(): void
    {
        $article = Article::creer($this->camille, $this->armoire, $this->pull, 'photos/1.jpg', $this->aujourdhui);

        $article->deplacerVers($this->armoire);

        self::assertSame($this->armoire, $article->getStockage());
        self::assertCount(1, $this->armoire->getArticles(), 'L\'article n\'est pas rangé deux fois.');
    }
}
