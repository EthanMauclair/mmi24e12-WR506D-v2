<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\Logement;
use App\Entity\Stockage;
use App\Entity\Utilisateur;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Tests d'intégration : Symfony démarré, vrais services, vraie base de test (remise à zéro après
 * chaque test par DAMADoctrineTestBundle), sans requête HTTP.
 *
 * Chaque test crée exactement les données dont il a besoin ; aucun ne dépend des fixtures.
 */
abstract class IntegrationTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = $this->service(EntityManagerInterface::class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $classe
     *
     * @return T
     */
    protected function service(string $classe): object
    {
        $service = static::getContainer()->get($classe);
        \assert($service instanceof $classe);

        return $service;
    }

    protected function majeur(string $pseudo, bool $estPrive = false): Utilisateur
    {
        return $this->enregistrer(Utilisateur::creerMajeur($pseudo, $pseudo.'@exemple.fr', new \DateTimeImmutable('-30 years'), $estPrive, new \DateTimeImmutable()));
    }

    protected function mineur(Utilisateur $referent, string $pseudo): Utilisateur
    {
        return $this->enregistrer(Utilisateur::creerMineur($referent, $pseudo, $pseudo.'@exemple.fr', new \DateTimeImmutable('-12 years'), new \DateTimeImmutable()));
    }

    /** Un logement avec une pièce « Chambre » et un rangement « Armoire ». */
    protected function logement(Utilisateur $proprietaire, string $nom = 'Appartement'): Logement
    {
        $logement = Logement::creer($nom, $proprietaire, new \DateTimeImmutable());
        $logement->ajouterPiece('Chambre')->ajouterStockage('Armoire');
        $this->em->persist($logement);
        $this->em->flush();

        return $logement;
    }

    protected function armoire(Logement $logement): Stockage
    {
        $piece = $logement->getPieces()->first();
        \assert(false !== $piece);
        $stockage = $piece->getStockages()->first();
        \assert($stockage instanceof Stockage);

        return $stockage;
    }

    /** Invitation acceptée (F-11, H13) ; $arrivee fixe l'ordre d'arrivée, utile pour I-09. */
    protected function rejoindre(Logement $logement, Utilisateur $invite, ?\DateTimeImmutable $arrivee = null): void
    {
        $logement->inviter($invite, $logement->getProprietaire(), new \DateTimeImmutable())
            ->accepter($arrivee ?? new \DateTimeImmutable());
        $this->em->flush();
    }

    protected function article(
        Utilisateur $auteur,
        Stockage $stockage,
        ?string $marque = null,
        ?string $couleur = null,
        ?string $note = null,
        bool $estPersonnel = false,
        string $categorie = 'Pull',
    ): Article {
        $categorie = $this->categorie($categorie);
        $article = Article::creer($auteur, $stockage, $categorie, \sprintf('photos/%s.jpg', bin2hex(random_bytes(8))), new \DateTimeImmutable());
        $article->decrire($categorie, $marque, $couleur, $note);
        $article->marquerPersonnel($estPersonnel);
        $this->em->persist($article);
        $this->em->flush();

        return $article;
    }

    /** Une catégorie de la liste fixe, remplie par la migration de données. */
    protected function categorie(string $nom): Categorie
    {
        return $this->service(CategorieRepository::class)->nommee($nom)
            ?? throw new \LogicException(\sprintf('Catégorie « %s » absente : lancer les migrations sur la base de test.', $nom));
    }

    /** Se met à la place de cette personne, comme après une connexion ; null = visiteur sans compte. */
    protected function connecter(?Utilisateur $utilisateur): void
    {
        $this->service(TokenStorageInterface::class)->setToken(
            null === $utilisateur ? null : new UsernamePasswordToken($utilisateur, 'api', $utilisateur->getRoles()),
        );
    }

    /** Oublie tout ce que Doctrine garde en mémoire : la suite du test lit ce qui est vraiment en base. */
    protected function relire(): void
    {
        $this->em->clear();
    }

    private function enregistrer(Utilisateur $utilisateur): Utilisateur
    {
        $utilisateur->definirMotDePasseHache('inutile-ici');
        $this->em->persist($utilisateur);
        $this->em->flush();

        return $utilisateur;
    }
}
