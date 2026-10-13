<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Article;
use App\Entity\Logement;
use App\Entity\Utilisateur;
use PHPUnit\Framework\Attributes\Test;

/**
 * Logements, pièces, rangements et membres : règles A-01 à A-11 et tests de sécurité S-xx
 * (docs/06-modele-autorisation.md, séance 8-9 « Voler les affaires du voisin »).
 */
final class LogementApiTest extends ApiTestCase
{
    private Utilisateur $camille;
    private Utilisateur $leo;
    private Utilisateur $nina;
    private Utilisateur $jules;
    private Logement $appartement;

    protected function setUp(): void
    {
        parent::setUp();
        $maintenant = new \DateTimeImmutable();

        $this->camille = $this->creerMajeur('camille');
        $this->leo = $this->creerMajeur('leo');
        $this->nina = $this->creerMajeur('nina');
        $this->jules = $this->creerMineur($this->camille, 'jules');

        $this->appartement = Logement::creer('Appartement Lyon', $this->camille, $maintenant);
        $this->appartement->inviter($this->leo, $this->camille, $maintenant)->accepter($maintenant);
        $this->appartement->inviter($this->jules, $this->camille, $maintenant)->accepter($maintenant);
        $this->appartement->ajouterPiece('Chambre')->ajouterStockage('Grande armoire');
        $this->em->persist($this->appartement);
        $this->em->flush();
    }

    #[Test]
    public function f05_je_cree_un_logement_et_j_en_suis_proprietaire(): void
    {
        $jeton = $this->jetonDe($this->nina);

        $this->json('POST', '/api/logements', ['nom' => 'Maison Annecy'], $jeton);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('PROPRIETAIRE', $this->reponse()['monRole']);

        $this->json('GET', '/api/logements', null, $jeton);
        self::assertSame(['Maison Annecy'], array_column($this->reponse(), 'nom'));
    }

    #[Test]
    public function i08_un_mineur_ne_cree_pas_de_logement(): void
    {
        $this->json('POST', '/api/logements', ['nom' => 'Ma chambre'], $this->jetonDe($this->jules));

        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function a15_on_ne_choisit_pas_le_proprietaire_a_la_creation(): void
    {
        $this->json('POST', '/api/logements', ['nom' => 'Piège', 'proprietaire' => 'leo'], $this->jetonDe($this->nina));

        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function a01_un_membre_voit_le_detail_avec_pieces_et_rangements(): void
    {
        $this->json('GET', '/api/logements/'.$this->appartement->getId(), null, $this->jetonDe($this->leo));

        self::assertResponseIsSuccessful();
        self::assertSame('COPROPRIETAIRE', $this->reponse()['monRole']);
        self::assertSame('Grande armoire', $this->reponse()['pieces'][0]['stockages'][0]['nom']);
    }

    #[Test]
    public function s01_un_non_membre_ne_voit_pas_le_logement_d_un_autre(): void
    {
        $jeton = $this->jetonDe($this->nina);
        $id = $this->appartement->getId();

        foreach (['GET' => null, 'PATCH' => ['nom' => 'Volé'], 'DELETE' => null] as $methode => $corps) {
            $this->json($methode, '/api/logements/'.$id, $corps, $jeton);
            self::assertResponseStatusCodeSame(404, $methode.' doit répondre 404 : un 403 confirmerait que le logement existe.');
        }
        $this->json('GET', '/api/logements/'.$id.'/membres', null, $jeton);
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function s04_sans_jeton_on_n_obtient_rien(): void
    {
        $this->json('GET', '/api/logements/'.$this->appartement->getId());

        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function a10_un_coproprietaire_majeur_renomme_le_logement(): void
    {
        $this->json('PATCH', '/api/logements/'.$this->appartement->getId(), ['nom' => 'Appart Lyon 3e'], $this->jetonDe($this->leo));

        self::assertResponseIsSuccessful();
        self::assertSame('Appart Lyon 3e', $this->reponse()['nom']);
    }

    #[Test]
    public function s07_un_mineur_ne_renomme_pas_le_logement_mais_ajoute_une_piece(): void
    {
        $jeton = $this->jetonDe($this->jules);
        $id = $this->appartement->getId();

        $this->json('PATCH', '/api/logements/'.$id, ['nom' => 'Chez Jules'], $jeton);
        self::assertResponseStatusCodeSame(403);

        $this->json('POST', '/api/logements/'.$id.'/pieces', ['nom' => 'Chambre de Jules'], $jeton);
        self::assertResponseStatusCodeSame(201);
    }

    #[Test]
    public function s08_un_coproprietaire_ne_supprime_pas_le_logement(): void
    {
        $this->json('DELETE', '/api/logements/'.$this->appartement->getId(), null, $this->jetonDe($this->leo));

        self::assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function d011_le_proprietaire_supprime_son_logement_et_leo_le_reprend(): void
    {
        $this->json('DELETE', '/api/logements/'.$this->appartement->getId(), null, $this->jetonDe($this->camille));
        self::assertResponseStatusCodeSame(204);

        $this->json('GET', '/api/logements', null, $this->jetonDe($this->leo));
        self::assertSame('PROPRIETAIRE', $this->reponse()[0]['monRole']);
    }

    #[Test]
    public function h13_une_invitation_ne_donne_acces_qu_apres_acceptation(): void
    {
        $id = $this->appartement->getId();
        $suzanne = $this->creerMajeur('suzanne');
        $jetonSuzanne = $this->jetonDe($suzanne);

        $this->json('POST', '/api/logements/'.$id.'/membres', ['pseudo' => 'suzanne'], $this->jetonDe($this->leo));
        self::assertResponseStatusCodeSame(201);
        self::assertSame('INVITE', $this->reponse()['statut']);
        $invitation = $this->reponse()['id'];

        $this->json('GET', '/api/logements/'.$id, null, $jetonSuzanne);
        self::assertResponseStatusCodeSame(404, 'Pas d\'accès avant d\'avoir accepté.');

        $this->json('POST', '/api/membres/'.$invitation.'/acceptation', null, $this->jetonDe($this->nina));
        self::assertResponseStatusCodeSame(404, 'Nina ne voit pas l\'invitation de Suzanne.');

        $this->json('GET', '/api/moi/invitations', null, $jetonSuzanne);
        self::assertSame([$invitation], array_column($this->reponse(), 'id'));

        $this->json('POST', '/api/membres/'.$invitation.'/acceptation', null, $jetonSuzanne);
        self::assertResponseIsSuccessful();
        self::assertSame('ACCEPTE', $this->reponse()['statut']);

        $this->json('GET', '/api/logements/'.$id, null, $jetonSuzanne);
        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function a10_un_mineur_n_invite_personne_et_un_doublon_est_refuse(): void
    {
        $id = $this->appartement->getId();

        $this->json('POST', '/api/logements/'.$id.'/membres', ['pseudo' => 'nina'], $this->jetonDe($this->jules));
        self::assertResponseStatusCodeSame(403);

        $this->json('POST', '/api/logements/'.$id.'/membres', ['pseudo' => 'leo'], $this->jetonDe($this->camille));
        self::assertResponseStatusCodeSame(409);
    }

    #[Test]
    public function s05_la_liste_des_membres_ne_revele_pas_les_e_mails(): void
    {
        $this->json('GET', '/api/logements/'.$this->appartement->getId().'/membres', null, $this->jetonDe($this->leo));

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('@exemple.fr', (string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('dateNaissance', (string) $this->client->getResponse()->getContent());
    }

    #[Test]
    public function i10_une_piece_qui_contient_des_articles_ne_se_supprime_pas(): void
    {
        $armoire = $this->appartement->getPieces()->first()->getStockages()->first();
        $pull = $this->categorie('Pull');
        $this->em->persist(Article::creer($this->camille, $armoire, $pull, 'photos/1.jpg', new \DateTimeImmutable()));
        $this->em->flush();

        $this->json('DELETE', '/api/pieces/'.$armoire->getPiece()->getId(), null, $this->jetonDe($this->camille));
        self::assertResponseStatusCodeSame(409);

        $this->json('DELETE', '/api/stockages/'.$armoire->getId(), null, $this->jetonDe($this->camille));
        self::assertResponseStatusCodeSame(409);
    }

    #[Test]
    public function a17_seul_le_proprietaire_retire_un_coproprietaire_et_ses_articles_partent_avec_lui(): void
    {
        $armoire = $this->appartement->getPieces()->first()->getStockages()->first();
        $pull = $this->categorie('Pull');
        $this->em->persist(Article::creer($this->jules, $armoire, $pull, 'photos/1.jpg', new \DateTimeImmutable()));
        $this->em->persist(Article::creer($this->camille, $armoire, $pull, 'photos/2.jpg', new \DateTimeImmutable()));
        $this->em->flush();
        $membreJules = $this->appartement->membreDe($this->jules)->getId();

        $this->json('DELETE', '/api/membres/'.$membreJules, null, $this->jetonDe($this->leo));
        self::assertResponseStatusCodeSame(403, 'Léo, co-propriétaire, ne retire personne.');

        $this->json('DELETE', '/api/membres/'.$membreJules, null, $this->jetonDe($this->camille));
        self::assertResponseStatusCodeSame(204);

        $this->em->clear();
        $articles = $this->em->getRepository(Article::class)->findAll();
        self::assertCount(1, $articles, 'Seul l\'article de Camille reste.');
        self::assertSame('camille', $articles[0]->getAuteur()->getPseudo());
    }

    #[Test]
    public function a17_un_coproprietaire_quitte_le_logement_de_lui_meme(): void
    {
        $membreLeo = $this->appartement->membreDe($this->leo)->getId();

        $this->json('DELETE', '/api/membres/'.$membreLeo, null, $this->jetonDe($this->leo));
        self::assertResponseStatusCodeSame(204);

        $this->json('GET', '/api/logements/'.$this->appartement->getId(), null, $this->jetonDe($this->leo));
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function a15_le_proprietaire_ne_se_retire_pas_lui_meme(): void
    {
        $membreCamille = $this->appartement->membreDe($this->camille)->getId();

        $this->json('DELETE', '/api/membres/'.$membreCamille, null, $this->jetonDe($this->camille));
        self::assertResponseStatusCodeSame(403);
    }
}
