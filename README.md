# PENDERIE

Application mobile first pour cataloguer ses vêtements, chaussures et objets par logement, pièce et stockage, générer des tenues et partager son dressing.

Projet réalisé dans le cadre du module **WR506D – Développement back avancé** (BUT MMI, IUT de Troyes).

## État du projet

**Phase de cadrage.** Conformément aux règles du module, le dépôt ne contient que de la documentation jusqu'à la validation du pitch 1. Aucun code applicatif n'est commité avant cette date.

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
| [Journal de décisions](docs/journal-decisions.md) | Chaque décision, ses alternatives et la part de l'assistant IA |

## Organisation Git

Le dépôt suit **Gitflow** :

- `main` : toujours déployable, aucun commit direct ;
- `develop` : branche d'intégration, aucun commit direct ;
- `feature/…` : une branche par fonctionnalité, fusionnée dans `develop` par pull request ;
- `hotfix/…` : correctif urgent depuis `main`.

Commits conventionnels (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`), au présent de l'impératif, un commit par intention.

## Installation

Les instructions d'installation (Docker, Symfony) seront ajoutées après la validation du pitch 1.
