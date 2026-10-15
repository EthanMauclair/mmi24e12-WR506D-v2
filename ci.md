# Synthèse des vérifications – PENDERIE

- **Séances** : 15 à 21 (qualité, tests, préparation de la CI)
- **Date** : 15/10/2026
- **Branche** : `feature/test`
- **Détail** : qualité et sécurité dans [audit.md](audit.md), tests et couverture dans [coverage.md](coverage.md)

Chaque ligne se rejoue avec une commande du [Makefile](Makefile). Les captures sont dans [`docs/captures/`](docs/captures/).

## Résultats

| Vérification | Commande | Résultat | Capture |
|---|---|---|---|
| Linters Symfony, `composer.json`, mapping Doctrine | `make lint` | ✅ conformes | [lint.png](docs/captures/lint.png) |
| Analyse statique, niveau 6, sans baseline | `make phpstan` | ✅ 0 erreur | [phpstan.png](docs/captures/phpstan.png) |
| Style du code PHP (`@Symfony`) | `make cs` | ✅ conforme | [cs.png](docs/captures/cs.png) |
| Dépendances PHP et front | `make audit` | ✅ PHP : 0 ; front : 0 critique, failles hautes sans correctif acceptées (D-033, D-034) | [audit.png](docs/captures/audit.png) |
| Snyk (dépendances, image Docker, code) | app.snyk.io | ✅ code : 0 ; PHP : 0 ; image : 0 critique ; front : 0 critique | [snyk.png](docs/captures/snyk.png) |
| Tests PHPUnit (unitaires, intégration, fonctionnels) | `make tests` | ✅ 132 tests, 508 assertions | [tests.png](docs/captures/tests.png) |
| Couverture du code | `make couverture` | ✅ 100 % des lignes (883 / 883), des méthodes et des classes | [couverture.png](docs/captures/couverture.png) |
| Tests de bout en bout (Cypress) | `make e2e` | ✅ 11 tests, 4 fichiers, en 2 min 05 | [cypress.png](docs/captures/cypress.png) |
| Jeu de démonstration | `make fixtures` | ✅ 1 000 articles, 25 comptes, 24 logements, 92 relations, en 17 s | [fixtures.png](docs/captures/fixtures.png) |

`make qualite` enchaîne lint, PHPStan, style, audit et tests : c'est la commande que lancera la CI.

## Tests de bout en bout (Cypress)

| Fichier | Parcours | Tests |
|---|---|---|
| `connexion.cy.js` | P1 : page protégée → connexion → retour à la page demandée ; mauvais mot de passe ; mot de passe affiché en clair ; connexion qui mène au profil sans afficher l'e-mail | 4 |
| `inscription-et-logement.cy.js` | P1 : inscription → création guidée du logement, d'une pièce et d'un rangement ; pseudo invalide refusé | 2 |
| `ajout-article.cy.js` | P2 et P3 : photo → fiche → article retrouvé en tête de sa pièce et de son rangement ; catégorie et rangement exigés | 2 |
| `securite-et-mobile.cy.js` | Logement d'un autre introuvable (404) même en changeant l'adresse ; profil d'un autre sans e-mail ; barre du bas mobile et bouton photo | 3 |

**Lancer** : `make front`, puis `make fixtures` (les tests se connectent avec le compte de démonstration de Camille), puis `make e2e`. Cypress s'exécute sur le poste (Node requis) et non dans Docker : il pilote un navigateur, ce que l'image Alpine du front ne fournit pas.

**Ce que Cypress a révélé** : un clic sur « Se connecter » avant que Vue ait pris la main envoyait le formulaire en GET, avec le **mot de passe dans l'adresse**. Corrigé (`method="post"`, D-035). Les tests attendent désormais la fin de l'hydratation (`cy.visiter`, marqueur `data-pret`), ce qui a supprimé leurs échecs aléatoires.

## Vers la CI (avec Thomas)

Ce qui est prêt pour un workflow GitHub Actions :

1. `docker compose up -d` (image PHP Alpine, MySQL), `make installer`, `make migrer` ;
2. `make qualite` : lint, PHPStan, style, audit, tests ; une seule commande, qui échoue au premier problème ;
3. `make couverture` pour publier le rapport ;
4. `make front`, `make fixtures`, puis Cypress (image `cypress/included` ou action officielle `cypress-io/github-action`).

Points à régler alors : les secrets (`APP_SECRET`, clés JWT) à fournir par les « secrets » GitHub, jamais dans le dépôt ; la branche `feature/test` n'étant pas fusionnée, le workflow devra tourner sur cette branche ou la CI attendra la décision de l'enseignant sur les fixtures.

## Captures à ajouter

Prises depuis un terminal à la racine du projet, enregistrées dans `docs/captures/` sous les noms du tableau :

| Fichier | Que capturer |
|---|---|
| `lint.png`, `phpstan.png`, `cs.png`, `audit.png`, `tests.png` | La fin de la sortie de chaque commande `make …` |
| `couverture.png` | Le résumé de `make couverture`, ou la page `api/coverage/index.html` ouverte dans le navigateur |
| `cypress.png` | Le tableau final de `make e2e` (« All specs passed! ») |
| `fixtures.png` | La sortie de `make fixtures`, puis la page « Mes logements » de Camille |
| `snyk.png` | La liste des projets sur app.snyk.io |
