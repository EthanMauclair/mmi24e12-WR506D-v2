<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Utilisateur;
use App\Repository\CategorieRepository;
use App\Service\ReconnaissanceArticles;
use App\Tests\Double\ModeleSimule;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * POST /api/reconnaissances, puis POST /api/reconnaissances/proposition (D-041), avec le modèle
 * simulé (D-040) : la proposition est renvoyée sans
 * être enregistrée (H6), et toute panne donne une proposition vide sans perdre la photo (P2).
 */
final class ReconnaissanceApiTest extends ApiTestCase
{
    private Utilisateur $leo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leo = $this->creerMajeur('leo');
        ModeleSimule::reinitialiser();
    }

    #[Test]
    public function la_proposition_du_modele_est_renvoyee(): void
    {
        ModeleSimule::prevoirTexte('{"categorie": "Pull", "marque": "Uniqlo", "couleur": "bleu marine"}');

        $this->analyserPhoto();

        self::assertResponseIsSuccessful();
        self::assertSame([
            'categorie' => ['id' => $this->categorie('Pull')->getId(), 'nom' => 'Pull'],
            'marque' => 'Uniqlo',
            'couleur' => 'bleu marine',
        ], $this->reponse()['proposition']);
    }

    #[Test]
    public function la_requete_envoie_la_photo_reduite_et_la_liste_des_categories(): void
    {
        ModeleSimule::prevoirTexte('{"categorie": "Pull", "marque": null, "couleur": null}');

        $this->analyserPhoto();

        self::assertCount(1, ModeleSimule::$requetes);
        $requete = ModeleSimule::$requetes[0];
        self::assertSame('POST', $requete['methode']);
        self::assertSame('https://generativelanguage.googleapis.com/v1beta/openai/chat/completions', $requete['url']);
        self::assertContains('Authorization: Bearer cle-de-test', $requete['options']['headers']);

        $corps = json_decode($requete['options']['body'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('modele-de-test', $corps['model']);
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
        ModeleSimule::prevoirTexte('{"categorie": "Doudoune", "marque": "", "couleur": "Noir"}');

        $this->analyserPhoto();

        self::assertSame([
            'categorie' => ['id' => $this->categorie('Autre')->getId(), 'nom' => 'Autre'],
            'marque' => null,
            'couleur' => 'Noir',
        ], $this->reponse()['proposition']);
    }

    #[Test]
    public function la_categorie_est_reconnue_sans_casse_ni_accents_et_le_texte_autour_est_ignore(): void
    {
        ModeleSimule::prevoirTexte("Voici :\n```json\n{\"categorie\": \"echarpe\", \"marque\": null, \"couleur\": \"rouge\"}\n```");

        $this->analyserPhoto();

        self::assertSame('Écharpe', $this->reponse()['proposition']['categorie']['nom']);
    }

    #[Test]
    public function une_reponse_illisible_donne_une_proposition_vide(): void
    {
        ModeleSimule::prevoirTexte('Je pense que c\'est un pull bleu.');

        $this->analyserPhoto();

        self::assertResponseIsSuccessful();
        self::assertNull($this->reponse()['proposition']);
    }

    #[Test]
    public function des_accolades_sans_json_valide_donnent_une_proposition_vide(): void
    {
        ModeleSimule::prevoirTexte('{categorie: Pull, couleur: bleu}');

        $this->analyserPhoto();

        self::assertResponseIsSuccessful();
        self::assertNull($this->reponse()['proposition']);
    }

    #[Test]
    public function sans_categorie_autre_une_categorie_inconnue_donne_une_proposition_vide(): void
    {
        // Garde-fou : « Autre » vient d'une migration ; si elle manquait, on ne propose rien plutôt qu'une catégorie fausse.
        $this->em->remove($this->categorie('Autre'));
        $this->em->flush();
        ModeleSimule::prevoirTexte('{"categorie": "Doudoune", "marque": null, "couleur": "noir"}');

        $this->analyserPhoto();

        self::assertResponseIsSuccessful();
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
            ModeleSimule::prevoir($panne);
            $photo = $this->analyserPhoto();

            self::assertResponseIsSuccessful();
            self::assertNull($this->reponse()['proposition']);
            self::assertMatchesRegularExpression('#^photos/\d+/[0-9a-f]{32}\.jpg$#', $photo, 'La photo reste enregistrée.');
        }
    }

    #[Test]
    public function le_modele_de_secours_prend_le_relais_si_le_principal_refuse_ou_tarde(): void
    {
        $refus = [
            'quota atteint' => new MockResponse('{"error": {"message": "Quota exceeded"}}', ['http_code' => 429]),
            'surcharge' => new MockResponse('', ['http_code' => 503]),
            'délai dépassé' => new MockResponse('', ['error' => 'Délai dépassé']),
        ];

        foreach ($refus as $cas => $reponse) {
            ModeleSimule::reinitialiser();
            ModeleSimule::prevoir($reponse);
            ModeleSimule::prevoirTexte('{"categorie": "Pull", "marque": null, "couleur": "bleu"}');

            $this->analyserPhoto();

            self::assertResponseIsSuccessful();
            self::assertSame('Pull', $this->reponse()['proposition']['categorie']['nom'], $cas);
            $modeles = array_map(static fn (array $requete): string => json_decode($requete['options']['body'], true, 512, \JSON_THROW_ON_ERROR)['model'], ModeleSimule::$requetes);
            self::assertSame(['modele-de-test', 'modele-de-secours'], $modeles, $cas);
        }
    }

    #[Test]
    public function une_requete_refusee_pour_une_autre_raison_ne_passe_pas_au_secours(): void
    {
        ModeleSimule::prevoir(new MockResponse('{"error": {"message": "API key not valid"}}', ['http_code' => 400]));

        $this->analyserPhoto();

        self::assertResponseIsSuccessful();
        self::assertNull($this->reponse()['proposition']);
        self::assertCount(1, ModeleSimule::$requetes, 'Une clé invalide le serait aussi pour le secours.');
    }

    #[Test]
    public function sans_cle_aucun_appel_n_est_fait(): void
    {
        $client = new MockHttpClient(static fn () => self::fail('Aucun appel sans clé.'));
        $service = new ReconnaissanceArticles($client, static::getContainer()->get(CategorieRepository::class), new NullLogger(), '', 'modele-de-test');

        self::assertNull($service->proposer('jpeg'));
        self::assertSame(0, $client->getRequestsCount());
    }

    /** POST /api/reconnaissances avec une image de 2400 × 1200 px ; renvoie le chemin de la photo. */
    #[Test]
    public function l_envoi_de_la_photo_n_attend_pas_le_modele(): void
    {
        $photo = $this->envoyerPhoto();

        self::assertResponseStatusCodeSame(201);
        self::assertSame(['photo' => $photo], $this->reponse(), 'Seulement la photo : la proposition se demande à part.');
        self::assertCount(0, ModeleSimule::$requetes);
    }

    #[Test]
    public function on_ne_demande_une_proposition_que_pour_sa_propre_photo(): void
    {
        $photoDeLeo = $this->envoyerPhoto();
        $nora = $this->creerMajeur('nora');

        $this->json('POST', '/api/reconnaissances/proposition', ['photo' => $photoDeLeo], $this->jetonDe($nora));
        self::assertResponseStatusCodeSame(404);

        $this->json('POST', '/api/reconnaissances/proposition', ['photo' => 'photos/'.$this->leo->getId().'/'.str_repeat('0', 32).'.jpg'], $this->jetonDe($this->leo));
        self::assertResponseStatusCodeSame(404, 'Photo inexistante.');

        $this->json('POST', '/api/reconnaissances/proposition', ['photo' => $photoDeLeo]);
        self::assertResponseStatusCodeSame(401);

        self::assertCount(0, ModeleSimule::$requetes, 'Le modèle n\'est jamais appelé pour une photo refusée.');
    }

    /** Les deux étapes : envoi de la photo, puis demande de proposition ; la réponse lue ensuite est la proposition. */
    private function analyserPhoto(): string
    {
        $photo = $this->envoyerPhoto();
        $this->json('POST', '/api/reconnaissances/proposition', ['photo' => $photo], $this->jetonDe($this->leo));

        return $photo;
    }

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
