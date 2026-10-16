# Couverture des tests – PENDERIE

- **Séances** : 16 à 21 (Tests PHP)
- **Date** : 15/10/2026, mis à jour le 16/10/2026
- **Branche** : `feature/test` (non fusionnée dans `develop`, comme le demande la séance 16)
- **Rejouer** : `make couverture` (rapport HTML : `api/coverage/index.html`) ; `make tests` pour les tests seuls
- **Rejouer un seul niveau** : `docker compose exec php php -d pcov.enabled=1 bin/phpunit tests/Unit --coverage-text` (remplacer `Unit` par `Integration` ou `Fonctionnel`)

## Où trouver les tests

Le dépôt contient deux applications : l'API Symfony dans `api/` et le front Nuxt dans `front/`. Les tests de l'API sont donc dans `api/`, et les tests de bout en bout dans `e2e/`, à la racine.

| Demandé | Emplacement |
|---|---|
| Fixtures | `api/src/DataFixtures/AppFixtures.php` (avec `Fournisseur/PenderieFournisseur.php` et `PhotoFactice.php`) |
| Tests unitaires | `api/tests/Unit/` (`Entity/`, `EventListener/`, `Security/`) |
| Tests d'intégration | `api/tests/Integration/` |
| Tests fonctionnels | `api/tests/Fonctionnel/` |
| Configuration PHPUnit | `api/phpunit.dist.xml` |
| Configuration Cypress | `e2e/cypress.config.js` |
| Tests Cypress (bout en bout) | `e2e/cypress/e2e/*.cy.js` |
| Audit qualité et sécurité | `audit.md` (racine) |
| Captures des résultats | `ci.md` (racine) |

## Résultat

| Mesure | Avant (séance 15) | 15/10 | 16/10 (scan compris, D-040) |
|---|---|---|---|
| Tests PHPUnit | 98 (391 assertions) | 132 (508 assertions) | **202** (800 assertions) |
| dont unitaires / intégration / fonctionnels | – | 40 / 2 / 90 | **57 / 45 / 100** |
| Lignes couvertes | 93,44 % (827 / 885) | 100 % (883 / 883) | **100 %** (940 / 940) |
| Méthodes couvertes | 87,55 % (204 / 233) | 100 % (232 / 232) | **100 %** (237 / 237) |
| Classes entièrement couvertes | 67,24 % (39 / 58) | 100 % (58 / 58) | **100 %** (59 / 59) |

Le 16/10, les 100 % ne changent pas, mais ils ne reposent plus sur les seuls tests fonctionnels : **chaque niveau couvre seul tout le métier qui le concerne** (voir « Couverture par niveau »). Le scan (D-040) a ajouté le service de reconnaissance et 10 tests fonctionnels ; la première mesure après son arrivée donnait 99,68 % : deux cas n'étaient pas testés (« Autre » absente de la base, accolades sans JSON valide), ils le sont désormais.

Pilote de mesure : **PCOV**, ajouté à l'image PHP et désactivé par défaut (`pcov.enabled = 0`) pour ne pas ralentir l'application ; `make couverture` l'active pour la mesure seulement.

> Une couverture de 100 % dit que chaque ligne a été **exécutée** par un test, pas qu'elle a été **bien vérifiée**. C'est pourquoi chaque test ajouté vérifie un résultat attendu (statut HTTP, donnée renvoyée, état de la base ou du stockage), jamais le simple fait que le code tourne.

## Ce qui est mesuré, et ce qui ne l'est pas

Mesuré : tout `api/src/` (entités, règles d'accès, fournisseurs et processeurs API Platform, contrôleurs, dépôts, services, écouteurs, commandes).

Exclu, avec la raison (`phpunit.dist.xml`) :

| Exclu | Raison |
|---|---|
| `src/DataFixtures/` | Jeu de démonstration, pas du code de l'application. Il est quand même vérifié à chaque `make fixtures` : il passe par les fabriques des entités, donc par leurs invariants (un article rangé chez quelqu'un qui n'est pas membre du logement lèverait I-11). |
| `src/Kernel.php` | Amorçage de Symfony, sans logique propre. |

Deux lignes inatteignables, marquées `@codeCoverageIgnore` **avec leur justification dans le code** :

| Ligne | Pourquoi elle ne s'exécute jamais |
|---|---|
| `Logement::getMembreProprietaire()` : `throw new \LogicException('I-05 violé…')` | Garde-fou. `Logement::creer()` pose toujours un propriétaire et le transfert (I-09) en désigne un nouveau avant tout départ : aucun appel public ne peut produire un logement sans propriétaire. |
| `Utilisateur::eraseCredentials()` | Méthode vide exigée par l'interface de Symfony, marquée dépréciée par Symfony 7.3. L'appeler dans un test déclencherait l'avertissement de dépréciation, que la configuration des tests transforme en échec (`failOnDeprecation`). |

## Ce que la mesure a révélé

La première mesure (93 %) n'était pas un chiffre à faire monter : elle montrait **des routes utilisées par le front sans aucun test**.

| Trou trouvé | Pourquoi c'est grave | Test ajouté |
|---|---|---|
| Créer, renommer, supprimer un **rangement** (`POST /pieces/{id}/stockages`, `PATCH`, `DELETE /stockages/{id}`) | Utilisées par la création guidée et « + Rangement » : jamais exécutées par un test. | `PiecesEtRangementsApiTest` |
| Renommer et supprimer une **pièce** | Idem. | `PiecesEtRangementsApiTest` |
| Ajouter une pièce ou un rangement **chez quelqu'un d'autre** | Cas de sécurité (« voler les affaires du voisin ») non vérifié pour ces routes. | `s13_on_n_ajoute_ni_piece_ni_rangement_chez_les_autres` |
| Changer son **e-mail** ou son **mot de passe** | Écran Paramètres : jamais testé, dont le refus d'un e-mail déjà pris (409). | `MonCompteTest` |
| **Photos PNG, WebP et prises de travers** | Le redressement selon l'orientation EXIF des téléphones n'était jamais exécuté. | `f07_une_photo_prise_de_travers_est_redressee` (3 orientations), `f07_les_photos_png_et_webp…` |
| Suppression de la **photo** avec l'article | Sinon, des fichiers orphelins s'accumulent dans le stockage. | `supprimer_un_article_supprime_aussi_sa_photo` |
| Filtres **couleur, marque, logement** de la recherche | Recherche (P3, « où est ma veste noire ? ») partiellement testée. | `f26_je_filtre_par_couleur_par_marque_et_par_logement` |
| **Pagination** par 30 et ordre de la recherche | Règle du contrat jamais vérifiée : invisible avec 3 articles, essentielle avec 1 000. | `f26_la_recherche_est_paginee_par_30…` (intégration) |

## Les trois niveaux de tests

Le code métier de l'API est réparti en couches. Chaque niveau de test vise la couche où une règle **se décide** : on teste une règle là où elle est écrite, au plus près, avec le moins d'outillage possible.

| Niveau | Dossier | Classe de base | Code visé | Pourquoi ce niveau |
|---|---|---|---|---|
| **Unitaire** (57 tests) | `tests/Unit` | `TestCase` | Entités (`src/Entity`), voters (`src/Security/*Voter`), traduction des règles en statuts HTTP (`RegleMetierListener`) | Les invariants vivent dans les entités riches (D-018) et les droits dans les voters : du PHP pur, testable sans base ni HTTP. Toute la suite tourne en moins d'une seconde. |
| **Intégration** (45 tests) | `tests/Integration` | `KernelTestCase` | Services d'accès (`AccesLogement`, `AccesArticle`, `AccesProfil`), dépôts (`src/Repository`), service photo (`PhotoArticles`), règles tenues par la base | Ce code n'a de sens qu'avec ses vraies dépendances : requêtes MySQL (interclassement sans accents, `LIKE`, tri), clés étrangères en cascade, contraintes d'unicité, GD et Flysystem. Les simuler reviendrait à tester les simulations. |
| **Fonctionnel** (100 tests) | `tests/Fonctionnel` | `WebTestCase` | Les routes du contrat d'API : processeurs et fournisseurs API Platform (`src/State`), contrôleurs, DTO, format des réponses | Cette couche ne fait qu'assembler les deux autres derrière HTTP. Le vrai risque y est la **colle** : jeton JWT, désérialisation, champ en trop, statut HTTP, champ qui ne doit pas sortir (S-05). Seule une vraie requête le vérifie. |

### Couverture par niveau

Mesurée en lançant **chaque suite seule** (commande dans l'en-tête), sur le code qu'elle vise :

| Code métier | Lignes | Unitaire seul | Intégration seule | Fonctionnel seul |
|---|---|---|---|---|
| Entités : règles (invariants, transitions) | 191 | **100 %** | – | – |
| Entités : accesseurs sans logique (`getId()`, `getNom()`…) | 21 | non visé | exécutés | exécutés |
| Voters (`LogementVoter`, `ArticleVoter`) | 26 | **100 %** | **100 %** | – |
| `RegleMetierListener` (règle → 403, 409 ou 422) | 11 | **100 %** | – | – |
| Services d'accès (`AccesLogement`, `AccesArticle`, `AccesProfil`) | 38 | – | **100 %** | – |
| Dépôts (`src/Repository`) | 117 | – | **100 %** | – |
| Service photo (`PhotoArticles`) | 39 | – | **100 %** | – |
| Service de reconnaissance (`ReconnaissanceArticles`, D-040) | 54 | – | – | **100 %** |
| Routes de l'API (`src/State`, `src/Controller`, `src/ApiResource`) | 394 | – | – | **100 %** |
| **Tout `api/src/`** (hors exclusions ci-dessus) | 940 | 25,0 % | 40,3 % | 98,2 % |

Lecture :
- **Unitaire.** Les tests unitaires couvrent **toutes les lignes de règles** des entités. Les 21 lignes restantes sont des accesseurs d'une ligne (`return $this->id;`) et l'enregistrement du mot de passe haché. Leur écrire un test unitaire gonflerait le chiffre sans rien vérifier. Ils sont exécutés, et leurs valeurs vérifiées, par les tests d'intégration et fonctionnels.
- **Fonctionnel.** Les 17 lignes que ce niveau n'atteint pas seul sont des garde-fous qu'aucune requête ne peut déclencher, parce qu'un contrôle placé avant les arrête :
  - I-11 et H18 dans les entités : le contrôle d'accès refuse avant ;
  - I-01 : la création d'un compte mineur n'a pas encore de route (F-12) ;
  - I-02 : la tâche planifiée n'est pas écrite ;
  - identifiant non numérique, et voter appelé sans compte.

  Ces garde-fous sont justement le rôle des tests unitaires et d'intégration, qui les couvrent.
- **Reconnaissance (D-040).** Le service appelle un modèle de vision chez Google. Les tests fonctionnels remplacent cet appel par `ModeleSimule` (`framework.http_client.mock_response_factory`) : la requête complète (photo, jeton, route, réponse JSON) est vérifiée, sans dépendre du réseau ni du quota de Google. Chaque réponse possible du modèle a son test : proposition correcte, catégorie hors liste, texte autour du JSON, réponse illisible, accolades sans JSON valide, panne (429, 503, délai), nouvel essai après un 503, clé absente, catégorie « Autre » absente.

### Le test fonctionnel désigné

**`ArticleApiTest::f07_j_envoie_une_photo_puis_j_enregistre_l_article`** (`api/tests/Fonctionnel/ArticleApiTest.php`).

Pourquoi lui :
- **C'est le cœur du produit.** Il rejoue le parcours P2, « j'ajoute un article en le prenant en photo », qui est la promesse du pitch.
- **Il traverse toutes les couches en une requête réelle** :
  - connexion par jeton JWT ;
  - envoi d'un fichier (`multipart`), traitement de l'image par GD, écriture dans Flysystem ;
  - création de l'article : désérialisation, contrôle d'accès au rangement (A-11), invariant I-11, Doctrine ;
  - réponse au format du contrat ;
  - photo resservie par l'API, avec contrôle de visibilité.
- **Il vérifie des résultats, pas seulement un statut** : auteur imposé, catégorie, emplacement, photo réduite à 1600 × 800 et en JPG, refus de réutiliser une photo (422).
- Aucun test unitaire ni d'intégration ne peut prouver que ces morceaux, chacun testé à part, **fonctionnent ensemble**.

Les 99 autres tests fonctionnels vérifient les routes une à une : chaque route avec son cas nominal, ses erreurs et ses refus d'accès (401, 403, 404).

### Chaque règle métier et son test

Règles de `docs/05-modele-donnees.md` (I-xx) et `docs/06-modele-autorisation.md` (A-xx, S-xx). Le préfixe du nom de test retrouve la règle (`grep -r "function i10_" api/tests`).

| Règle | Unitaire | Intégration | Fonctionnel | Note |
|---|---|---|---|---|
| I-01 Mineur rattaché à un référent majeur | ✅ | | ✅ | |
| I-02 Indépendance à 18 ans | ✅ | | | Tâche planifiée pas encore écrite (F-13, lot 6) : la règle est dans l'entité. |
| I-03 Supprimer un référent supprime ses mineurs et leurs articles | | ✅ | ✅ | Tenue par la clé étrangère `ON DELETE CASCADE` : invérifiable sans base. |
| I-04 Un propriétaire ne supprime pas son compte | | ✅ | ✅ | Décidée par une requête (`MembreRepository`). |
| I-05 Un seul propriétaire | ✅ | | | |
| I-06 Le propriétaire est majeur | ✅ | ✅ | | |
| I-07 Membre au plus une fois | ✅ | ✅ | | En base : contrainte d'unicité. |
| I-08 Un mineur ne crée pas de logement | ✅ | | ✅ | |
| I-09 Transfert ou suppression du logement | ✅ | ✅ | | |
| I-10 Un article est toujours rangé ; pièce et rangement ne se suppriment que vides | ✅ | ✅ | ✅ | |
| I-11 L'auteur est membre du logement | ✅ | | | |
| I-17 Pas d'abonnement à soi-même ; une relation par couple | ✅ | ✅ | | En base : contrainte d'unicité. |
| I-18 Acceptée d'office vers un compte public | ✅ | | ✅ | |
| I-21 Un mineur reste privé | ✅ | | ✅ | |
| A-01 Un membre voit tout le logement | ✅ | ✅ | ✅ | |
| A-03 Visibilité selon le compte de l'auteur | ✅ | ✅ | ✅ | D-013 : jamais selon le propriétaire du logement. |
| A-04 Un article personnel reste dans le logement | ✅ | ✅ | ✅ | |
| A-09 Seul le propriétaire supprime le logement | ✅ | ✅ | ✅ (S-08) | |
| A-10 Les majeurs modifient tout | ✅ | ✅ | ✅ | |
| A-11 Un mineur ajoute, ne modifie que ses articles | ✅ | ✅ | ✅ (S-06, S-07) | |
| A-15 Auteur, rôle et propriétaire non modifiables par requête | ✅ | | ✅ | |
| A-16 Les articles d'un mineur ne sont jamais publics | ✅ | ✅ | ✅ | |
| A-17 Retirer ou quitter un logement | ✅ | ✅ | ✅ | |
| S-01, S-02, S-04 à S-08, S-13 (tests de sécurité) | | S-01, S-13 | ✅ | |
| **Pas encore codées** | | | | |
| I-12 à I-16, A-05, A-06, A-12 (tenues) | – | – | – | Lot 4, pas encore développé. |
| I-19, I-20, A-07, A-08, A-13, A-14, S-09 à S-12 (commentaires, liens) | – | – | – | Lot 5, pas encore développé. |
| A-02 Un non-membre voit le nom d'un logement | – | – | – | Aucun écran ne montre le logement d'un autre ; l'API répond 404 (D-014), ce qui est plus strict que la règle. |

Toutes les règles **déjà codées** ont au moins un test, et presque toutes au niveau où elles se décident. Les règles non codées seront testées avec leur lot, selon la même grille.

Chaque test porte dans son nom la règle qu'il vérifie (`a03_…`, `i10_…`, `s13_…`, `f26_…`) : la liste des tests se lit comme la liste des règles du projet (docs/03 à 06).

Choix de méthode :

- **Base remise à zéro à chaque test** (DAMADoctrineTestBundle : transaction annulée à la fin) : chaque test crée ses propres données, aucun ne dépend d'un autre ni de l'ordre d'exécution.
- **Les tests n'utilisent pas les fixtures** : elles servent à la démonstration et aux tests de bout en bout. Un test qui dépendrait de « Camille a 286 articles » casserait au moindre changement du jeu de données ; chaque test crée exactement les 2 ou 3 objets dont il a besoin.
- **Fixtures reproductibles** quand même (graine fixe 2026) : deux chargements donnent les mêmes 1 000 articles, vérifié par une somme de contrôle sur toutes les lignes. Seuls les identifiants changent (l'auto-incrément de MySQL n'est pas remis à zéro par la purge).

## Tests de bout en bout (Cypress)

Dossier `e2e/` : un vrai navigateur, contre l'application lancée par Docker (`make front` puis `make fixtures`, puis `make e2e`). 14 tests dans 5 fichiers. Résultats et captures : [ci.md](ci.md).

**Parcours métier** (`docs/04-parcours-utilisateurs.md`) :

| Parcours | Écrans existants | Test Cypress | Couvert |
|---|---|---|---|
| P1 Découvrir l'application et ranger ses premiers articles | ✅ | `inscription-et-logement.cy.js` (inscription, logement, pièce, rangement, pseudo refusé), `connexion.cy.js` (pages protégées, mauvais mot de passe, profil) | ✅ |
| P2 Ajouter un article en le prenant en photo | ✅ | `ajout-article.cy.js` (photo, fiche, catégorie et rangement obligatoires) | ✅ |
| P3 Retrouver un article | ✅ recherche (F-26) et fiche d'un article (F-09, F-10) | `recherche-article.cy.js` (« veste », puis couleur noire, emplacement de chaque résultat ; ouverture de la fiche, déplacement, retour à la recherche avec ses critères ; aucun résultat), `ajout-article.cy.js` (l'article est retrouvé dans sa pièce) | ✅ |
| P4 Composer une tenue et la publier | ❌ lot 4 | – | hors périmètre actuel |
| P5 Découvrir un compte et s'abonner | ❌ API faite, écrans non faits | – | hors périmètre actuel (API testée : `RelationApiTest`) |
| P6 Commenter une tenue, retirer un ami | ❌ lot 5 | – | hors périmètre actuel |
| P7 Envoyer un article par lien | ❌ lot 5 | – | hors périmètre actuel |
| P8 Partager son logement | ❌ API faite, écrans non faits | – | hors périmètre actuel (API testée : `LogementApiTest`) |
| P9 Supprimer son logement, puis son compte | ❌ API faite, écrans non faits | – | hors périmètre actuel (API testée) |

Plus deux scénarios transversaux : **sécurité** (le logement d'une autre personne reste introuvable même en changeant l'adresse ; aucun e-mail dans un profil, S-05) et **mobile** (barre du bas, bouton photo).

**Bilan** : 100 % des parcours dont les écrans existent sont couverts, P1 à P3 entièrement. Un parcours sans écran ne peut pas avoir de test de bout en bout : chacun recevra le sien avec ses écrans. En attendant, ses règles sont testées côté API.

**Sélecteurs** : les tests visent des attributs `data-cy` (barre du bas, menu latéral, grilles, recherche, fiche d'un article, D-037), à défaut un rôle ou un texte visible (`cy.contains('button', …)`), jamais une classe CSS : un changement de style ne casse plus les tests.

**Écart connu aux bonnes pratiques Cypress** : les tests tournent sur la base de développement chargée par les fixtures, et non sur une base dédiée. Ils en tiennent compte : quand ils ajoutent des données, elles sont uniques (pseudo et marque horodatés) ; quand ils en modifient, ils ne supposent rien de l'état de départ (le déplacement de P3 choisit un rangement différent de l'actuel).

Ces tests ont révélé un défaut que les tests PHP ne pouvaient pas voir : un clic sur « Se connecter » avant que Vue prenne la main envoyait le **mot de passe dans l'adresse** (D-035). Corrigé.
