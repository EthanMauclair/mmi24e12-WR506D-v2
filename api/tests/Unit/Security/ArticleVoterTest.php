<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\Logement;
use App\Entity\Utilisateur;
use App\Repository\RelationRepository;
use App\Security\ArticleVoter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Le voter des articles, sans base de données : les relations acceptées sont simulées.
 * Règles : A-01, A-03, A-04, A-10, A-11, A-16 (docs/06-modele-autorisation.md).
 */
final class ArticleVoterTest extends TestCase
{
    private \DateTimeImmutable $aujourdhui;
    private Utilisateur $camille;
    private Utilisateur $jules;
    private Utilisateur $nora;
    private Utilisateur $sophie;
    private Logement $logement;
    /** @var list<array{Utilisateur, Utilisateur}> couples (suiveur, suivi) dont la relation est acceptée */
    private array $relationsAcceptees = [];

    protected function setUp(): void
    {
        $this->aujourdhui = new \DateTimeImmutable('2026-10-15');
        $this->camille = Utilisateur::creerMajeur('camille', 'camille@exemple.fr', new \DateTimeImmutable('1995-04-02'), false, $this->aujourdhui);
        $this->jules = Utilisateur::creerMineur($this->camille, 'jules', 'jules@exemple.fr', new \DateTimeImmutable('2011-03-05'), $this->aujourdhui);
        $this->nora = Utilisateur::creerMajeur('nora', 'nora@exemple.fr', new \DateTimeImmutable('1998-01-01'), true, $this->aujourdhui);
        $this->sophie = Utilisateur::creerMajeur('sophie', 'sophie@exemple.fr', new \DateTimeImmutable('1992-01-01'), false, $this->aujourdhui);
        $this->logement = Logement::creer('Appartement Lyon', $this->camille, $this->aujourdhui);
        $this->logement->inviter($this->jules, $this->camille, $this->aujourdhui)->accepter($this->aujourdhui);
        $this->logement->inviter($this->nora, $this->camille, $this->aujourdhui)->accepter($this->aujourdhui);
    }

    #[Test]
    public function a01_un_membre_voit_tous_les_articles_du_logement_meme_personnels(): void
    {
        $article = $this->article($this->nora, estPersonnel: true);

        self::assertTrue($this->peut($this->camille, ArticleVoter::VOIR, $article));
        self::assertTrue($this->peut($this->jules, ArticleVoter::VOIR, $article));
    }

    #[Test]
    public function a03_un_visiteur_voit_un_article_d_un_compte_public_meme_sans_compte(): void
    {
        $article = $this->article($this->camille);

        self::assertTrue($this->peut(null, ArticleVoter::VOIR, $article));
        self::assertTrue($this->peut($this->sophie, ArticleVoter::VOIR, $article));
    }

    #[Test]
    public function a03_un_compte_prive_n_est_vu_que_par_une_relation_acceptee(): void
    {
        $article = $this->article($this->nora);

        self::assertFalse($this->peut(null, ArticleVoter::VOIR, $article));
        self::assertFalse($this->peut($this->sophie, ArticleVoter::VOIR, $article));

        $this->relationsAcceptees[] = [$this->sophie, $this->nora];
        self::assertTrue($this->peut($this->sophie, ArticleVoter::VOIR, $article));
    }

    #[Test]
    public function d013_suivre_le_proprietaire_du_logement_ne_donne_pas_acces_aux_articles_des_autres(): void
    {
        $article = $this->article($this->nora);
        $this->relationsAcceptees[] = [$this->sophie, $this->camille];

        self::assertFalse($this->peut($this->sophie, ArticleVoter::VOIR, $article), 'C\'est l\'auteur qui compte, pas le propriétaire.');
    }

    #[Test]
    public function a04_un_article_personnel_n_est_jamais_vu_hors_du_logement(): void
    {
        $article = $this->article($this->camille, estPersonnel: true);
        $this->relationsAcceptees[] = [$this->sophie, $this->camille];

        self::assertFalse($this->peut(null, ArticleVoter::VOIR, $article));
        self::assertFalse($this->peut($this->sophie, ArticleVoter::VOIR, $article), 'Même pour une abonnée acceptée.');
    }

    #[Test]
    public function a16_un_article_de_mineur_n_est_vu_hors_du_logement_que_par_une_relation_acceptee(): void
    {
        $article = $this->article($this->jules);

        self::assertFalse($this->peut($this->sophie, ArticleVoter::VOIR, $article), 'Un compte mineur est privé (I-21).');

        $this->relationsAcceptees[] = [$this->sophie, $this->jules];
        self::assertTrue($this->peut($this->sophie, ArticleVoter::VOIR, $article));
    }

    #[Test]
    public function a10_a11_un_majeur_modifie_tout_un_mineur_seulement_les_siens_un_visiteur_rien(): void
    {
        $articleDeCamille = $this->article($this->camille);
        $articleDeJules = $this->article($this->jules);

        self::assertTrue($this->peut($this->nora, ArticleVoter::MODIFIER, $articleDeCamille), 'A-10 : tout membre majeur.');
        self::assertTrue($this->peut($this->camille, ArticleVoter::MODIFIER, $articleDeJules));
        self::assertTrue($this->peut($this->jules, ArticleVoter::MODIFIER, $articleDeJules), 'A-11 : un mineur modifie les siens.');
        self::assertFalse($this->peut($this->jules, ArticleVoter::MODIFIER, $articleDeCamille), 'S-06 : pas ceux des autres.');
        self::assertFalse($this->peut($this->sophie, ArticleVoter::MODIFIER, $articleDeCamille), 'S-02 : un visiteur ne modifie rien.');
        self::assertFalse($this->peut(null, ArticleVoter::MODIFIER, $articleDeCamille));
    }

    #[Test]
    public function le_voter_ne_se_prononce_pas_sur_autre_chose_qu_un_article(): void
    {
        $voter = new ArticleVoter(new MockClock($this->aujourdhui), $this->createStub(RelationRepository::class));

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(new NullToken(), $this->logement, [ArticleVoter::VOIR]));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(new NullToken(), $this->article($this->camille), ['AUTRE_DROIT']));
    }

    private function article(Utilisateur $auteur, bool $estPersonnel = false): Article
    {
        $stockage = $this->logement->getPieces()->first() ?: $this->logement->ajouterPiece('Chambre');
        $armoire = $stockage->getStockages()->first() ?: $stockage->ajouterStockage('Armoire');
        $article = Article::creer($auteur, $armoire, new Categorie('Pull'), 'photos/1.jpg', $this->aujourdhui);
        $article->marquerPersonnel($estPersonnel);

        return $article;
    }

    private function peut(?Utilisateur $qui, string $droit, Article $article): bool
    {
        $relations = $this->createStub(RelationRepository::class);
        $relations->method('estAcceptee')->willReturnCallback(
            fn (Utilisateur $suiveur, Utilisateur $suivi): bool => \in_array([$suiveur, $suivi], $this->relationsAcceptees, true),
        );
        $voter = new ArticleVoter(new MockClock($this->aujourdhui), $relations);
        $jeton = null === $qui ? new NullToken() : new UsernamePasswordToken($qui, 'api', $qui->getRoles());

        return VoterInterface::ACCESS_GRANTED === $voter->vote($jeton, $article, [$droit]);
    }
}
