<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use PHPUnit\Framework\Attributes\Test;

final class AuthentificationTest extends ApiTestCase
{
    private const INSCRIPTION = [
        'pseudo' => 'camille.dressing',
        'email' => 'camille@exemple.fr',
        'motDePasse' => 'un-mot-de-passe-solide',
        'dateNaissance' => '1995-04-02',
    ];

    #[Test]
    public function l_inscription_cree_un_compte_sans_jamais_renvoyer_le_mot_de_passe(): void
    {
        $this->json('POST', '/api/auth/inscription', self::INSCRIPTION);

        self::assertResponseStatusCodeSame(201);
        $compte = $this->reponse();
        self::assertSame('camille.dressing', $compte['pseudo']);
        self::assertFalse($compte['estMineur']);
        self::assertFalse($compte['estPrive'], 'Public par défaut.');
        self::assertArrayNotHasKey('motDePasse', $compte);
        self::assertStringNotContainsString('un-mot-de-passe-solide', (string) $this->client->getResponse()->getContent());
    }

    #[Test]
    public function i01_un_mineur_ne_peut_pas_s_inscrire_seul(): void
    {
        $this->json('POST', '/api/auth/inscription', ['dateNaissance' => (new \DateTimeImmutable('-15 years'))->format('Y-m-d')] + self::INSCRIPTION);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('I-01', $this->reponse()['detail']);
    }

    #[Test]
    public function un_pseudo_deja_pris_est_refuse(): void
    {
        $this->creerMajeur('camille.dressing');

        $this->json('POST', '/api/auth/inscription', self::INSCRIPTION);

        self::assertResponseStatusCodeSame(409);
    }

    #[Test]
    public function un_mot_de_passe_trop_court_est_refuse(): void
    {
        $this->json('POST', '/api/auth/inscription', ['motDePasse' => 'court'] + self::INSCRIPTION);

        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function a15_un_champ_non_prevu_est_refuse(): void
    {
        $this->json('POST', '/api/auth/inscription', self::INSCRIPTION + ['roles' => ['ROLE_ADMIN']]);

        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function on_se_connecte_par_email_ou_par_pseudo_et_le_jeton_part_dans_un_cookie_httponly(): void
    {
        $this->creerMajeur('camille');

        foreach (['camille@exemple.fr', 'camille'] as $identifiant) {
            $this->json('POST', '/api/auth/connexion', ['identifiant' => $identifiant, 'motDePasse' => self::MOT_DE_PASSE]);

            self::assertResponseIsSuccessful();
            self::assertArrayHasKey('jeton', $this->reponse());
            $cookie = $this->client->getResponse()->headers->getCookies()[0] ?? null;
            self::assertNotNull($cookie);
            self::assertSame('BEARER', $cookie->getName());
            self::assertTrue($cookie->isHttpOnly());
            self::assertTrue($cookie->isSecure());
        }
    }

    #[Test]
    public function un_mauvais_mot_de_passe_est_refuse(): void
    {
        $this->creerMajeur('camille');

        $this->json('POST', '/api/auth/connexion', ['identifiant' => 'camille', 'motDePasse' => 'pas-le-bon-mot-de-passe']);

        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function la_deconnexion_efface_le_cookie(): void
    {
        $this->json('POST', '/api/auth/deconnexion');

        self::assertResponseStatusCodeSame(204);
        $cookie = $this->client->getResponse()->headers->getCookies()[0];
        self::assertSame('BEARER', $cookie->getName());
        self::assertTrue($cookie->isCleared());
    }
}
