<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Relation;
use App\Entity\Utilisateur;
use App\Enum\NiveauRelation;
use App\Enum\StatutRelation;
use App\Exception\RegleMetierException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RelationTest extends TestCase
{
    private \DateTimeImmutable $aujourdhui;
    private Utilisateur $camille;
    private Utilisateur $nora;

    protected function setUp(): void
    {
        $this->aujourdhui = new \DateTimeImmutable('2026-10-14');
        $this->camille = Utilisateur::creerMajeur('camille', 'camille@exemple.fr', new \DateTimeImmutable('1995-04-02'), false, $this->aujourdhui);
        $this->nora = Utilisateur::creerMajeur('nora', 'nora@exemple.fr', new \DateTimeImmutable('1998-06-20'), false, $this->aujourdhui);
    }

    #[Test]
    public function i18_un_abonnement_a_un_compte_public_est_accepte_d_office(): void
    {
        $relation = Relation::abonner($this->nora, $this->camille, $this->aujourdhui);

        self::assertSame(NiveauRelation::Abonne, $relation->getNiveau());
        self::assertTrue($relation->estAcceptee());
    }

    #[Test]
    public function i18_un_abonnement_a_un_compte_prive_attend_l_acceptation(): void
    {
        $this->camille->definirPrive(true, $this->aujourdhui);

        $relation = Relation::abonner($this->nora, $this->camille, $this->aujourdhui);

        self::assertSame(StatutRelation::EnAttente, $relation->getStatut());
        $relation->accepter();
        self::assertTrue($relation->estAcceptee());
    }

    #[Test]
    public function a16_un_abonne_d_un_compte_mineur_doit_etre_accepte(): void
    {
        $jules = Utilisateur::creerMineur($this->camille, 'jules', 'jules@exemple.fr', new \DateTimeImmutable('2014-01-01'), $this->aujourdhui);

        self::assertFalse(Relation::abonner($this->nora, $jules, $this->aujourdhui)->estAcceptee());
    }

    #[Test]
    public function i17_on_ne_se_suit_pas_soi_meme(): void
    {
        $this->expectException(RegleMetierException::class);
        $this->expectExceptionMessage('I-17');

        Relation::abonner($this->nora, $this->nora, $this->aujourdhui);
    }

    #[Test]
    public function f17_l_ami_ajoute_par_le_proprietaire_du_compte_est_accepte(): void
    {
        $this->camille->definirPrive(true, $this->aujourdhui);

        $relation = Relation::ajouterAmi($this->camille, $this->nora, $this->aujourdhui);

        self::assertSame($this->nora, $relation->getSuiveur());
        self::assertSame($this->camille, $relation->getSuivi());
        self::assertTrue($relation->estAmi());
        self::assertTrue($relation->estAcceptee());
    }

    #[Test]
    public function f17_une_demande_en_attente_devient_amitie_acceptee(): void
    {
        $this->camille->definirPrive(true, $this->aujourdhui);
        $relation = Relation::abonner($this->nora, $this->camille, $this->aujourdhui);

        $relation->devenirAmi();

        self::assertTrue($relation->estAmi());
        self::assertTrue($relation->estAcceptee());

        $this->expectException(RegleMetierException::class);
        $this->expectExceptionMessage('I-17');
        $relation->devenirAmi();
    }

    #[Test]
    public function f18_un_ami_retrograde_reste_un_abonne_accepte(): void
    {
        $relation = Relation::ajouterAmi($this->camille, $this->nora, $this->aujourdhui);

        $relation->retrograder();

        self::assertSame(NiveauRelation::Abonne, $relation->getNiveau());
        self::assertTrue($relation->estAcceptee());
    }
}
