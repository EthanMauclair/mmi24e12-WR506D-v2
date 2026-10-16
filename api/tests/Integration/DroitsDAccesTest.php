<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Relation;
use App\Security\AccesArticle;
use App\Security\AccesLogement;
use App\Security\AccesProfil;
use App\Security\ArticleVoter;
use App\Security\LogementVoter;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Le modèle d'autorisation (docs/06) appliqué par les vrais services : AccesLogement, AccesArticle,
 * AccesProfil, les voters et la base. Règle D-014 : ce qu'on ne voit pas répond 404,
 * ce qu'on voit sans pouvoir le modifier répond 403, sans connexion 401.
 */
final class DroitsDAccesTest extends IntegrationTestCase
{
    #[Test]
    public function d014_sans_connexion_aucun_utilisateur_n_est_rendu(): void
    {
        $this->connecter(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->service(AccesLogement::class)->utilisateur();
    }

    #[Test]
    public function d014_sans_connexion_un_logement_est_introuvable(): void
    {
        $logement = $this->logement($this->majeur('camille'));
        $this->connecter(null);

        $this->expectException(NotFoundHttpException::class);
        $this->service(AccesLogement::class)->logement($logement->getId());
    }

    #[Test]
    public function a01_un_membre_accepte_voit_le_logement_ses_pieces_et_ses_rangements(): void
    {
        $camille = $this->majeur('camille');
        $logement = $this->logement($camille);
        $armoire = $this->armoire($logement);
        $this->connecter($camille);
        $acces = $this->service(AccesLogement::class);

        self::assertSame($camille, $acces->utilisateur());
        self::assertSame($logement, $acces->logement($logement->getId()));
        self::assertSame($armoire->getPiece(), $acces->piece($armoire->getPiece()->getId()));
        self::assertSame($armoire, $acces->stockage((string) $armoire->getId()), 'Un identifiant venu de l\'URL est une chaîne.');
    }

    #[Test]
    public function d014_le_logement_d_un_autre_est_introuvable_et_non_interdit(): void
    {
        $logement = $this->logement($this->majeur('camille'));
        $armoire = $this->armoire($logement);
        $this->connecter($this->majeur('voisin'));
        $acces = $this->service(AccesLogement::class);

        foreach ([
            'logement' => fn () => $acces->logement($logement->getId()),
            'pièce' => fn () => $acces->piece($armoire->getPiece()->getId()),
            'rangement' => fn () => $acces->stockage($armoire->getId()),
            'inexistant' => fn () => $acces->logement(999999),
        ] as $cas => $charger) {
            try {
                $charger();
                self::fail($cas.' : doit répondre 404.');
            } catch (NotFoundHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function h13_une_invitation_non_acceptee_ne_donne_pas_acces(): void
    {
        $camille = $this->majeur('camille');
        $leo = $this->majeur('leo');
        $logement = $this->logement($camille);
        $logement->inviter($leo, $camille, new \DateTimeImmutable());
        $this->em->flush();
        $this->connecter($leo);

        $this->expectException(NotFoundHttpException::class);
        $this->service(AccesLogement::class)->logement($logement->getId());
    }

    #[Test]
    public function a11_un_co_proprietaire_mineur_ajoute_mais_ne_renomme_ni_ne_supprime(): void
    {
        $camille = $this->majeur('camille');
        $jules = $this->mineur($camille, 'jules');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $jules);
        $this->connecter($jules);
        $acces = $this->service(AccesLogement::class);

        self::assertSame($logement, $acces->logement($logement->getId(), LogementVoter::AJOUTER));

        $this->expectException(AccessDeniedHttpException::class);
        $acces->piece($this->armoire($logement)->getPiece()->getId(), LogementVoter::MODIFIER);
    }

    #[Test]
    public function a09_seul_le_proprietaire_supprime_le_logement(): void
    {
        $camille = $this->majeur('camille');
        $leo = $this->majeur('leo');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $leo);
        $acces = $this->service(AccesLogement::class);

        $this->connecter($leo);
        self::assertSame($logement, $acces->logement($logement->getId(), LogementVoter::MODIFIER), 'A-10 : un co-propriétaire majeur renomme.');
        try {
            $acces->logement($logement->getId(), LogementVoter::SUPPRIMER);
            self::fail('Un co-propriétaire ne supprime pas le logement.');
        } catch (AccessDeniedHttpException) {
            $this->addToAssertionCount(1);
        }

        $this->connecter($camille);
        $acces->exiger($logement, LogementVoter::SUPPRIMER);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function a01_un_membre_voit_aussi_les_articles_personnels_du_logement(): void
    {
        $camille = $this->majeur('camille', estPrive: true);
        $leo = $this->majeur('leo');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $leo);
        $article = $this->article($camille, $this->armoire($logement), estPersonnel: true);
        $this->connecter($leo);

        self::assertSame($article, $this->service(AccesArticle::class)->article($article->getId()));
    }

    #[Test]
    public function a03_un_visiteur_voit_les_articles_d_un_compte_public_seulement(): void
    {
        $camille = $this->majeur('camille');
        $nora = $this->majeur('nora', estPrive: true);
        $articleDeCamille = $this->article($camille, $this->armoire($this->logement($camille)));
        $articleDeNora = $this->article($nora, $this->armoire($this->logement($nora)));
        $acces = $this->service(AccesArticle::class);

        $this->connecter(null);
        self::assertSame($articleDeCamille, $acces->article($articleDeCamille->getId()), 'Compte public : visible même sans compte.');

        $this->connecter($this->majeur('sophie'));
        $this->expectException(NotFoundHttpException::class);
        $acces->article($articleDeNora->getId());
    }

    #[Test]
    public function a03_seule_une_relation_acceptee_ouvre_les_articles_d_un_compte_prive(): void
    {
        $nora = $this->majeur('nora', estPrive: true);
        $sophie = $this->majeur('sophie');
        $article = $this->article($nora, $this->armoire($this->logement($nora)));
        $relation = Relation::abonner($sophie, $nora, new \DateTimeImmutable());
        $this->em->persist($relation);
        $this->em->flush();
        $this->connecter($sophie);
        $acces = $this->service(AccesArticle::class);

        try {
            $acces->article($article->getId());
            self::fail('I-18 : une demande en attente ne donne aucun droit.');
        } catch (NotFoundHttpException) {
            $this->addToAssertionCount(1);
        }

        $relation->accepter();
        $this->em->flush();
        self::assertSame($article, $acces->article($article->getId()));
    }

    #[Test]
    public function a04_un_article_personnel_reste_invisible_hors_du_logement_meme_pour_un_abonne(): void
    {
        $camille = $this->majeur('camille');
        $sophie = $this->majeur('sophie');
        $article = $this->article($camille, $this->armoire($this->logement($camille)), estPersonnel: true);
        $this->em->persist(Relation::abonner($sophie, $camille, new \DateTimeImmutable()));
        $this->em->flush();
        $this->connecter($sophie);

        $this->expectException(NotFoundHttpException::class);
        $this->service(AccesArticle::class)->article($article->getId());
    }

    #[Test]
    public function a16_les_articles_d_un_mineur_restent_caches_au_public(): void
    {
        $camille = $this->majeur('camille');
        $jules = $this->mineur($camille, 'jules');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $jules);
        $article = $this->article($jules, $this->armoire($logement));
        $this->connecter($this->majeur('inconnu'));

        $this->expectException(NotFoundHttpException::class);
        $this->service(AccesArticle::class)->article($article->getId());
    }

    #[Test]
    public function a10_a11_un_mineur_ne_modifie_que_ses_propres_articles(): void
    {
        $camille = $this->majeur('camille');
        $jules = $this->mineur($camille, 'jules');
        $logement = $this->logement($camille);
        $this->rejoindre($logement, $jules);
        $armoire = $this->armoire($logement);
        $articleDeJules = $this->article($jules, $armoire);
        $articleDeCamille = $this->article($camille, $armoire);
        $acces = $this->service(AccesArticle::class);

        $this->connecter($camille);
        self::assertSame($articleDeJules, $acces->article($articleDeJules->getId(), ArticleVoter::MODIFIER), 'A-10 : un majeur modifie tout le logement.');

        $this->connecter($jules);
        self::assertSame($articleDeJules, $acces->article($articleDeJules->getId(), ArticleVoter::MODIFIER));
        $this->expectException(AccessDeniedHttpException::class);
        $acces->article($articleDeCamille->getId(), ArticleVoter::MODIFIER);
    }

    #[Test]
    public function s01_un_identifiant_d_article_non_numerique_est_introuvable(): void
    {
        $this->connecter($this->majeur('camille'));

        $this->expectException(NotFoundHttpException::class);
        $this->service(AccesArticle::class)->article('1 OR 1=1');
    }

    #[Test]
    public function s13_on_ne_range_pas_un_article_chez_les_autres_et_rien_n_est_revele(): void
    {
        $camille = $this->majeur('camille');
        $armoireDeCamille = $this->armoire($this->logement($camille));
        $voisin = $this->majeur('voisin');
        $armoireDuVoisin = $this->armoire($this->logement($voisin, 'Studio'));
        $this->connecter($voisin);
        $acces = $this->service(AccesArticle::class);

        self::assertSame($armoireDuVoisin, $acces->rangement($armoireDuVoisin->getId()));
        foreach ([$armoireDeCamille->getId(), 999999] as $id) {
            try {
                $acces->rangement($id);
                self::fail('Rangement d\'un autre ou inexistant : 403 dans les deux cas.');
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function a03_le_profil_suit_la_meme_regle_que_la_liste_des_articles(): void
    {
        $nora = $this->majeur('nora', estPrive: true);
        $camille = $this->majeur('camille');
        $sophie = $this->majeur('sophie');
        $relation = Relation::abonner($sophie, $nora, new \DateTimeImmutable());
        $this->em->persist($relation);
        $this->em->flush();
        $acces = $this->service(AccesProfil::class);

        $this->connecter(null);
        self::assertTrue($acces->voitLesArticlesDe($camille), 'Compte public.');
        self::assertFalse($acces->voitLesArticlesDe($nora), 'Compte privé, sans compte.');

        $this->connecter($nora);
        self::assertTrue($acces->voitLesArticlesDe($nora), 'Soi-même.');

        $this->connecter($sophie);
        self::assertFalse($acces->voitLesArticlesDe($nora), 'Demande en attente.');
        $relation->accepter();
        $this->em->flush();
        self::assertTrue($acces->voitLesArticlesDe($nora), 'Abonnée acceptée.');
    }
}
