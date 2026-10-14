<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Article;
use App\Entity\Logement;
use App\Entity\Stockage;
use App\Entity\Utilisateur;
use PHPUnit\Framework\Attributes\Test;

/**
 * Abonnés et amis (F-15 à F-18), et ce qu'ils ouvrent sur les articles (A-03, A-16, cas 4).
 * Camille est propriétaire de l'appartement ; Léo et Jules (mineur) en sont co-propriétaires.
 */
final class RelationApiTest extends ApiTestCase
{
    private Utilisateur $camille;
    private Utilisateur $leo;
    private Utilisateur $jules;
    private Utilisateur $sophie;
    private Utilisateur $nora;
    private Stockage $armoire;

    /** Le compte de Camille est privé ou public selon le test. */
    private function preparer(bool $camillePrivee = true, bool $leoPrive = false): void
    {
        $maintenant = new \DateTimeImmutable();

        $this->camille = $this->creerMajeur('camille', $camillePrivee);
        $this->leo = $this->creerMajeur('leo', $leoPrive);
        $this->sophie = $this->creerMajeur('sophie');
        $this->nora = $this->creerMajeur('nora');
        $this->jules = $this->creerMineur($this->camille, 'jules');

        $appartement = Logement::creer('Appartement Lyon', $this->camille, $maintenant);
        $appartement->inviter($this->leo, $this->camille, $maintenant)->accepter($maintenant);
        $appartement->inviter($this->jules, $this->camille, $maintenant)->accepter($maintenant);
        $this->armoire = $appartement->ajouterPiece('Chambre')->ajouterStockage('Grande armoire');
        $this->em->persist($appartement);
        $this->em->flush();
    }

    #[Test]
    public function f15_i18_on_s_abonne_directement_a_un_compte_public(): void
    {
        $this->preparer(camillePrivee: false);
        $jeton = $this->jetonDe($this->nora);

        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $jeton);

        self::assertResponseStatusCodeSame(201);
        $relation = $this->reponse();
        self::assertSame(['pseudo' => 'nora'], $relation['suiveur'], 'Le pseudo seul, sans e-mail (S-05).');
        self::assertSame(['pseudo' => 'camille'], $relation['suivi']);
        self::assertSame(['ABONNE', 'ACCEPTEE'], [$relation['niveau'], $relation['statut']]);

        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $jeton);
        self::assertResponseStatusCodeSame(409, 'Une seule relation par couple (I-17).');

        $this->json('POST', '/api/utilisateurs/nora/abonnement', null, $jeton);
        self::assertResponseStatusCodeSame(409, 'On ne se suit pas soi-même (I-17).');

        $this->json('POST', '/api/utilisateurs/inconnu/abonnement', null, $jeton);
        self::assertResponseStatusCodeSame(404);

        $this->json('POST', '/api/utilisateurs/camille/abonnement');
        self::assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function f15_f16_compte_prive_la_demande_attend_l_acceptation(): void
    {
        $this->preparer();
        $pull = $this->article($this->camille);
        $jetonNora = $this->jetonDe($this->nora);
        $jetonCamille = $this->jetonDe($this->camille);

        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $jetonNora);
        self::assertResponseStatusCodeSame(201);
        self::assertSame('EN_ATTENTE', $this->reponse()['statut']);
        $id = $this->reponse()['id'];

        $this->json('GET', '/api/utilisateurs/camille/articles', null, $jetonNora);
        self::assertSame([], $this->reponse(), 'Une demande en attente ne donne aucun droit.');
        $this->json('GET', '/api/articles/'.$pull->getId(), null, $jetonNora);
        self::assertResponseStatusCodeSame(404);

        $this->json('GET', '/api/moi/demandes-abonnement', null, $jetonCamille);
        self::assertSame([$id], array_column($this->reponse(), 'id'));

        $this->json('PATCH', '/api/relations/'.$id, ['statut' => 'ACCEPTEE'], $jetonNora);
        self::assertResponseStatusCodeSame(403, 'Nora ne s\'accepte pas elle-même.');

        $this->json('PATCH', '/api/relations/'.$id, ['statut' => 'ACCEPTEE'], $jetonCamille);
        self::assertResponseIsSuccessful();
        self::assertSame('ACCEPTEE', $this->reponse()['statut']);

        $this->json('GET', '/api/utilisateurs/camille/articles', null, $jetonNora);
        self::assertSame([$pull->getId()], array_column($this->reponse(), 'id'), 'A-03 : une relation acceptée ouvre le compte privé.');
        $this->json('GET', '/api/articles/'.$pull->getId(), null, $jetonNora);
        self::assertResponseIsSuccessful();
        self::assertArrayNotHasKey('emplacement', $this->reponse(), 'Toujours la vue publique.');

        $this->json('GET', '/api/moi/demandes-abonnement', null, $jetonCamille);
        self::assertSame([], $this->reponse());
        $this->json('GET', '/api/moi/relations', null, $jetonCamille);
        self::assertSame(['nora'], array_column(array_column($this->reponse(), 'suiveur'), 'pseudo'));
    }

    #[Test]
    public function a16_un_abonne_d_un_compte_mineur_doit_etre_accepte(): void
    {
        $this->preparer(camillePrivee: false);
        $articleDeJules = $this->article($this->jules);
        $jetonNora = $this->jetonDe($this->nora);

        $this->json('POST', '/api/utilisateurs/jules/abonnement', null, $jetonNora);
        self::assertSame('EN_ATTENTE', $this->reponse()['statut']);
        $id = $this->reponse()['id'];
        $this->json('GET', '/api/articles/'.$articleDeJules->getId(), null, $jetonNora);
        self::assertResponseStatusCodeSame(404);

        $this->json('PATCH', '/api/relations/'.$id, ['statut' => 'ACCEPTEE'], $this->jetonDe($this->jules));
        self::assertResponseIsSuccessful();

        $this->json('GET', '/api/articles/'.$articleDeJules->getId(), null, $jetonNora);
        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function f16_refuser_une_demande_puis_retirer_un_abonne(): void
    {
        $this->preparer();
        $pull = $this->article($this->camille);
        $jetonNora = $this->jetonDe($this->nora);
        $jetonCamille = $this->jetonDe($this->camille);

        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $jetonNora);
        $id = $this->reponse()['id'];

        $this->json('DELETE', '/api/relations/'.$id, null, $this->jetonDe($this->leo));
        self::assertResponseStatusCodeSame(404, 'Léo ne voit pas une relation qui ne le concerne pas.');
        $this->json('DELETE', '/api/relations/'.$id, null, $jetonNora);
        self::assertResponseStatusCodeSame(403, 'Le suiveur passe par /utilisateurs/{pseudo}/abonnement.');

        $this->json('DELETE', '/api/relations/'.$id, null, $jetonCamille);
        self::assertResponseStatusCodeSame(204);
        $this->json('GET', '/api/moi/demandes-abonnement', null, $jetonCamille);
        self::assertSame([], $this->reponse());

        // Nora peut redemander ; Camille accepte, puis la retire.
        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $jetonNora);
        self::assertResponseStatusCodeSame(201);
        $id = $this->reponse()['id'];
        $this->json('PATCH', '/api/relations/'.$id, ['statut' => 'ACCEPTEE'], $jetonCamille);
        $this->json('DELETE', '/api/relations/'.$id, null, $jetonCamille);
        self::assertResponseStatusCodeSame(204);

        $this->json('GET', '/api/articles/'.$pull->getId(), null, $jetonNora);
        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function f16_un_statut_ou_un_champ_inconnu_est_refuse(): void
    {
        $this->preparer();
        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $this->jetonDe($this->nora));
        $id = $this->reponse()['id'];
        $jetonCamille = $this->jetonDe($this->camille);

        $this->json('PATCH', '/api/relations/'.$id, ['statut' => 'EN_ATTENTE'], $jetonCamille);
        self::assertResponseStatusCodeSame(422);
        $this->json('PATCH', '/api/relations/'.$id, ['suiveur' => 'leo'], $jetonCamille);
        self::assertResponseStatusCodeSame(422, 'A-15 : champ non prévu.');
        $this->json('GET', '/api/moi/relations?niveau=COUSIN', null, $jetonCamille);
        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function f15_on_se_desabonne(): void
    {
        $this->preparer(camillePrivee: false);
        $jeton = $this->jetonDe($this->nora);
        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $jeton);

        $this->json('DELETE', '/api/utilisateurs/camille/abonnement', null, $jeton);
        self::assertResponseStatusCodeSame(204);

        $this->json('DELETE', '/api/utilisateurs/camille/abonnement', null, $jeton);
        self::assertResponseStatusCodeSame(404);
        $this->json('GET', '/api/moi/relations', null, $this->jetonDe($this->camille));
        self::assertSame([], $this->reponse());
    }

    #[Test]
    public function f17_j_ajoute_un_ami_ou_j_eleve_un_abonne(): void
    {
        $this->preparer();
        $jetonCamille = $this->jetonDe($this->camille);
        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $this->jetonDe($this->nora));

        $this->json('POST', '/api/moi/amis', ['pseudo' => 'sophie'], $jetonCamille);
        self::assertResponseStatusCodeSame(201);
        self::assertSame(['sophie', 'camille', 'AMI', 'ACCEPTEE'], [
            $this->reponse()['suiveur']['pseudo'], $this->reponse()['suivi']['pseudo'], $this->reponse()['niveau'], $this->reponse()['statut'],
        ]);

        $this->json('POST', '/api/moi/amis', ['pseudo' => 'sophie'], $jetonCamille);
        self::assertResponseStatusCodeSame(409);

        $this->json('POST', '/api/moi/amis', ['pseudo' => 'nora'], $jetonCamille);
        self::assertResponseStatusCodeSame(201, 'La demande en attente de Nora devient une amitié acceptée.');
        self::assertSame(['AMI', 'ACCEPTEE'], [$this->reponse()['niveau'], $this->reponse()['statut']]);

        $this->json('POST', '/api/moi/amis', ['pseudo' => 'inconnu'], $jetonCamille);
        self::assertResponseStatusCodeSame(404);
        $this->json('POST', '/api/moi/amis', ['pseudo' => 'camille'], $jetonCamille);
        self::assertResponseStatusCodeSame(409, 'I-17');

        $this->json('GET', '/api/moi/relations?niveau=AMI', null, $jetonCamille);
        self::assertSame(['nora', 'sophie'], array_column(array_column($this->reponse(), 'suiveur'), 'pseudo'));
        $this->json('GET', '/api/moi/relations?niveau=ABONNE', null, $jetonCamille);
        self::assertSame([], $this->reponse());
    }

    #[Test]
    public function f18_cas4_un_ami_retrograde_voit_encore_puis_supprime_ne_voit_plus(): void
    {
        $this->preparer();
        $pull = $this->article($this->camille);
        $jetonCamille = $this->jetonDe($this->camille);
        $jetonSophie = $this->jetonDe($this->sophie);
        $this->json('POST', '/api/moi/amis', ['pseudo' => 'sophie'], $jetonCamille);
        $id = $this->reponse()['id'];

        $this->json('PATCH', '/api/relations/'.$id, ['niveau' => 'ABONNE', 'supprimerCommentaires' => true], $jetonCamille);
        self::assertResponseIsSuccessful();
        self::assertSame(['ABONNE', 'ACCEPTEE'], [$this->reponse()['niveau'], $this->reponse()['statut']]);
        $this->json('GET', '/api/articles/'.$pull->getId(), null, $jetonSophie);
        self::assertResponseIsSuccessful('Rétrogradée en abonnée, Sophie voit encore les articles.');

        $this->json('DELETE', '/api/relations/'.$id.'?supprimerCommentaires=false', null, $jetonCamille);
        self::assertResponseStatusCodeSame(204);
        $this->json('GET', '/api/articles/'.$pull->getId(), null, $jetonSophie);
        self::assertResponseStatusCodeSame(404, 'Supprimée : elle voit ce que voit un non-inscrit, rien sur un compte privé.');
    }

    #[Test]
    public function d013_on_suit_une_personne_pas_un_logement(): void
    {
        $this->preparer(leoPrive: true);
        $pullDeCamille = $this->article($this->camille);
        $pullDeLeo = $this->article($this->leo);
        $jetonNora = $this->jetonDe($this->nora);
        $this->json('POST', '/api/moi/amis', ['pseudo' => 'nora'], $this->jetonDe($this->camille));

        $this->json('GET', '/api/articles/'.$pullDeCamille->getId(), null, $jetonNora);
        self::assertResponseIsSuccessful();
        $this->json('GET', '/api/articles/'.$pullDeLeo->getId(), null, $jetonNora);
        self::assertResponseStatusCodeSame(404, 'Même logement, mais Léo est privé et Nora ne le suit pas.');
        $this->json('GET', '/api/utilisateurs/leo/articles', null, $jetonNora);
        self::assertSame([], $this->reponse());
    }

    #[Test]
    public function i18_passer_public_accepte_les_demandes_en_attente(): void
    {
        $this->preparer();
        $jetonCamille = $this->jetonDe($this->camille);
        $this->json('POST', '/api/utilisateurs/camille/abonnement', null, $this->jetonDe($this->nora));

        $this->json('PATCH', '/api/moi', ['estPrive' => false], $jetonCamille);
        self::assertResponseIsSuccessful();

        $this->json('GET', '/api/moi/demandes-abonnement', null, $jetonCamille);
        self::assertSame([], $this->reponse());
        $this->json('GET', '/api/moi/relations?niveau=ABONNE', null, $jetonCamille);
        self::assertSame(['nora'], array_column(array_column($this->reponse(), 'suiveur'), 'pseudo'));
    }

    /** Recharge l'auteur et le rangement : Symfony vide le gestionnaire d'entités après chaque requête. */
    private function article(Utilisateur $auteur): Article
    {
        $pull = $this->categorie('Pull');
        $auteur = $this->em->find(Utilisateur::class, $auteur->getId());
        $armoire = $this->em->find(Stockage::class, $this->armoire->getId());
        $article = Article::creer($auteur, $armoire, $pull, 'photos/test.jpg', new \DateTimeImmutable());
        $this->em->persist($article);
        $this->em->flush();

        return $article;
    }
}
