<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Logement;
use App\Entity\Utilisateur;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pièces et rangements (F-06) : création, renommage, suppression, et qui en a le droit.
 * Ces routes servent à l'écran 3 (création guidée) et à la page d'une pièce (« + Rangement »).
 */
final class PiecesEtRangementsApiTest extends ApiTestCase
{
    private Utilisateur $camille;
    private Utilisateur $nina;
    private int $logementId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->camille = $this->creerMajeur('camille');
        $this->nina = $this->creerMajeur('nina');
        $appartement = Logement::creer('Appartement Lyon', $this->camille, new \DateTimeImmutable());
        $this->em->persist($appartement);
        $this->em->flush();
        $this->logementId = (int) $appartement->getId();
    }

    #[Test]
    public function f06_je_cree_renomme_et_supprime_une_piece(): void
    {
        $jeton = $this->jetonDe($this->camille);

        $this->json('POST', '/api/logements/'.$this->logementId.'/pieces', ['nom' => 'Chambre'], $jeton);
        self::assertResponseStatusCodeSame(201);
        $piece = $this->reponse()['id'];

        $this->json('PATCH', '/api/pieces/'.$piece, ['nom' => 'Chambre de Jules'], $jeton);
        self::assertResponseIsSuccessful();
        self::assertSame('Chambre de Jules', $this->reponse()['nom']);

        $this->json('DELETE', '/api/pieces/'.$piece, null, $jeton);
        self::assertResponseStatusCodeSame(204);

        $this->json('GET', '/api/pieces/'.$piece, null, $jeton);
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function f06_je_cree_renomme_et_supprime_un_rangement(): void
    {
        $jeton = $this->jetonDe($this->camille);
        $this->json('POST', '/api/logements/'.$this->logementId.'/pieces', ['nom' => 'Chambre'], $jeton);
        $piece = $this->reponse()['id'];

        $this->json('POST', '/api/pieces/'.$piece.'/stockages', ['nom' => 'Armoire'], $jeton);
        self::assertResponseStatusCodeSame(201);
        $rangement = $this->reponse()['id'];

        $this->json('GET', '/api/logements/'.$this->logementId, null, $jeton);
        self::assertSame([['id' => $rangement, 'nom' => 'Armoire']], $this->reponse()['pieces'][0]['stockages']);

        $this->json('PATCH', '/api/stockages/'.$rangement, ['nom' => 'Grande armoire'], $jeton);
        self::assertResponseIsSuccessful();
        self::assertSame('Grande armoire', $this->reponse()['nom']);

        $this->json('DELETE', '/api/stockages/'.$rangement, null, $jeton);
        self::assertResponseStatusCodeSame(204);

        $this->json('GET', '/api/stockages/'.$rangement, null, $jeton);
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function s13_on_n_ajoute_ni_piece_ni_rangement_chez_les_autres(): void
    {
        $this->json('POST', '/api/logements/'.$this->logementId.'/pieces', ['nom' => 'Chambre'], $this->jetonDe($this->camille));
        $piece = $this->reponse()['id'];
        $jetonNina = $this->jetonDe($this->nina);

        $this->json('POST', '/api/logements/'.$this->logementId.'/pieces', ['nom' => 'Cave'], $jetonNina);
        self::assertResponseStatusCodeSame(404, 'Le logement d\'un autre est invisible (D-014).');

        $this->json('POST', '/api/pieces/'.$piece.'/stockages', ['nom' => 'Cachette'], $jetonNina);
        self::assertResponseStatusCodeSame(404);

        $this->json('PATCH', '/api/pieces/'.$piece, ['nom' => 'Piratée'], $jetonNina);
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function s01_une_adresse_trafiquee_repond_404_et_non_une_erreur_serveur(): void
    {
        $jeton = $this->jetonDe($this->camille);

        $this->json('POST', '/api/logements/abc/pieces', ['nom' => 'Chambre'], $jeton);
        self::assertResponseStatusCodeSame(404);

        $this->json('POST', '/api/pieces/1%20OR%201=1/stockages', ['nom' => 'Armoire'], $jeton);
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function un_nom_vide_est_refuse(): void
    {
        $jeton = $this->jetonDe($this->camille);

        $this->json('POST', '/api/logements/'.$this->logementId.'/pieces', ['nom' => ''], $jeton);
        self::assertResponseStatusCodeSame(422);

        $this->json('POST', '/api/logements/'.$this->logementId.'/pieces', ['nom' => 'Chambre'], $jeton);
        $this->json('POST', '/api/pieces/'.$this->reponse()['id'].'/stockages', ['nom' => str_repeat('a', 81)], $jeton);
        self::assertResponseStatusCodeSame(422);
    }
}
