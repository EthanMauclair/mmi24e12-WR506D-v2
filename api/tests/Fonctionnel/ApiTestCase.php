<?php

declare(strict_types=1);

namespace App\Tests\Fonctionnel;

use App\Entity\Categorie;
use App\Entity\Utilisateur;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base des tests fonctionnels : des raccourcis pour créer des comptes et appeler l'API en JSON.
 *
 * La base de test (penderie_test) est créée une seule fois par les migrations.
 * DAMADoctrineTestBundle ouvre une transaction avant chaque test et l'annule après :
 * chaque test part donc d'une base vide, sans recréer le schéma.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected const MOT_DE_PASSE = 'motdepasse-de-test';

    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Même noyau (et même connexion) pour toutes les requêtes du test.
        $this->client->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function creerMajeur(string $pseudo, bool $estPrive = false): Utilisateur
    {
        $utilisateur = Utilisateur::creerMajeur($pseudo, $pseudo.'@exemple.fr', new \DateTimeImmutable('-30 years'), $estPrive, new \DateTimeImmutable());

        return $this->enregistrer($utilisateur);
    }

    protected function creerMineur(Utilisateur $referent, string $pseudo): Utilisateur
    {
        $utilisateur = Utilisateur::creerMineur($referent, $pseudo, $pseudo.'@exemple.fr', new \DateTimeImmutable('-12 years'), new \DateTimeImmutable());

        return $this->enregistrer($utilisateur);
    }

    /** Une catégorie de la liste fixe, remplie par la migration de données. */
    protected function categorie(string $nom): Categorie
    {
        return static::getContainer()->get(CategorieRepository::class)->nommee($nom)
            ?? throw new \LogicException(\sprintf('Catégorie « %s » absente : lancer les migrations sur la base de test.', $nom));
    }

    /** Se connecte comme le ferait Postman et renvoie le jeton. */
    protected function jetonDe(Utilisateur $utilisateur): string
    {
        $this->json('POST', '/api/auth/connexion', ['identifiant' => $utilisateur->getEmail(), 'motDePasse' => self::MOT_DE_PASSE]);
        self::assertResponseIsSuccessful();

        return $this->reponse()['jeton'];
    }

    /** @param array<string, mixed>|null $corps */
    protected function json(string $methode, string $url, ?array $corps = null, ?string $jeton = null): void
    {
        $entetes = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if (null !== $jeton) {
            $entetes['HTTP_AUTHORIZATION'] = 'Bearer '.$jeton;
        }
        $this->client->getCookieJar()->clear();
        $this->client->request($methode, $url, [], [], $entetes, null === $corps ? null : json_encode($corps, \JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    protected function reponse(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }

    private function enregistrer(Utilisateur $utilisateur): Utilisateur
    {
        $hacheur = static::getContainer()->get(UserPasswordHasherInterface::class);
        $utilisateur->definirMotDePasseHache($hacheur->hashPassword($utilisateur, self::MOT_DE_PASSE));
        $this->em->persist($utilisateur);
        $this->em->flush();

        return $utilisateur;
    }
}
