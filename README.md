# PENDERIE

Application mobile first pour cataloguer ses vêtements, chaussures et objets par logement, pièce et stockage, générer des tenues et partager son dressing.

Projet réalisé dans le cadre du module **WR506D – Développement back avancé** (BUT MMI, IUT de Troyes).

## État du projet

**Phase de construction.** Le cadrage a été validé au pitch 1 ; le socle technique est en place (Docker, API Symfony, front Nuxt).

## Documentation

| Document | Contenu |
|---|---|
| [Compte rendu de lancement](docs/01-compte-rendu-lancement.md) | Ce que le client a dit, ce qu'on en déduit, ce qui reste ouvert |
| [Échanges client](docs/02-echanges-client.md) | Questions posées au client, séance par séance, et questions en attente |
| [Périmètre de la V1](docs/03-perimetre-v1.md) | Fonctions retenues, exclusions justifiées, hypothèses |
| [Parcours utilisateurs](docs/04-parcours-utilisateurs.md) | Les principaux parcours de la V1, du point de vue de la personne |
| [Modèle de données](docs/05-modele-donnees.md) | Entités, schéma, invariants, règle de visibilité |
| [Modèle d'autorisation](docs/06-modele-autorisation.md) | Qui voit quoi, qui modifie quoi, tests de sécurité |
| [Choix techniques](docs/07-choix-techniques.md) | Symfony, front, base de données, stockage, IA : chaque choix justifié |
| [Contrat d'API](docs/08-contrat-api.md) | Conventions, correspondance avec les fonctions, fichier [OpenAPI](docs/api/openapi.yaml) |
| [Maquettes](docs/09-maquettes.md) | Les écrans principaux en mobile, et les choix visuels |
| [Journal de décisions](docs/journal-decisions.md) | Chaque décision, ses alternatives et la part de l'assistant IA |

## Organisation Git

Le dépôt suit **Gitflow** :

- `main` : toujours déployable, aucun commit direct ;
- `develop` : branche d'intégration, aucun commit direct ;
- `feature/…` : une branche par fonctionnalité, fusionnée dans `develop` par pull request ;
- `hotfix/…` : correctif urgent depuis `main`.

Commits conventionnels (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`), au présent de l'impératif, un commit par intention.

## Installation

### Prérequis

- **Docker Desktop** (Windows, macOS) ou Docker Engine avec Compose (Linux).
- **Git**.

Rien d'autre : PHP, Composer, MySQL et Node tournent dans des conteneurs.

### Première installation

Depuis la racine du dépôt :

```bash
# 1. Construire l'image PHP
docker compose build php

# 2. Installer les dépendances de l'API (dans le volume Docker api_vendor)
docker compose run --rm --no-deps php composer install

# 3. Créer le fichier des secrets locaux (jamais commité)
#    puis y mettre deux valeurs aléatoires :
#      APP_SECRET=<32 caractères hexadécimaux>
#      JWT_PASSPHRASE=<une phrase secrète>
#    (fichier : api/.env.local)

#    Copier aussi ces deux valeurs dans api/.env.test.local (pour les tests)

# 4. Générer les clés de signature des jetons JWT (stockées dans api/config/jwt/, jamais commitées)
docker compose run --rm --no-deps php php bin/console lexik:jwt:generate-keypair

# 5. Démarrer l'API et la base de données
docker compose up -d

# 6. Créer la base de données et appliquer les migrations
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### Lancer le projet

```bash
docker compose up -d                    # API, base de données, e-mails
docker compose --profile front up -d    # en plus : le front Nuxt
docker compose down                     # tout arrêter
```

| Service | Adresse |
|---|---|
| API (documentation interactive) | http://localhost:8080/api |
| Front Nuxt | http://localhost:3000 |
| E-mails de test (Mailpit) | http://localhost:8025 |
| MySQL (depuis la machine hôte) | `localhost:3307`, utilisateur `penderie` |

Les mots de passe de `compose.yaml` ne servent qu'en développement local.

### Dossiers gardés dans Docker

`api/vendor/`, `api/var/` (cache, journaux de tests, photos) et `front/node_modules/` sont des **volumes Docker**, pas des dossiers du dépôt : sous Windows, le partage de fichiers rendait chaque requête de l'API 50 fois plus lente (plus de 10 s). Conséquences :

- les dépendances s'installent toujours dans les conteneurs (`docker compose exec php composer …`, `docker compose exec front npm …`) ; les dossiers `vendor/` ou `node_modules/` éventuellement présents sur la machine ne sont pas utilisés ;
- pour lire un fichier de `var/`, passer par le conteneur : `docker compose exec php cat var/phpunit.txt` ;
- `docker compose down -v` supprime ces volumes (et la base) : il faut alors relancer `composer install`.

Le conteneur PHP tourne avec l'utilisateur `www-data`, celui de PHP-FPM : les fichiers créés en ligne de commande restent modifiables par l'application.

### Tests

```bash
# Une seule fois : créer les tables de la base de test
docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Lancer tous les tests (unitaires et fonctionnels)
docker compose exec php php bin/phpunit
```

Les tests utilisent la base `penderie_test` et lisent leurs secrets dans `api/.env.test.local` (non commité, mêmes valeurs que `api/.env.local`).

### Qualité et raccourcis (Makefile)

Les commandes courantes sont regroupées dans le [Makefile](Makefile) ; `make` seul les liste.

```bash
make qualite   # tout vérifier, comme la CI : linters, PHPStan, style, audit des dépendances, tests
make phpstan   # analyse statique, niveau 6
make cs        # style du code PHP (make cs-corriger pour corriger)
make tests     # PHPUnit
```

Sous Windows, `make` n'est pas installé d'office : `winget install ezwinports.make`, puis rouvrir le terminal. Sans `make`, chaque commande du Makefile peut être copiée telle quelle.

Résultats et analyse : [audit.md](audit.md).

### Versions

| Outil | Version |
|---|---|
| PHP | 8.4 |
| Symfony | 7.4 LTS |
| API Platform | 5.0 |
| MySQL | 8.4 |
| Node / Nuxt | 22 / 4 |
