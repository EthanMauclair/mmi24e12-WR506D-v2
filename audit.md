# Audit de qualité – PENDERIE

- **Séance** : 15 (Linter, PHPStan, Code Quality)
- **Date** : 15/10/2026
- **Périmètre** : API Symfony (`api/`, 69 fichiers PHP, 15 fichiers de tests) et front Nuxt (`front/`)
- **Rejouer l'audit** : `make qualite` (voir le [Makefile](Makefile)) ; décisions dans [D-033](docs/journal-decisions.md)

## Synthèse

| Outil | Ce qu'il observe | Résultat | Commande |
|---|---|---|---|
| PHPStan niveau 6 | Types, appels invalides, incohérences | ✅ 0 erreur, **sans baseline** (16 corrigées) | `make phpstan` |
| PHP-CS-Fixer (`@Symfony`) | Style du code PHP | ✅ conforme | `make cs` |
| Linters Symfony | Conteneur, YAML, Twig | ✅ conformes | `make lint` |
| `composer validate --strict` | `composer.json` | ✅ valide (nom et description ajoutés) | `make lint` |
| `doctrine:schema:validate` | Mapping et schéma de la base | ✅ synchronisés | `make lint` |
| `composer audit` | Vulnérabilités des dépendances PHP | ✅ aucune | `make audit` |
| `npm audit` | Vulnérabilités des dépendances du front | ⚠️ 14 au départ (2 critiques, 12 hautes) → **11 hautes**, sans correctif publié, argumentées ci-dessous | `make audit` |
| Snyk (dépôt GitHub, branche `develop`) | Dépendances, image Docker, code source | ✅ code source (Snyk Code) : 0 ; `composer.lock` : 0 ; front : voir § 3 ; image Docker : 288 failles → **image remplacée**, voir § 4 | app.snyk.io |
| Trivy 0.58 | Failles du système de l'image Docker | `php:8.4-fpm` : 2 critiques, 124 hautes → `php:8.4-fpm-alpine` : **0 critique, 0 haute**, 2 moyennes | voir § 4 |
| axe-core 4.10 (WCAG 2.1 AA) | Accessibilité des écrans | ✅ 0 défaut après correction (1 défaut corrigé) | dans le navigateur |
| Build de production Nuxt | Poids des ressources | 95 Ko de JS et 7 Ko de CSS (gzip) pour toute l'application | `npx nuxt build` |
| PHPUnit | Fonctionnement | ✅ 98 tests, 391 assertions | `make tests` |

## 1. Qualité du code PHP

### PHPStan niveau 6

Configuration : [`api/phpstan.dist.neon`](api/phpstan.dist.neon), avec les extensions Symfony (types des services, via le conteneur compilé), Doctrine (requêtes DQL, mapping) et PHPUnit. Analyse de `src/` **et** de `tests/`.

Premier passage : **16 erreurs**, toutes corrigées, aucune ignorée :

| Erreurs | Cause | Correction |
|---|---|---|
| 4 | `@param LogementVoter::* $droit` inclut les constantes entières héritées de `Voter` | Liste explicite des droits (`VOIR`, `MODIFIER`…) |
| 8 | Les fournisseurs API Platform déclaraient `object\|array\|null` sans jamais renvoyer `null` | Type de retour précis (`PieceRessource`, `object\|array`) |
| 4 | `reponse()` des tests typée « objet JSON » alors qu'elle renvoie aussi des listes | `array<mixed>` |

Le code était déjà typé strictement (`declare(strict_types=1)`, propriétés et retours typés, tableaux décrits en PHPDoc). C'est ce qui explique le petit nombre d'erreurs au niveau 6.

### Style

Règles `@Symfony`, avec deux exceptions assumées ([`api/.php-cs-fixer.dist.php`](api/.php-cs-fixer.dist.php)) :

- **noms des tests en snake_case** : chaque test commence par la règle qu'il vérifie (`f07_j_envoie_une_photo_puis_j_enregistre_l_article`). Le camelCase imposé par `@Symfony` les rendrait illisibles (`f07JEnvoieUnePhotoPuisJEnregistreLArticle`) ;
- pas de point ajouté après une phrase finissant par « … ».

### Inspection manuelle

| Point observé | Constat | Suite |
|---|---|---|
| Responsabilités | Entités riches sans setters (D-018) : les règles métier sont dans les entités, les contrôleurs et fournisseurs restent minces. | – |
| Duplication | La règle « qui voit les articles d'une personne » (A-03) était écrite deux fois. Elle a été regroupée dans `AccesProfil` lors du profil (D-028). | Surveiller `ArticleVoter`, qui porte une règle voisine pour un article seul. |
| Organisation | Deux styles cohabitent : ressources API Platform en DTO (logements, articles…) et contrôleurs Symfony (authentification, `/api/moi`, photo). Choix assumé, ces routes n'étant pas des ressources CRUD. | Documenter le critère dans le README. |
| Dette connue | Mot de passe actuel non demandé pour changer d'e-mail ou de mot de passe (D-028) ; photos abandonnées jamais supprimées (D-030). | Lots suivants. |

## 2. Qualité technique Symfony

- `lint:container` : tous les services reçoivent des valeurs compatibles avec leurs types.
- `lint:yaml` (19 fichiers) et `lint:twig` (1 modèle) : syntaxe valide.
- `doctrine:schema:validate` : mapping correct et base synchronisée avec les migrations.
- `composer audit` : aucune vulnérabilité connue.

## 3. Sécurité des dépendances du front

| Paquet | Gravité | Chemin | Traitement |
|---|---|---|---|
| `simple-git`, `@simple-git/argv-parser` | **Critique** (×2) | `nuxt` → `@nuxt/devtools` 3.4.2 | ✅ **Corrigé** : `@nuxt/devtools` 3.4.3 imposé (`overrides` dans `front/package.json`), qui ne dépend plus de `simple-git`. |
| `node-forge` ≤ 1.4.0 | Haute | `nuxt` → `nitropack` → `listhen` | ⚠️ **Accepté** : aucune version corrigée publiée (1.4.0 est la dernière). `listhen` s'en sert pour fabriquer un certificat HTTPS **du serveur de développement** ; la faille concerne la *vérification* de signatures RSA, que nous n'utilisons pas. Absent du serveur de production. |
| `braces` ≤ 3.0.3 (et `micromatch`, `fast-glob`, `globby` qui en dépendent) | Haute | `nuxt` → `nitropack` → `globby` | ⚠️ **Accepté** : aucune version corrigée (3.0.3 est la dernière). Déni de service sur des motifs de fichiers très imbriqués, utilisés **à la compilation** sur des motifs écrits par nous, jamais par un utilisateur. |
| `ioredis` 5.11.1 (relevé par Snyk, pas par `npm audit`) | Haute | `nuxt` → `nitropack` et `@nuxt/devtools` → `unstorage` | ⚠️ **Accepté** : récursion non contrôlée dans le client Redis. `unstorage` ne l'utilise que pour son connecteur Redis, que l'application n'emploie pas (aucun serveur Redis). Pas de version compatible avec Nuxt pour l'instant. |
| `nuxt`, `@nuxt/*`, `nitropack`, `listhen` | Haute | – | Signalés uniquement parce qu'ils dépendent des deux paquets ci-dessus. |

Une seule correction proposée par npm (`npm audit fix --force`) : **revenir à Nuxt 4.1.3**, une version plus ancienne, sans corriger `node-forge` ni `braces`. Refusée.

Incident rencontré : `npm` 10 plantait (`Cannot read properties of null (reading 'edgesOut')`), un bug connu de calcul des dépendances. L'installation a été faite une fois avec `npm` 11 (`npx npm@11 install`) ; `npm` 10, utilisé par le conteneur, fonctionne de nouveau avec le fichier de verrouillage produit.

**À revoir à chaque `make audit`** : dès qu'une version corrigée de `node-forge` ou de `braces` paraît, l'imposer de la même façon.

## 4. Image Docker de l'API

Snyk a été branché sur le dépôt GitHub (branche `develop`). Il analyse aussi `docker/php/Dockerfile` : l'image de base `php:8.4-fpm` (Debian 13) contenait **288 failles** selon Snyk (3 critiques, 23 hautes, 8 moyennes, 254 faibles), et 1 985 selon Trivy (2 critiques, 124 hautes).

- Elles viennent du **système Debian** embarqué, pas de notre code : les 3 critiques concernent `perl`, que l'application n'utilise pas ; les hautes, `libssh2`, `pcre2`, `sqlite3`, `gzip`…
- 220 sur 288 n'ont aucune correction publiée : mettre les paquets à jour ne suffisait pas.
- Snyk proposait `php:8.6-rc-zts-alpine3.24` : écarté, c'est une version **candidate** de PHP 8.6, en mode multi-thread (ZTS) dont nous n'avons pas besoin.

**Nouvelle analyse Snyk après le changement** : 22 failles (0 critique, 1 haute dans `zlib`, 1 moyenne dans `nghttp2`, 20 faibles dans `curl`), toutes corrigées dans les paquets Alpine publiés depuis l'image de base. Le Dockerfile lance donc `apk upgrade` à la construction : **l'image construite ne contient plus aucune faille connue** (Trivy, 15/10/2026). Snyk continue d'afficher ces 22 failles, car il analyse l'image de base déclarée dans le Dockerfile, pas l'image construite.

**Décision** : passer à `php:8.4-fpm-alpine`, avec **la même version de PHP** (8.4) et un système Alpine réduit au strict nécessaire. Résultat Trivy : **0 critique, 0 haute, 2 moyennes**. `www-data` garde l'identifiant 33 de Debian, pour que les volumes `vendor/` et `var/` déjà créés restent utilisables sans manipulation. Vérifié après le changement : 98 tests verts (dont la réduction des photos avec GD), PHPStan, linters, API et front.

## 5. Accessibilité numérique (A11Y)

Outil : **axe-core 4.10.2**, règles WCAG 2.0 A et AA, WCAG 2.1 AA et bonnes pratiques, lancé dans le navigateur sur chaque écran (compte connecté, la barre de Nuxt DevTools exclue car absente en production).

| Écran | Défauts |
|---|---|
| Connexion | 0 |
| Mon profil, Paramètres, Mes logements | 1 (commun, corrigé) |
| Page d'une pièce, Création du logement, Ajout d'un article | 0 |

**Défaut corrigé** (`aria-prohibited-attr`, gravité sérieuse) : dans la barre du bas mobile, les entrées pas encore disponibles (Rechercher, Mes tenues) étaient des `<span>` avec un `aria-label`, nom que les lecteurs d'écran n'annoncent pas sur un élément sans rôle. Elles portent maintenant `role="link"` et `aria-disabled="true"`.

Points vérifiés à la main (ce qu'un outil automatique ne voit pas) :

| Critère | Constat |
|---|---|
| Navigation au clavier | Tous les contrôles sont de vrais `<button>`, `<a>`, `<input>` ; aucun clic sur un `<div>`. |
| Focus visible | Contour bordeaux de 3 px (`:focus-visible`) sur toute l'application. |
| Formulaires | Chaque champ a un `<label>` ; les erreurs sont reliées par `aria-describedby` et annoncées (`role="alert"`). |
| Mot de passe | Bouton « Afficher le mot de passe » avec `aria-pressed` (D-026). |
| Contrastes | Contrôlés par axe : texte courant ≥ 4,5:1 (`#1F1517` et `#5C4E51` sur `#FAF8F7`, blanc sur bordeaux `#6E1A2B`). |
| Lisibilité | Police Atkinson Hyperlegible, dessinée pour les personnes malvoyantes (D-026). |
| Zones tactiles | 44 px pour les boutons, liens et champs. **À corriger** : les puces de rangement (36 px), les filtres de la page d'une pièce (40 px) et la croix pour retirer un rangement (32 px) sont en dessous. |
| Titres | Un `<h1>` par écran, hiérarchie respectée. |
| Images | Photos des articles décrites par leur catégorie, marque et couleur (`alt`). |

**Non vérifié** : l'écran Inscription et l'étape « fiche » de l'ajout n'ont pas été passés à axe ; à faire avec Cypress (séances 16-21).

## 6. Écoconception

| Mesure | Valeur | Commentaire |
|---|---|---|
| JavaScript (toute l'application, gzip) | 95 Ko | Découpé par page par Nuxt ; une page n'en charge qu'une partie. |
| CSS (gzip) | 7 Ko | Écrit à la main, sans bibliothèque de composants (D-025). |
| Polices | 1 famille, 2 graisses (+ italique) | Une seule police au lieu de deux (D-026). |
| Rendu serveur | Oui | La page arrive remplie : pas d'écran vide puis d'appels en cascade. |
| Photos | Réduites à 1 600 px, JPG, sans métadonnées (D-022) | Faites côté serveur, une seule fois, à l'envoi. |
| Images | `loading="lazy"` dans les grilles | Seules les photos visibles sont chargées. |

**Point faible principal** : les grilles (profil, page d'une pièce) chargent la photo entière (jusqu'à 1 600 px) pour afficher une vignette d'environ 150 px. Avec le jeu de données des fixtures (~1 000 articles), c'est le premier poste de poids. **Amélioration prévue** : produire une vignette (~400 px) à l'envoi et l'utiliser dans les grilles.

**Non mesuré** : Lighthouse et EcoIndex demandent un navigateur Chrome hors de Docker ; à lancer depuis le poste (onglet Lighthouse des outils de développement) sur `http://localhost:3000`.

## 7. Expérience utilisateur (UX)

Constats issus du **test du parcours P1 et P2 par l'étudiant** (15/10/2026), tous corrigés :

| Problème rencontré | Correction |
|---|---|
| Après un `git pull`, pages en 404 : le front ne voyait pas les nouveaux fichiers | Relecture des fichiers par le serveur (D-031) |
| Les pièces de « Mes logements » n'étaient pas cliquables | Page d'une pièce avec ses articles et un filtre par rangement (D-032) |
| La fiche d'un article ne proposait pas un logement tout juste créé (pièces sans rangement masquées, sans explication) | Toutes les pièces listées, création d'un rangement depuis la fiche (D-032) |
| Informations du compte affichées dès la connexion | Profil, puis paramètres séparés (D-028) |

Bonnes pratiques en place : retour visible à chaque action (« Enregistrement… », message de confirmation, message d'erreur près du champ), contrôles avant l'envoi avec les mêmes règles que l'API, création guidée après l'inscription (D-010), entrées pas encore disponibles affichées mais grisées.

**À améliorer** : supprimer ou renommer une pièce et un rangement après leur création (les routes de l'API existent) ; confirmation avant les actions irréversibles.

## 8. Testabilité et fiabilité (séances 16 à 21)

- **Existant** : 98 tests PHPUnit (unitaires sur les entités, fonctionnels sur chaque route : cas nominal, erreurs, autorisations), base de test remise à zéro à chaque test (DAMADoctrineTestBundle).
- **À faire** : fixtures Faker volumineuses et à graine fixe, mesure de la couverture (`coverage.md`), tests de bout en bout Cypress, CI GitHub Actions lançant `make qualite`, synthèse `ci.md`.
