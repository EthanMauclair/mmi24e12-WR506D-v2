<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Logement;
use App\Entity\Utilisateur;
use PHPUnit\Framework\Attributes\Test;

final class MonCompteTest extends ApiTestCase
{
    #[Test]
    public function sans_jeton_on_n_accede_pas_a_son_compte(): void
    {
        $this->json('GET', '/api/moi');

        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function un_jeton_invalide_est_refuse(): void
    {
        $this->json('GET', '/api/moi', null, 'faux.jeton.signe');

        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function on_lit_son_propre_compte(): void
    {
        $camille = $this->creerMajeur('camille');

        $this->json('GET', '/api/moi', null, $this->jetonDe($camille));

        self::assertResponseIsSuccessful();
        self::assertSame('camille', $this->reponse()['pseudo']);
        self::assertSame('camille@exemple.fr', $this->reponse()['email']);
        self::assertArrayNotHasKey('motDePasse', $this->reponse());
    }

    #[Test]
    public function f02_on_passe_son_compte_en_prive(): void
    {
        $camille = $this->creerMajeur('camille');

        $this->json('PATCH', '/api/moi', ['estPrive' => true], $this->jetonDe($camille));

        self::assertResponseIsSuccessful();
        self::assertTrue($this->reponse()['estPrive']);
    }

    #[Test]
    public function i21_un_compte_mineur_ne_peut_pas_devenir_public(): void
    {
        $jules = $this->creerMineur($this->creerMajeur('camille'), 'jules');

        $this->json('PATCH', '/api/moi', ['estPrive' => false], $this->jetonDe($jules));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('I-21', $this->reponse()['detail']);
    }

    #[Test]
    public function a15_on_ne_change_ni_son_pseudo_ni_son_referent_par_cette_route(): void
    {
        $jules = $this->creerMineur($this->creerMajeur('camille'), 'jules');

        $this->json('PATCH', '/api/moi', ['referent' => null], $this->jetonDe($jules));

        self::assertResponseStatusCodeSame(422);
        $this->em->clear();
        self::assertNotNull($this->em->getRepository(Utilisateur::class)->findOneBy(['pseudo' => 'jules'])?->getReferent());
    }

    #[Test]
    public function i04_un_proprietaire_ne_peut_pas_supprimer_son_compte(): void
    {
        $camille = $this->creerMajeur('camille');
        $this->em->persist(Logement::creer('Appartement Lyon', $camille, new \DateTimeImmutable()));
        $this->em->flush();

        $this->json('DELETE', '/api/moi', null, $this->jetonDe($camille));

        self::assertResponseStatusCodeSame(409);
        self::assertStringContainsString('I-04', $this->reponse()['detail']);
    }

    #[Test]
    public function i03_supprimer_son_compte_supprime_ses_comptes_mineurs(): void
    {
        $camille = $this->creerMajeur('camille');
        $this->creerMineur($camille, 'jules');

        $this->json('DELETE', '/api/moi', null, $this->jetonDe($camille));

        self::assertResponseStatusCodeSame(204);
        $this->em->clear();
        self::assertSame(0, $this->em->getRepository(Utilisateur::class)->count([]));
    }

    #[Test]
    public function f01_je_change_mon_e_mail_s_il_n_est_pas_deja_pris(): void
    {
        $camille = $this->creerMajeur('camille');
        $this->creerMajeur('leo');
        $jeton = $this->jetonDe($camille);

        $this->json('PATCH', '/api/moi', ['email' => 'Leo@exemple.fr'], $jeton);
        self::assertResponseStatusCodeSame(409, 'Sans tenir compte des majuscules.');

        $this->json('PATCH', '/api/moi', ['email' => 'Camille.Martin@Exemple.fr'], $jeton);
        self::assertResponseIsSuccessful();
        self::assertSame('camille.martin@exemple.fr', $this->reponse()['email']);
    }

    #[Test]
    public function f01_apres_un_changement_de_mot_de_passe_l_ancien_ne_marche_plus(): void
    {
        $camille = $this->creerMajeur('camille');

        $this->json('PATCH', '/api/moi', ['motDePasse' => 'un-nouveau-mot-de-passe'], $this->jetonDe($camille));
        self::assertResponseIsSuccessful();

        $this->json('POST', '/api/auth/connexion', ['identifiant' => 'camille', 'motDePasse' => self::MOT_DE_PASSE]);
        self::assertResponseStatusCodeSame(401);

        $this->json('POST', '/api/auth/connexion', ['identifiant' => 'camille', 'motDePasse' => 'un-nouveau-mot-de-passe']);
        self::assertResponseIsSuccessful();
    }
}
