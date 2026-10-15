<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Fournisseur\PenderieFournisseur;
use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\Logement;
use App\Entity\Relation;
use App\Entity\Stockage;
use App\Entity\Utilisateur;
use App\Repository\CategorieRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de démonstration (séance 16, pitch 2) : make fixtures.
 *
 * - Les personnes des parcours (docs/04-parcours-utilisateurs.md) : Camille (propriétaire, publique),
 *   Léo (co-propriétaire), Jules (mineur, référent Camille), Sophie (amie), Nora (abonnée).
 * - 20 autres comptes, chacun avec son logement, et des abonnements entre eux.
 * - 1 000 articles au total, avec une vraie photo dans le stockage.
 *
 * Graine fixe : deux chargements donnent les mêmes données, et un test qui échoue se rejoue.
 * Tous les comptes ont le mot de passe MOT_DE_PASSE (données de démonstration, jamais en production).
 */
final class AppFixtures extends Fixture
{
    public const MOT_DE_PASSE = 'penderie-demo';
    public const NOMBRE_ARTICLES = 1000;
    private const GRAINE = 2026;
    private const NOMBRE_COMPTES_GENERES = 20;

    /** Pièces possibles et leurs rangements. */
    private const PIECES = [
        'Chambre' => ['Grande armoire', 'Commode', 'Table de nuit'],
        'Entrée' => ['Penderie', 'Meuble à chaussures'],
        'Salon' => ['Étagère', 'Buffet'],
        'Salle de bain' => ['Placard'],
        'Dressing' => ['Portant', 'Tiroirs', 'Boîtes'],
        'Bureau' => ['Bibliothèque'],
        'Cave' => ['Cartons'],
    ];

    /** Où l'on range quoi : famille de catégorie => rangements préférés (sinon : n'importe lequel). */
    private const RANGEMENTS_PREFERES = [
        'vetement' => ['Grande armoire', 'Commode', 'Penderie', 'Portant', 'Tiroirs'],
        'accessoire' => ['Penderie', 'Portant', 'Boîtes', 'Commode'],
        'chaussure' => ['Meuble à chaussures', 'Penderie', 'Boîtes'],
        'exterieur' => ['Penderie', 'Portant', 'Grande armoire'],
        'livre' => ['Bibliothèque', 'Étagère', 'Cartons'],
        'vinyle' => ['Étagère', 'Buffet', 'Bibliothèque'],
        'jeu' => ['Buffet', 'Étagère'],
        'lingerie' => ['Commode', 'Tiroirs'],
        'bijou' => ['Table de nuit', 'Commode', 'Boîtes'],
        'montre' => ['Table de nuit', 'Boîtes'],
        'lunettes' => ['Table de nuit', 'Placard'],
    ];

    private Generator $faker;
    private PenderieFournisseur $penderie;
    private \DateTimeImmutable $maintenant;
    private string $motDePasseHache = '';
    /** @var array<string, Categorie> */
    private array $categories = [];

    public function __construct(
        private readonly UserPasswordHasherInterface $hacheur,
        private readonly CategorieRepository $categoriesRepository,
        private readonly PhotoFactice $photos,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->faker = Factory::create('fr_FR');
        $this->penderie = new PenderieFournisseur($this->faker);
        $this->faker->addProvider($this->penderie);
        $this->faker->seed(self::GRAINE);
        $this->maintenant = new \DateTimeImmutable();
        $this->chargerCategories();
        $this->photos->viderLeStockage();

        // --- Les personnes des parcours ---
        $camille = $this->majeur($manager, 'camille', estPrive: false);
        $leo = $this->majeur($manager, 'leo', estPrive: false);
        $sophie = $this->majeur($manager, 'sophie', estPrive: true);
        $nora = $this->majeur($manager, 'nora', estPrive: false);
        $jules = Utilisateur::creerMineur($camille, 'jules', 'jules@exemple.fr', new \DateTimeImmutable('-15 years'), $this->maintenant);
        $this->enregistrer($manager, $jules);

        $appartement = $this->logement($manager, 'Appartement Lyon', $camille, ['Chambre', 'Entrée', 'Salon', 'Salle de bain', 'Dressing']);
        $appartement->inviter($leo, $camille, $this->maintenant)->accepter($this->maintenant);
        $appartement->inviter($jules, $camille, $this->maintenant)->accepter($this->maintenant);
        $vacances = $this->logement($manager, 'Maison de vacances', $camille, ['Chambre', 'Cave']);
        $studio = $this->logement($manager, 'Studio Croix-Rousse', $sophie, ['Chambre', 'Entrée']);
        $villeurbanne = $this->logement($manager, 'Appartement Villeurbanne', $nora, ['Chambre', 'Entrée', 'Bureau']);

        // Nora suit Camille (compte public : acceptée d'office) ; Sophie est son amie (F-17).
        $manager->persist(Relation::abonner($nora, $camille, $this->maintenant));
        $manager->persist(Relation::ajouterAmi($camille, $sophie, $this->maintenant));
        $manager->flush();

        // --- Les autres comptes ---
        /** @var list<array{Utilisateur, Logement}> $autres */
        $autres = [];
        for ($i = 0; $i < self::NOMBRE_COMPTES_GENERES; ++$i) {
            $personne = $this->majeur($manager, $this->pseudo(), estPrive: $this->faker->boolean(40));
            $pieces = $this->faker->randomElements(array_keys(self::PIECES), $this->faker->numberBetween(2, 4));
            $autres[] = [$personne, $this->logement($manager, 'Chez '.ucfirst($personne->getPseudo()), $personne, $pieces)];
        }
        $manager->flush();
        $this->abonnements($manager, array_merge([$camille, $nora, $sophie], array_column($autres, 0)));

        // --- Les articles : Camille est la plus fournie (« beaucoup trop pour s'y retrouver ») ---
        $repartition = [
            [$camille, $appartement, 260], [$camille, $vacances, 40], [$leo, $appartement, 80],
            [$jules, $appartement, 40], [$sophie, $studio, 60], [$nora, $villeurbanne, 50],
        ];
        $reste = self::NOMBRE_ARTICLES - array_sum(array_column($repartition, 2));
        foreach ($autres as $index => [$personne, $logement]) {
            // Le reste est partagé entre les comptes générés, le dernier prenant ce qui manque.
            $part = $index === \count($autres) - 1 ? $reste : min($reste, $this->faker->numberBetween(10, 35));
            $reste -= $part;
            $repartition[] = [$personne, $logement, $part];
        }

        $numero = 0;
        foreach ($repartition as [$auteur, $logement, $nombre]) {
            for ($i = 0; $i < $nombre; ++$i) {
                $this->article($manager, $auteur, $logement, ++$numero);
            }
            $manager->flush();
        }
    }

    private function majeur(ObjectManager $manager, string $pseudo, bool $estPrive): Utilisateur
    {
        $naissance = \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-60 years', '-19 years'));
        $utilisateur = Utilisateur::creerMajeur($pseudo, $pseudo.'@exemple.fr', $naissance, $estPrive, $this->maintenant);

        return $this->enregistrer($manager, $utilisateur);
    }

    private function enregistrer(ObjectManager $manager, Utilisateur $utilisateur): Utilisateur
    {
        // Un seul hachage pour tous : le mot de passe est le même, et hacher coûte volontairement cher.
        if ('' === $this->motDePasseHache) {
            $this->motDePasseHache = $this->hacheur->hashPassword($utilisateur, self::MOT_DE_PASSE);
        }
        $utilisateur->definirMotDePasseHache($this->motDePasseHache);
        $manager->persist($utilisateur);

        return $utilisateur;
    }

    /** Pseudo au format accepté par l'inscription : minuscules, chiffres, « _ » et « . », 3 à 30 caractères. */
    private function pseudo(): string
    {
        $base = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT', $this->faker->unique()->firstName().'.'.$this->faker->lastName()));

        return substr((string) preg_replace('/[^a-z0-9_.]/', '', $base), 0, 30);
    }

    /** @param list<string> $pieces */
    private function logement(ObjectManager $manager, string $nom, Utilisateur $proprietaire, array $pieces): Logement
    {
        $logement = Logement::creer($nom, $proprietaire, $this->maintenant);
        foreach ($pieces as $nomPiece) {
            $piece = $logement->ajouterPiece($nomPiece);
            foreach (self::PIECES[$nomPiece] as $rangement) {
                $piece->ajouterStockage($rangement);
            }
        }
        $manager->persist($logement);

        return $logement;
    }

    /**
     * Chacun suit 2 à 6 autres personnes. Compte public : acceptée d'office (I-18) ;
     * compte privé : la personne suivie accepte deux demandes sur trois, les autres restent en attente.
     *
     * @param list<Utilisateur> $personnes
     */
    private function abonnements(ObjectManager $manager, array $personnes): void
    {
        $existantes = ['nora>camille' => true, 'sophie>camille' => true];
        foreach ($personnes as $suiveur) {
            $candidats = array_values(array_filter($personnes, static fn (Utilisateur $p) => $p !== $suiveur));
            foreach ($this->faker->randomElements($candidats, $this->faker->numberBetween(2, 6)) as $suivi) {
                $cle = $suiveur->getPseudo().'>'.$suivi->getPseudo();
                if (isset($existantes[$cle])) {
                    continue;
                }
                $existantes[$cle] = true;
                $relation = Relation::abonner($suiveur, $suivi, $this->maintenant);
                if ($suivi->estPrive() && $this->faker->boolean(66)) {
                    $relation->accepter();
                }
                $manager->persist($relation);
            }
        }
        $manager->flush();
    }

    private function article(ObjectManager $manager, Utilisateur $auteur, Logement $logement, int $numero): void
    {
        $nomCategorie = $this->penderie->categorieArticle();
        $couleur = $this->penderie->couleurArticle($nomCategorie);
        $creeLe = \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-18 months'));

        $photo = $this->photos->creer((int) $auteur->getId(), $nomCategorie, $couleur, self::GRAINE.'-'.$numero);
        $article = Article::creer($auteur, $this->rangement($logement, $nomCategorie), $this->categories[$nomCategorie], $photo, $creeLe);
        $article->decrire($this->categories[$nomCategorie], $this->penderie->marqueArticle($nomCategorie), $couleur, $this->penderie->noteArticle());
        // Quelques articles personnels (montre de luxe, bijoux…) : 1 sur 20 (A-04).
        $article->marquerPersonnel($this->faker->boolean(5));
        $manager->persist($article);
    }

    /** Un rangement plausible pour la catégorie s'il existe dans le logement, sinon un rangement au hasard. */
    private function rangement(Logement $logement, string $categorie): Stockage
    {
        $tous = [];
        foreach ($logement->getPieces() as $piece) {
            foreach ($piece->getStockages() as $stockage) {
                $tous[] = $stockage;
            }
        }
        $preferes = array_values(array_filter(
            $tous,
            static fn (Stockage $s) => \in_array($s->getNom(), self::RANGEMENTS_PREFERES[PenderieFournisseur::famille($categorie)] ?? [], true),
        ));

        return $this->faker->randomElement([] !== $preferes ? $preferes : $tous);
    }

    private function chargerCategories(): void
    {
        foreach (PenderieFournisseur::categories() as $nom) {
            $this->categories[$nom] = $this->categoriesRepository->nommee($nom)
                ?? throw new \LogicException(\sprintf('Catégorie « %s » absente : lancer les migrations (make migrer).', $nom));
        }
    }
}
