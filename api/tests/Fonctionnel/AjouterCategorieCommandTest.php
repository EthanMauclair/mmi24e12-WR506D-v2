<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Repository\CategorieRepository;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AjouterCategorieCommandTest extends KernelTestCase
{
    private CommandTester $commande;

    protected function setUp(): void
    {
        $application = new Application(self::bootKernel());
        $this->commande = new CommandTester($application->find('app:categorie:ajouter'));
    }

    #[Test]
    public function l_administrateur_ajoute_une_categorie(): void
    {
        $this->commande->execute(['nom' => '  casquette ']);

        $this->commande->assertCommandIsSuccessful();
        self::assertNotNull(self::getContainer()->get(CategorieRepository::class)->nommee('Casquette'));
    }

    #[Test]
    public function on_n_ajoute_pas_un_doublon_meme_sans_majuscule_ni_accent(): void
    {
        self::assertSame(Command::FAILURE, $this->commande->execute(['nom' => 'vinyle']));
        self::assertSame(Command::FAILURE, $this->commande->execute(['nom' => 'echarpe']));
        self::assertStringContainsString('Écharpe', $this->commande->getDisplay());
    }

    #[Test]
    public function un_nom_vide_ou_trop_long_est_refuse(): void
    {
        self::assertSame(Command::INVALID, $this->commande->execute(['nom' => '   ']));
        self::assertSame(Command::INVALID, $this->commande->execute(['nom' => str_repeat('a', 61)]));
    }
}
