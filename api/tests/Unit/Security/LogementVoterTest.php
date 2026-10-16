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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
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

    /** docs/06 : A-01 (voir), A-11 (ajouter, mineur compris), A-10 (modifier, majeurs), A-09 (supprimer, propriétaire). */
    #[Test]
    public function a01_a09_a10_a11_chaque_membre_a_les_droits_de_son_role(): void
    {
        $maintenant = new \DateTimeImmutable('2026-10-15');
        $camille = Utilisateur::creerMajeur('camille', 'camille@exemple.fr', new \DateTimeImmutable('1995-04-02'), false, $maintenant);
        $leo = Utilisateur::creerMajeur('leo', 'leo@exemple.fr', new \DateTimeImmutable('1994-06-10'), false, $maintenant);
        $jules = Utilisateur::creerMineur($camille, 'jules', 'jules@exemple.fr', new \DateTimeImmutable('2011-03-05'), $maintenant);
        $invite = Utilisateur::creerMajeur('invite', 'invite@exemple.fr', new \DateTimeImmutable('1990-01-01'), false, $maintenant);
        $voisin = Utilisateur::creerMajeur('voisin', 'voisin@exemple.fr', new \DateTimeImmutable('1990-01-01'), false, $maintenant);
        $logement = Logement::creer('Appartement Lyon', $camille, $maintenant);
        $logement->inviter($leo, $camille, $maintenant)->accepter($maintenant);
        $logement->inviter($jules, $camille, $maintenant)->accepter($maintenant);
        $logement->inviter($invite, $camille, $maintenant);
        $voter = new LogementVoter(new MockClock($maintenant));

        $attendu = [
            'camille (propriétaire)' => [$camille, true, true, true, true],
            'leo (co-propriétaire majeur)' => [$leo, true, true, true, false],
            'jules (co-propriétaire mineur)' => [$jules, true, true, false, false],
            'invite (invitation en attente)' => [$invite, false, false, false, false],
            'voisin (non membre)' => [$voisin, false, false, false, false],
        ];
        foreach ($attendu as $qui => [$utilisateur, $voir, $ajouter, $modifier, $supprimer]) {
            $jeton = new UsernamePasswordToken($utilisateur, 'api', $utilisateur->getRoles());
            foreach ([LogementVoter::VOIR => $voir, LogementVoter::AJOUTER => $ajouter, LogementVoter::MODIFIER => $modifier, LogementVoter::SUPPRIMER => $supprimer] as $droit => $accorde) {
                self::assertSame(
                    $accorde ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
                    $voter->vote($jeton, $logement, [$droit]),
                    $qui.' / '.$droit,
                );
            }
        }
    }

    #[Test]
    public function le_voter_ne_se_prononce_pas_sur_autre_chose_qu_un_logement(): void
    {
        $voter = new LogementVoter(new MockClock());

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote(new NullToken(), new \stdClass(), [LogementVoter::VOIR]));
    }
}
