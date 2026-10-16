<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Article;
use App\Entity\Relation;
use App\Enum\NiveauRelation;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Repository\MembreRepository;
use App\Repository\RelationRepository;
use App\Repository\UtilisateurRepository;
use PHPUnit\Framework\Attributes\Test;

/**
 * Les requêtes des dépôts, contre MySQL : ce qu'un test unitaire ne peut pas vérifier
 * (jointures, interclassement sans casse ni accents, échappement de LIKE, tri, comptage).
 */
final class DepotsTest extends IntegrationTestCase
{
    #[Test]
    public function f26_a01_la_recherche_ne_parcourt_que_les_logements_dont_je_suis_membre_accepte(): void
    {
        $camille = $this->majeur('camille');
        $leo = $this->majeur('leo');
        $invite = $this->majeur('invite');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $leo);
        $logement->inviter($invite, $camille, new \DateTimeImmutable());
        $this->em->flush();
        $pull = $this->article($camille, $this->armoire($logement), estPersonnel: true);
        $this->article($invite, $this->armoire($this->logement($invite, 'Studio')));
        $articles = $this->service(ArticleRepository::class);

        self::assertSame([$pull], $articles->rechercher($leo), 'Un co-propriétaire voit tout le logement, articles personnels compris.');
        self::assertCount(1, $articles->rechercher($invite), 'Une invitation non acceptée n\'ouvre pas le logement.');
        self::assertSame([], $articles->rechercher($this->majeur('voisin')));
    }

    #[Test]
    public function f26_le_texte_est_cherche_dans_la_marque_la_couleur_et_la_note_sans_casse_ni_accents(): void
    {
        $camille = $this->majeur('camille');
        $armoire = $this->armoire($this->logement($camille));
        $parMarque = $this->article($camille, $armoire, marque: 'Kiabi');
        $parCouleur = $this->article($camille, $armoire, couleur: 'Bleu ciel');
        $parNote = $this->article($camille, $armoire, note: 'Offert pour l\'été');
        $this->article($camille, $armoire, marque: 'Zara', couleur: 'Noir', note: 'Hiver');
        $articles = $this->service(ArticleRepository::class);

        self::assertSame([$parMarque], $articles->rechercher($camille, texte: 'KIA'));
        self::assertSame([$parCouleur], $articles->rechercher($camille, texte: 'bleu'));
        self::assertSame([$parNote], $articles->rechercher($camille, texte: 'ETE'), 'Sans accents : « ETE » retrouve « été ».');
    }

    #[Test]
    public function f26_les_jokers_de_like_tapes_par_l_utilisateur_sont_cherches_tels_quels(): void
    {
        $camille = $this->majeur('camille');
        $armoire = $this->armoire($this->logement($camille));
        $solde = $this->article($camille, $armoire, note: 'Soldé à -50%');
        $this->article($camille, $armoire, note: 'Prix plein');
        $this->article($camille, $armoire, note: 'Tee_shirt');
        $articles = $this->service(ArticleRepository::class);

        self::assertSame([$solde], $articles->rechercher($camille, texte: '%'), '« % » ne veut pas dire « tout ».');
        self::assertCount(1, $articles->rechercher($camille, texte: '_'), '« _ » ne veut pas dire « un caractère ».');
    }

    #[Test]
    public function f26_chaque_filtre_restreint_le_resultat(): void
    {
        $camille = $this->majeur('camille');
        $appartement = $this->logement($camille);
        $armoire = $this->armoire($appartement);
        $commode = $armoire->getPiece()->ajouterStockage('Commode');
        $entree = $appartement->ajouterPiece('Entrée')->ajouterStockage('Porte-manteau');
        $this->em->flush();
        $maison = $this->armoire($this->logement($camille, 'Maison'));
        $pullRouge = $this->article($camille, $armoire, marque: 'Zara', couleur: 'Rouge');
        $pullNoir = $this->article($camille, $commode, marque: 'Kiabi', couleur: 'Noir');
        $veste = $this->article($camille, $entree, couleur: 'Noir', categorie: 'Veste');
        $echarpe = $this->article($camille, $maison, categorie: 'Écharpe');
        $articles = $this->service(ArticleRepository::class);

        self::assertCount(4, $articles->rechercher($camille));
        self::assertEqualsCanonicalizing([$pullRouge, $pullNoir, $veste], $articles->rechercher($camille, logement: $appartement->getId()));
        self::assertEqualsCanonicalizing([$pullRouge, $pullNoir], $articles->rechercher($camille, piece: $armoire->getPiece()->getId()));
        self::assertSame([$pullNoir], $articles->rechercher($camille, stockage: $commode->getId()));
        self::assertSame([$veste], $articles->rechercher($camille, categorie: $this->categorie('Veste')->getId()));
        self::assertEqualsCanonicalizing([$pullNoir, $veste], $articles->rechercher($camille, couleur: 'noir'));
        self::assertSame([$pullRouge], $articles->rechercher($camille, marque: 'zara'));
        self::assertSame([$echarpe], $articles->rechercher($camille, logement: $maison->getLogement()->getId(), categorie: $this->categorie('Écharpe')->getId()));
        self::assertSame([], $articles->rechercher($camille, couleur: 'noir', marque: 'zara'), 'Les filtres se cumulent.');
    }

    #[Test]
    public function f21_f04_le_profil_ne_liste_ni_ne_compte_les_articles_personnels(): void
    {
        $camille = $this->majeur('camille');
        $appartement = $this->logement($camille);
        $maison = $this->logement($camille, 'Maison');
        $pull = $this->article($camille, $this->armoire($appartement));
        $veste = $this->article($camille, $this->armoire($maison), categorie: 'Veste');
        $this->article($camille, $this->armoire($appartement), estPersonnel: true);
        $articles = $this->service(ArticleRepository::class);

        self::assertEqualsCanonicalizing([$pull, $veste], $articles->visiblesDe($camille));
        self::assertSame(2, $articles->nombreVisiblesDe($camille), 'Le compteur ne révèle pas ce que la liste cache.');
        self::assertSame([$pull], $articles->visiblesDe($camille, logement: $appartement->getId()));
        self::assertSame([$veste], $articles->visiblesDe($camille, categorie: $this->categorie('Veste')->getId()));
        self::assertSame([], $articles->visiblesDe($camille, page: 2));
    }

    #[Test]
    public function f07_une_photo_ne_sert_qu_a_un_seul_article(): void
    {
        $camille = $this->majeur('camille');
        $article = $this->article($camille, $this->armoire($this->logement($camille)));
        $articles = $this->service(ArticleRepository::class);

        self::assertTrue($articles->photoDejaUtilisee($article->getPhoto()));
        self::assertFalse($articles->photoDejaUtilisee('photos/1/inconnue.jpg'));
        self::assertInstanceOf(Article::class, $articles->find($article->getId()));
    }

    #[Test]
    public function a03_f16_les_relations_distinguent_demandes_abonnes_et_amis(): void
    {
        $nora = $this->majeur('nora', estPrive: true);
        $zoe = $this->majeur('zoe');
        $alice = $this->majeur('alice');
        $sophie = $this->majeur('sophie');
        $demandeAncienne = Relation::abonner($zoe, $nora, new \DateTimeImmutable('2026-01-01'));
        $demandeRecente = Relation::abonner($alice, $nora, new \DateTimeImmutable('2026-02-01'));
        $amie = Relation::ajouterAmi($nora, $sophie, new \DateTimeImmutable());
        foreach ([$demandeRecente, $demandeAncienne, $amie] as $relation) {
            $this->em->persist($relation);
        }
        $this->em->flush();
        $relations = $this->service(RelationRepository::class);

        self::assertSame([$demandeAncienne, $demandeRecente], $relations->demandesVers($nora), 'F-16 : les plus anciennes d\'abord.');
        self::assertSame($demandeAncienne, $relations->entre($zoe, $nora));
        self::assertNull($relations->entre($nora, $zoe), 'Une relation a un sens.');
        self::assertFalse($relations->estAcceptee($zoe, $nora), 'I-18 : en attente vers un compte privé.');
        self::assertTrue($relations->estAcceptee($sophie, $nora));
        self::assertSame(1, $relations->nombreAbonnes($nora), 'F-04 : les demandes en attente ne comptent pas.');

        $demandeRecente->accepter();
        $this->em->flush();
        self::assertSame([$alice, $sophie], array_map(static fn (Relation $r) => $r->getSuiveur(), $relations->accepteesVers($nora)), 'Par pseudo.');
        self::assertSame([$amie], $relations->accepteesVers($nora, NiveauRelation::Ami));
        self::assertSame([$demandeRecente], $relations->accepteesVers($nora, NiveauRelation::Abonne));
    }

    #[Test]
    public function i04_f11_les_adhesions_distinguent_proprietaire_co_proprietaire_et_invite(): void
    {
        $camille = $this->majeur('camille');
        $leo = $this->majeur('leo');
        $appartement = $this->logement($camille, 'Appartement');
        $bureau = $this->logement($camille, 'Bureau');
        $this->rejoindre($appartement, $leo);
        $invitation = $bureau->inviter($leo, $camille, new \DateTimeImmutable());
        $this->em->flush();
        $membres = $this->service(MembreRepository::class);

        self::assertTrue($membres->estProprietaireDUnLogement($camille), 'I-04 : son compte ne peut pas être supprimé.');
        self::assertFalse($membres->estProprietaireDUnLogement($leo), 'Un co-propriétaire peut supprimer son compte.');
        self::assertSame(['Appartement', 'Bureau'], array_map(static fn ($m) => $m->getLogement()->getNom(), $membres->adhesionsAcceptees($camille)));
        self::assertSame(['Appartement'], array_map(static fn ($m) => $m->getLogement()->getNom(), $membres->adhesionsAcceptees($leo)));
        self::assertSame([$invitation], $membres->invitationsEnAttente($leo));
    }

    #[Test]
    public function f03_on_se_connecte_par_pseudo_ou_par_e_mail_sans_tenir_compte_de_la_casse(): void
    {
        $camille = $this->majeur('camille');
        $utilisateurs = $this->service(UtilisateurRepository::class);

        self::assertSame($camille, $utilisateurs->loadUserByIdentifier('camille'));
        self::assertSame($camille, $utilisateurs->loadUserByIdentifier('  Camille@Exemple.fr '));
        self::assertNull($utilisateurs->loadUserByIdentifier('inconnue'));
        self::assertTrue($utilisateurs->pseudoOuEmailExiste('camille', 'neuve@exemple.fr'));
        self::assertTrue($utilisateurs->pseudoOuEmailExiste('neuve', 'CAMILLE@exemple.fr'));
        self::assertFalse($utilisateurs->pseudoOuEmailExiste('neuve', 'neuve@exemple.fr'));
    }

    #[Test]
    public function d021_les_categories_sont_triees_et_retrouvees_sans_casse_ni_accents(): void
    {
        $categories = $this->service(CategorieRepository::class);
        $noms = array_map(static fn ($c) => $c->getNom(), $categories->parNom());

        self::assertCount(31, $noms, 'La liste fixe remplie par la migration.');
        self::assertLessThan(array_search('Jean', $noms, true), array_search('Écharpe', $noms, true), 'Tri sans accents : « É » se range avec les « E ».');
        self::assertLessThan(array_search('Veste', $noms, true), array_search('Pull', $noms, true));
        self::assertSame('Écharpe', $categories->nommee('echarpe')?->getNom());
        self::assertSame('Vinyle', $categories->nommee('VINYLE')?->getNom());
        self::assertNull($categories->nommee('Fusée'));
    }
}
