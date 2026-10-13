<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Utilisateur;
use App\Exception\RegleMetierException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UtilisateurTest extends TestCase
{
    private \DateTimeImmutable $aujourdhui;

    protected function setUp(): void
    {
        $this->aujourdhui = new \DateTimeImmutable('2026-10-13');
    }

    #[Test]
    public function un_majeur_cree_son_compte(): void
    {
        $camille = Utilisateur::creerMajeur('camille', 'Camille@Exemple.fr', new \DateTimeImmutable('1995-04-02'), false, $this->aujourdhui);

        self::assertFalse($camille->estMineur($this->aujourdhui));
        self::assertNull($camille->getReferent());
        self::assertSame('camille@exemple.fr', $camille->getEmail());
    }

    #[Test]
    public function i01_un_mineur_ne_cree_pas_seul_son_compte(): void
    {
        $this->expectException(RegleMetierException::class);
        $this->expectExceptionMessage('I-01');

        Utilisateur::creerMajeur('jules', 'jules@exemple.fr', new \DateTimeImmutable('2011-01-01'), true, $this->aujourdhui);
    }

    #[Test]
    public function i01_un_compte_mineur_a_un_referent_majeur(): void
    {
        $camille = $this->camille();
        $jules = Utilisateur::creerMineur($camille, 'jules', 'jules@exemple.fr', new \DateTimeImmutable('2011-01-01'), $this->aujourdhui);

        self::assertTrue($jules->estMineur($this->aujourdhui));
        self::assertSame($camille, $jules->getReferent());
    }

    #[Test]
    public function i01_un_mineur_ne_peut_pas_etre_referent(): void
    {
        $jules = Utilisateur::creerMineur($this->camille(), 'jules', 'jules@exemple.fr', new \DateTimeImmutable('2011-01-01'), $this->aujourdhui);

        $this->expectExceptionMessage('I-01');
        Utilisateur::creerMineur($jules, 'lou', 'lou@exemple.fr', new \DateTimeImmutable('2015-01-01'), $this->aujourdhui);
    }

    #[Test]
    public function i21_un_compte_mineur_est_cree_prive_et_le_reste(): void
    {
        $jules = Utilisateur::creerMineur($this->camille(), 'jules', 'jules@exemple.fr', new \DateTimeImmutable('2011-01-01'), $this->aujourdhui);
        self::assertTrue($jules->estPrive());

        $this->expectExceptionMessage('I-21');
        $jules->definirPrive(false, $this->aujourdhui);
    }

    #[Test]
    public function i02_a_18_ans_le_compte_devient_independant_et_peut_devenir_public(): void
    {
        $jules = Utilisateur::creerMineur($this->camille(), 'jules', 'jules@exemple.fr', new \DateTimeImmutable('2008-10-14'), $this->aujourdhui);

        self::assertFalse($jules->devenirIndependantSiMajeur($this->aujourdhui), 'La veille de ses 18 ans, rien ne change.');
        self::assertNotNull($jules->getReferent());

        $anniversaire = new \DateTimeImmutable('2026-10-14');
        self::assertTrue($jules->devenirIndependantSiMajeur($anniversaire));
        self::assertNull($jules->getReferent());

        $jules->definirPrive(false, $anniversaire);
        self::assertFalse($jules->estPrive());
    }

    private function camille(): Utilisateur
    {
        return Utilisateur::creerMajeur('camille', 'camille@exemple.fr', new \DateTimeImmutable('1995-04-02'), false, $this->aujourdhui);
    }
}
