# Synthèse des vérifications – PENDERIE

- **Séances** : 15 à 21 (qualité, tests, préparation de la CI)
- **Date** : 16/10/2026 (captures)
- **Branche** : `feature/test`
- **Détail** : qualité et sécurité dans [audit.md](audit.md), tests et couverture dans [coverage.md](coverage.md)

Chaque ligne se rejoue avec une commande du [Makefile](Makefile). Les captures sont dans [`docs/captures/`](docs/captures/).

## Résultats

| Vérification | Commande | Résultat | Capture |
|---|---|---|---|
| Linters Symfony, `composer.json`, mapping Doctrine | `make lint` | ✅ conformes | [lint.png](docs/captures/lint.png) |
| Analyse statique, niveau 6, sans baseline | `make phpstan` | ✅ 0 erreur | [phpstan.png](docs/captures/phpstan.png) |
| Style du code PHP (`@Symfony`) | `make cs` | ✅ conforme | [cs.png](docs/captures/cs.png) |
| Dépendances PHP et front | `make audit` | ✅ PHP : 0 ; front : 0 critique, 11 hautes acceptées faute de correctif (`node-forge`, `braces`), toute autre faille haute ou critique fait échouer la commande (D-033, D-038) | [audit.png](docs/captures/audit.png) |
| Snyk (dépendances, image Docker, code) | app.snyk.io | ✅ 0 critique. Code : 0 ; PHP : 0 ; image : 1 haute, dans l'image de base, corrigée à la construction par `apk upgrade` (D-034) ; front : 3 hautes acceptées, héritées de Nuxt et sans correctif (D-033, D-034) | [snyk.png](docs/captures/snyk.png) |
| Tests PHPUnit (unitaires, intégration, fonctionnels) | `make tests` | ✅ 202 tests, 800 assertions | [tests.png](docs/captures/tests.png) |
| Couverture du code | `make couverture` | ✅ 100 % des lignes (940 / 940), des méthodes (237 / 237) et des classes (59 / 59) | [couverture.png](docs/captures/couverture.png) |
| Tests de bout en bout (Cypress) | `make e2e` | ✅ 15 tests, 5 fichiers, en 2 min 45 | [cypress.png](docs/captures/cypress.png) |
| Intégration continue (GitHub Actions) | `make qualite`, à chaque push et à chaque PR | ✅ verte sur `develop` et `feature/test` | [onglet Actions](https://github.com/EthanMauclair/mmi24e12-WR506D-v2/actions) |
| Jeu de démonstration | `make fixtures` | ✅ 1 000 articles, 25 comptes, 24 logements, 92 relations (comptés en base après le chargement) | [fixtures.png](docs/captures/fixtures.png) |

`make qualite` enchaîne lint, PHPStan, style, audit et tests : c'est la commande que lance la CI (D-038).

## Tests de bout en bout (Cypress)

| Fichier | Parcours | Tests |
|---|---|---|
| `connexion.cy.js` | P1 : page protégée → connexion → retour à la page demandée ; mauvais mot de passe ; mot de passe affiché en clair ; connexion qui mène au profil sans afficher l'e-mail | 4 |
| `inscription-et-logement.cy.js` | P1 : inscription → création guidée du logement, d'une pièce et d'un rangement ; pseudo invalide refusé | 2 |
| `ajout-article.cy.js` | P2 et P3 : photo → fiche → article retrouvé en tête de sa pièce et de son rangement ; fiche préremplie par la proposition du modèle (D-040) ; catégorie et rangement exigés. La réponse de `/api/reconnaissances` est interceptée : le résultat ne dépend pas de Google | 3 |
| `recherche-article.cy.js` | P3 : « veste », puis couleur noire, emplacement de chaque résultat ; ouverture de la fiche, déplacement, retour à la recherche avec ses critères ; aucun résultat | 3 |
| `securite-et-mobile.cy.js` | Logement d'un autre introuvable (404) même en changeant l'adresse ; profil d'un autre sans e-mail ; barre du bas mobile et bouton photo | 3 |

**Lancer** : `make front`, puis `make fixtures` (les tests se connectent avec le compte de démonstration de Camille), puis `make e2e`. Cypress s'exécute sur le poste (Node requis) et non dans Docker : il pilote un navigateur, ce que l'image Alpine du front ne fournit pas.

**Ce que Cypress a révélé** : un clic sur « Se connecter » avant que Vue ait pris la main envoyait le formulaire en GET, avec le **mot de passe dans l'adresse**. Corrigé (`method="post"`, D-035). Les tests attendent désormais la fin de l'hydratation (`cy.visiter`, marqueur `data-pret`), ce qui a supprimé leurs échecs aléatoires.

## Intégration continue

**En place** (séances 17-18, D-038) : `.github/workflows/qualite.yml` lance `make qualite` à chaque push et à chaque PR vers `develop` ou `main`, dans les mêmes conteneurs Docker que sur le poste. Les secrets et les clés JWT sont tirés au sort à chaque exécution : aucun n'est stocké sur GitHub. La protection des branches `develop` et `main` exige que cette vérification soit verte avant toute fusion.

**Reste à ajouter avec Thomas** : la couverture (`make couverture`, qui demande pcov) et Cypress (après `make front` et `make fixtures`, avec l'action officielle `cypress-io/github-action`). Les deux dépendent de cette branche, `feature/test`, qui n'est pas fusionnée (D-036).

## Refaire les captures

Prises depuis un terminal à la racine du projet, enregistrées dans `docs/captures/` sous les noms du tableau :

| Fichier | Que capturer |
|---|---|
| `lint.png`, `phpstan.png`, `cs.png`, `audit.png`, `tests.png` | La fin de la sortie de chaque commande `make …` |
| `couverture.png` | Le résumé de `make couverture`, ou la page `api/coverage/index.html` ouverte dans le navigateur |
| `cypress.png` | Le tableau final de `make e2e` (« All specs passed! ») |
| `fixtures.png` | La sortie de `make fixtures`, puis la page « Mes logements » de Camille |
| `snyk.png` | La liste des projets sur app.snyk.io |
