<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Article;
use App\Entity\Logement;
use App\Entity\Stockage;
use App\Entity\Utilisateur;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ArticleApiTest extends ApiTestCase
{
    private Utilisateur $camille;
    private Utilisateur $leo;
    private Utilisateur $jules;
    private Utilisateur $nora;
    private Logement $appartement;
    private Stockage $armoire;
    private Stockage $commode;

    protected function setUp(): void
    {
        parent::setUp();
        $maintenant = new \DateTimeImmutable();

        $this->camille = $this->creerMajeur('camille');
        $this->leo = $this->creerMajeur('leo');
        $this->nora = $this->creerMajeur('nora');
        $this->jules = $this->creerMineur($this->camille, 'jules');

        $this->appartement = Logement::creer('Appartement Lyon', $this->camille, $maintenant);
        $this->appartement->inviter($this->leo, $this->camille, $maintenant)->accepter($maintenant);
        $this->appartement->inviter($this->jules, $this->camille, $maintenant)->accepter($maintenant);
        $chambre = $this->appartement->ajouterPiece('Chambre');
        $this->armoire = $chambre->ajouterStockage('Grande armoire');
        $this->commode = $chambre->ajouterStockage('Commode');
        $this->em->persist($this->appartement);
        $this->em->flush();
    }

    #[Test]
    public function f07_j_envoie_une_photo_puis_j_enregistre_l_article(): void
    {
        $jeton = $this->jetonDe($this->leo);
        $photo = $this->envoyerPhoto($jeton);
        self::assertResponseStatusCodeSame(201);
        self::assertMatchesRegularExpression('#^photos/\d+/[0-9a-f]{32}\.jpg$#', $photo);
        self::assertNull($this->reponse()['proposition'], 'Pas encore de reconnaissance : la saisie se fait à la main.');

        $this->json('POST', '/api/articles', [
            'photo' => $photo,
            'stockage' => $this->armoire->getId(),
            'categorie' => $this->categorie('Vinyle')->getId(),
            'marque' => 'Blue Note',
            'note' => 'Pressage original',
        ], $jeton);

        self::assertResponseStatusCodeSame(201);
        $article = $this->reponse();
        self::assertSame('leo', $article['auteur']['pseudo']);
        self::assertSame('Vinyle', $article['categorie']['nom']);
        self::assertSame('Grande armoire', $article['emplacement']['stockage']['nom']);
        self::assertFalse($article['estPersonnel']);

        // La photo est réduite à 1600 px et servie en JPG par l'API.
        $this->client->request('GET', $article['photoUrl'], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$jeton]);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/jpeg');
        $taille = getimagesizefromstring((string) $this->client->getResponse()->getContent());
        self::assertSame([1600, 800, \IMAGETYPE_JPEG], [$taille[0], $taille[1], $taille[2]]);

        // Une photo ne sert qu'une fois.
        $this->json('POST', '/api/articles', ['photo' => $photo, 'stockage' => $this->armoire->getId(), 'categorie' => $this->categorie('Vinyle')->getId()], $jeton);
        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function un_fichier_qui_n_est_pas_une_image_est_refuse(): void
    {
        $texte = tempnam(sys_get_temp_dir(), 'texte');
        file_put_contents($texte, 'ceci n\'est pas une image');

        $this->client->request('POST', '/api/reconnaissances', [], ['photo' => new UploadedFile($texte, 'photo.jpg', 'image/jpeg', null, true)], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jetonDe($this->leo),
            'HTTP_ACCEPT' => 'application/json',
        ]);
        self::assertResponseStatusCodeSame(422);

        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/api/reconnaissances', [], [], ['HTTP_ACCEPT' => 'application/json']);
        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function s13_on_ne_range_pas_d_article_dans_le_logement_d_un_autre(): void
    {
        $jeton = $this->jetonDe($this->nora);
        $photo = $this->envoyerPhoto($jeton);

        $this->json('POST', '/api/articles', ['photo' => $photo, 'stockage' => $this->armoire->getId(), 'categorie' => $this->categorie('Pull')->getId()], $jeton);

        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function on_n_utilise_pas_la_photo_d_un_autre(): void
    {
        $photoDeCamille = $this->envoyerPhoto($this->jetonDe($this->camille));

        $this->json('POST', '/api/articles', ['photo' => $photoDeCamille, 'stockage' => $this->armoire->getId(), 'categorie' => $this->categorie('Pull')->getId()], $this->jetonDe($this->leo));

        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function a15_s03_l_auteur_et_la_photo_ne_se_choisissent_pas(): void
    {
        $jeton = $this->jetonDe($this->leo);
        $photo = $this->envoyerPhoto($jeton);
        $this->json('POST', '/api/articles', ['photo' => $photo, 'stockage' => $this->armoire->getId(), 'categorie' => $this->categorie('Pull')->getId(), 'auteur' => 'camille'], $jeton);
        self::assertResponseStatusCodeSame(422);

        $article = $this->article($this->leo);
        $this->json('PATCH', '/api/articles/'.$article->getId(), ['auteur' => 'camille'], $jeton);
        self::assertResponseStatusCodeSame(422);
        $this->json('PATCH', '/api/articles/'.$article->getId(), ['photo' => $photo], $jeton);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('leo', $this->relire($article)->getAuteur()->getPseudo());
    }

    #[Test]
    public function a01_s05_le_membre_voit_l_emplacement_le_visiteur_non(): void
    {
        $article = $this->article($this->camille, note: 'Offert par Mamie');

        $this->json('GET', '/api/articles/'.$article->getId(), null, $this->jetonDe($this->leo));
        self::assertResponseIsSuccessful();
        self::assertSame('Grande armoire', $this->reponse()['emplacement']['stockage']['nom']);
        self::assertSame('Offert par Mamie', $this->reponse()['note']);

        $this->json('GET', '/api/articles/'.$article->getId(), null, $this->jetonDe($this->nora));
        self::assertResponseIsSuccessful();
        $vue = $this->reponse();
        self::assertSame(['nom' => 'Appartement Lyon'], $vue['logement']);
        self::assertSame('camille', $vue['auteur']['pseudo']);
        foreach (['emplacement', 'note', 'estPersonnel', 'creeLe'] as $champ) {
            self::assertArrayNotHasKey($champ, $vue);
        }
        $contenu = (string) $this->client->getResponse()->getContent();
        foreach (['Grande armoire', 'Chambre', 'Mamie', '@exemple.fr', 'dateNaissance'] as $interdit) {
            self::assertStringNotContainsString($interdit, $contenu);
        }
    }

    #[Test]
    public function s01_a04_un_article_personnel_reste_dans_le_logement(): void
    {
        $article = $this->article($this->camille, personnel: true);

        $this->json('GET', '/api/articles/'.$article->getId(), null, $this->jetonDe($this->nora));
        self::assertResponseStatusCodeSame(404);
        $this->json('GET', '/api/articles/'.$article->getId());
        self::assertResponseStatusCodeSame(404);
        $this->client->request('GET', '/api/articles/'.$article->getId().'/photo');
        self::assertResponseStatusCodeSame(404);

        $this->json('GET', '/api/articles/'.$article->getId(), null, $this->jetonDe($this->jules));
        self::assertResponseIsSuccessful();
        self::assertTrue($this->reponse()['estPersonnel']);
    }

    #[Test]
    public function a03_a16_compte_prive_ou_mineur_rien_pour_les_inconnus(): void
    {
        $this->camille->definirPrive(true, new \DateTimeImmutable());
        $articleDeCamille = $this->article($this->camille);
        $articleDeJules = $this->article($this->jules);

        foreach ([$articleDeCamille, $articleDeJules] as $article) {
            $this->json('GET', '/api/articles/'.$article->getId());
            self::assertResponseStatusCodeSame(404);
            $this->json('GET', '/api/articles/'.$article->getId(), null, $this->jetonDe($this->nora));
            self::assertResponseStatusCodeSame(404);
        }

        $articleDeLeo = $this->article($this->leo);
        $this->json('GET', '/api/articles/'.$articleDeLeo->getId());
        self::assertResponseIsSuccessful('Le compte de Léo est public : on regarde l\'auteur, pas le propriétaire du logement.');
    }

    #[Test]
    public function s02_un_visiteur_ne_modifie_pas(): void
    {
        $article = $this->article($this->camille);

        $this->json('PATCH', '/api/articles/'.$article->getId(), ['marque' => 'Piratée'], $this->jetonDe($this->nora));

        self::assertResponseStatusCodeSame(403);
        self::assertSame('Uniqlo', $this->relire($article)->getMarque());
    }

    #[Test]
    public function s06_a11_un_mineur_ne_modifie_que_ses_articles(): void
    {
        $articleDeCamille = $this->article($this->camille);
        $articleDeJules = $this->article($this->jules);
        $jeton = $this->jetonDe($this->jules);

        $this->json('DELETE', '/api/articles/'.$articleDeCamille->getId(), null, $jeton);
        self::assertResponseStatusCodeSame(403);

        $this->json('PATCH', '/api/articles/'.$articleDeJules->getId(), ['marque' => 'Decathlon'], $jeton);
        self::assertResponseIsSuccessful();
        self::assertSame('Decathlon', $this->reponse()['marque']);
    }

    #[Test]
    public function a10_un_co_proprietaire_deplace_et_corrige_un_article(): void
    {
        $article = $this->article($this->camille);
        $id = $article->getId();

        $this->json('PATCH', '/api/articles/'.$id, ['stockage' => $this->commode->getId(), 'marque' => null, 'estPersonnel' => true], $this->jetonDe($this->leo));

        self::assertResponseIsSuccessful();
        $vue = $this->reponse();
        self::assertSame('Commode', $vue['emplacement']['stockage']['nom']);
        self::assertArrayNotHasKey('marque', $vue, 'Marque vidée.');
        self::assertSame('Bleu', $vue['couleur'], 'Un champ non envoyé ne change pas.');
        self::assertTrue($vue['estPersonnel']);

        // Le déplacement ne supprime pas l'article (orphanRemoval sur le rangement de départ).
        self::assertSame('Commode', $this->relire($article)->getStockage()->getNom());
    }

    #[Test]
    public function f09_le_proprietaire_supprime_un_article(): void
    {
        $article = $this->article($this->leo);
        $id = $article->getId();

        $this->json('DELETE', '/api/articles/'.$id, null, $this->jetonDe($this->camille));
        self::assertResponseStatusCodeSame(204);

        $this->json('GET', '/api/articles/'.$id, null, $this->jetonDe($this->camille));
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function f26_je_cherche_dans_mes_logements_uniquement(): void
    {
        $pull = $this->article($this->camille, note: 'Laine mérinos');
        $vinyle = $this->article($this->leo);
        $vinyle->decrire($this->categorie('Vinyle'), 'Blue Note', 'Noir', null);

        $maintenant = new \DateTimeImmutable();
        $studio = Logement::creer('Studio de Nora', $this->nora, $maintenant);
        $etagere = $studio->ajouterPiece('Salon')->ajouterStockage('Étagère');
        $this->em->persist($studio);
        $this->em->persist(Article::creer($this->nora, $etagere, $this->categorie('Vinyle'), 'photos/test.jpg', $maintenant));
        $this->em->flush();
        $jeton = $this->jetonDe($this->leo);

        $this->json('GET', '/api/articles', null, $jeton);
        self::assertResponseIsSuccessful();
        self::assertEqualsCanonicalizing([$pull->getId(), $vinyle->getId()], array_column($this->reponse(), 'id'));

        $this->json('GET', '/api/articles?q=merinos', null, $jeton);
        self::assertSame([$pull->getId()], array_column($this->reponse(), 'id'), 'Sans tenir compte des accents.');

        $this->json('GET', '/api/articles?categorie='.$this->categorie('Vinyle')->getId(), null, $jeton);
        self::assertSame([$vinyle->getId()], array_column($this->reponse(), 'id'));
        self::assertSame('Grande armoire', $this->reponse()[0]['emplacement']['stockage']['nom'], 'On voit où il est rangé.');

        $this->json('GET', '/api/articles');
        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function f21_un_visiteur_voit_les_articles_non_personnels_d_un_compte_public(): void
    {
        $pull = $this->article($this->camille);
        $this->article($this->camille, personnel: true);
        $this->article($this->leo);

        $this->json('GET', '/api/utilisateurs/camille/articles');

        self::assertResponseIsSuccessful();
        self::assertSame([$pull->getId()], array_column($this->reponse(), 'id'), 'Ni l\'article personnel, ni celui de Léo rangé dans le même logement.');
        self::assertArrayNotHasKey('emplacement', $this->reponse()[0], 'Toujours la vue publique.');
    }

    #[Test]
    public function f27_on_filtre_les_articles_d_une_personne_par_categorie(): void
    {
        $this->article($this->camille);
        $vinyle = $this->article($this->camille);
        $vinyle->decrire($this->categorie('Vinyle'), null, null, null);
        $this->em->flush();

        $this->json('GET', '/api/utilisateurs/camille/articles?categorie='.$this->categorie('Vinyle')->getId(), null, $this->jetonDe($this->nora));

        self::assertResponseIsSuccessful();
        self::assertSame([$vinyle->getId()], array_column($this->reponse(), 'id'));
    }

    #[Test]
    public function s04_compte_prive_liste_vide_sauf_pour_soi(): void
    {
        $this->camille->definirPrive(true, new \DateTimeImmutable());
        $pull = $this->article($this->camille);

        $this->json('GET', '/api/utilisateurs/camille/articles');
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->reponse());

        $this->json('GET', '/api/utilisateurs/camille/articles', null, $this->jetonDe($this->nora));
        self::assertSame([], $this->reponse());

        $this->json('GET', '/api/utilisateurs/camille/articles', null, $this->jetonDe($this->camille));
        self::assertSame([$pull->getId()], array_column($this->reponse(), 'id'));

        $this->json('GET', '/api/utilisateurs/inconnu/articles');
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * Après chaque requête, Symfony vide le gestionnaire d'entités : on recharge l'auteur et le
     * rangement pour pouvoir créer un article à tout moment du test.
     */
    private function article(Utilisateur $auteur, bool $personnel = false, ?string $note = null): Article
    {
        $pull = $this->categorie('Pull');
        $auteur = $this->em->find(Utilisateur::class, $auteur->getId());
        $armoire = $this->em->find(Stockage::class, $this->armoire->getId());
        $article = Article::creer($auteur, $armoire, $pull, 'photos/test.jpg', new \DateTimeImmutable());
        $article->decrire($pull, 'Uniqlo', 'Bleu', $note);
        $article->marquerPersonnel($personnel);
        $this->em->persist($article);
        $this->em->flush();

        return $article;
    }

    /** L'article tel qu'il est en base, et non l'objet gardé en mémoire par le test. */
    private function relire(Article $article): Article
    {
        $this->em->clear();

        return $this->em->find(Article::class, $article->getId()) ?? self::fail('Article supprimé.');
    }

    /** POST /api/reconnaissances avec une image de 2400 × 1200 px ; renvoie le chemin de la photo. */
    private function envoyerPhoto(string $jeton): string
    {
        $fichier = tempnam(sys_get_temp_dir(), 'photo');
        imagejpeg(imagecreatetruecolor(2400, 1200), $fichier);

        $this->client->getCookieJar()->clear();
        $this->client->request('POST', '/api/reconnaissances', [], ['photo' => new UploadedFile($fichier, 'photo.jpg', 'image/jpeg', null, true)], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$jeton,
            'HTTP_ACCEPT' => 'application/json',
        ]);

        return $this->reponse()['photo'] ?? '';
    }
}
