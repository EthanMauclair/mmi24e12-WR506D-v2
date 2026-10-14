<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Utilisateur;
use App\Repository\CategorieRepository;
use App\Service\ReconnaissanceArticles;
use App\Tests\Double\OpenRouterSimule;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * POST /api/reconnaissances avec OpenRouter simulé (D-023) : la proposition est renvoyée sans
 * être enregistrée (H6), et toute panne donne une proposition vide sans perdre la photo (P2).
 */
final class ReconnaissanceApiTest extends ApiTestCase
{
    private Utilisateur $leo;
    private OpenRouterSimule $openRouter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leo = $this->creerMajeur('leo');
        $this->openRouter = static::getContainer()->get(OpenRouterSimule::class);
    }

    #[Test]
    public function la_proposition_du_modele_est_renvoyee(): void
    {
        $this->openRouter->prevoirTexte('{"categorie": "Pull", "marque": "Uniqlo", "couleur": "bleu marine"}');

        $this->envoyerPhoto();

        self::assertResponseStatusCodeSame(201);
        self::assertSame([
            'categorie' => ['id' => $this->categorie('Pull')->getId(), 'nom' => 'Pull'],
            'marque' => 'Uniqlo',
            'couleur' => 'bleu marine',
        ], $this->reponse()['proposition']);
    }

    #[Test]
    public function la_requete_envoie_la_photo_reduite_et_la_liste_des_categories(): void
    {
        $this->openRouter->prevoirTexte('{"categorie": "Pull", "marque": null, "couleur": null}');

        $this->envoyerPhoto();

        self::assertCount(1, $this->openRouter->requetes);
        $requete = $this->openRouter->requetes[0];
        self::assertSame('POST', $requete['methode']);
        self::assertSame('https://openrouter.ai/api/v1/chat/completions', $requete['url']);
        self::assertContains('Authorization: Bearer cle-de-test', $requete['options']['headers']);

        $corps = json_decode($requete['options']['body'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('google/gemma-4-31b-it:free', $corps['model']);
        [$texte, $image] = $corps['messages'][0]['content'];
        self::assertStringContainsString('Vinyle', $texte['text']);
        self::assertStringContainsString('"Autre"', $texte['text']);

        // C'est la photo enregistrée (réduite à 1600 px, sans métadonnées) qui part chez le tiers.
        self::assertStringStartsWith('data:image/jpeg;base64,', $image['image_url']['url']);
        $envoyee = getimagesizefromstring(base64_decode(substr($image['image_url']['url'], 23), true));
        self::assertSame([1600, 800], [$envoyee[0], $envoyee[1]]);
    }

    #[Test]
    public function une_categorie_hors_liste_devient_autre(): void
    {
        $this->openRouter->prevoirTexte('{"categorie": "Doudoune", "marque": "", "couleur": "Noir"}');

        $this->envoyerPhoto();

        self::assertSame([
            'categorie' => ['id' => $this->categorie('Autre')->getId(), 'nom' => 'Autre'],
            'marque' => null,
            'couleur' => 'Noir',
        ], $this->reponse()['proposition']);
    }

    #[Test]
    public function la_categorie_est_reconnue_sans_casse_ni_accents_et_le_texte_autour_est_ignore(): void
    {
        $this->openRouter->prevoirTexte("Voici :\n```json\n{\"categorie\": \"echarpe\", \"marque\": null, \"couleur\": \"rouge\"}\n```");

        $this->envoyerPhoto();

        self::assertSame('Écharpe', $this->reponse()['proposition']['categorie']['nom']);
    }

    #[Test]
    public function une_reponse_illisible_donne_une_proposition_vide(): void
    {
        $this->openRouter->prevoirTexte('Je pense que c\'est un pull bleu.');

        $this->envoyerPhoto();

        self::assertResponseStatusCodeSame(201);
        self::assertNull($this->reponse()['proposition']);
    }

    #[Test]
    public function une_panne_ne_bloque_pas_l_ajout(): void
    {
        $pannes = [
            new MockResponse('{"error": {"message": "Rate limit exceeded"}}', ['http_code' => 429]),
            new MockResponse('', ['http_code' => 503]),
            new MockResponse('', ['error' => 'Délai dépassé']),
            new MockResponse('pas du json'),
        ];

        foreach ($pannes as $panne) {
            $this->openRouter->prevoir($panne);
            $photo = $this->envoyerPhoto();

            self::assertResponseStatusCodeSame(201);
            self::assertNull($this->reponse()['proposition']);
            self::assertMatchesRegularExpression('#^photos/\d+/[0-9a-f]{32}\.jpg$#', $photo, 'La photo reste enregistrée.');
        }
    }

    #[Test]
    public function sans_cle_aucun_appel_n_est_fait(): void
    {
        $client = new MockHttpClient(static fn () => self::fail('Aucun appel sans clé.'));
        $service = new ReconnaissanceArticles($client, static::getContainer()->get(CategorieRepository::class), new NullLogger(), '', 'google/gemma-4-31b-it:free');

        self::assertNull($service->proposer('jpeg'));
        self::assertSame(0, $client->getRequestsCount());
    }

    /** POST /api/reconnaissances avec une image de 2400 × 1200 px ; renvoie le chemin de la photo. */
    private function envoyerPhoto(): string
    {
        $fichier = tempnam(sys_get_temp_dir(), 'photo');
        imagejpeg(imagecreatetruecolor(2400, 1200), $fichier);

        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/api/reconnaissances', [], ['photo' => new UploadedFile($fichier, 'photo.jpg', 'image/jpeg', null, true)], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jetonDe($this->leo),
            'HTTP_ACCEPT' => 'application/json',
        ]);

        return $this->reponse()['photo'] ?? '';
    }
}
