<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Logement;
use App\Entity\Utilisateur;
use App\Security\LogementVoter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/** Le voter des logements, sans base de données ni requête HTTP. */
final class LogementVoterTest extends TestCase
{
    #[Test]
    public function s04_sans_compte_aucun_droit_sur_un_logement(): void
    {
        $maintenant = new \DateTimeImmutable('2026-10-15');
        $camille = Utilisateur::creerMajeur('camille', 'camille@exemple.fr', new \DateTimeImmutable('1995-04-02'), false, $maintenant);
        $logement = Logement::creer('Appartement Lyon', $camille, $maintenant);
        $voter = new LogementVoter(new MockClock($maintenant));

        foreach ([LogementVoter::VOIR, LogementVoter::AJOUTER, LogementVoter::MODIFIER, LogementVoter::SUPPRIMER] as $droit) {
            self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new NullToken(), $logement, [$droit]), $droit);
        }
    }
}
