# Couverture des tests – PENDERIE

- **Séances** : 16 à 21 (Tests PHP)
- **Date** : 15/10/2026
- **Branche** : `feature/test` (non fusionnée dans `develop`, comme le demande la séance 16)
- **Rejouer** : `make couverture` (rapport HTML : `api/coverage/index.html`) ; `make tests` pour les tests seuls

## Résultat

| Mesure | Avant (séance 15) | Après |
|---|---|---|
| Tests PHPUnit | 98 (391 assertions) | **132** (508 assertions) |
| Lignes couvertes | 93,44 % (827 / 885) | **100 %** (883 / 883) |
| Méthodes couvertes | 87,55 % (204 / 233) | **100 %** (232 / 232) |
| Classes entièrement couvertes | 67,24 % (39 / 58) | **100 %** (58 / 58) |

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

| Niveau | Dossier | Classe de base | Ce qu'il vérifie | Exemples |
|---|---|---|---|---|
| **Unitaire** (40 tests) | `tests/Unit` | `TestCase` | Les invariants métier dans les entités, sans base ni HTTP : rapide, précis. | I-01 (compte mineur), I-05/I-09 (propriétaire), I-10 (pièce vide), I-11 (rangement chez soi), I-17/I-18 (abonnements), voter sans compte, renommage du jeton |
| **Intégration** (2 tests) | `tests/Integration` | `KernelTestCase` | Plusieurs composants réels ensemble (Doctrine, base de test, services), quand le HTTP ne suffit pas. | Pagination de la recherche sur 31 articles ; identifiant trafiqué (`1 OR 1=1`) refusé avant toute requête SQL |
| **Fonctionnel** (90 tests) | `tests/Fonctionnel` | `WebTestCase` | Chaque route : cas nominal, cas d'erreur, cas d'autorisation (401, 403, 404). | Toutes les routes du contrat d'API, dont les tests S-01 à S-13 de la séance 8-9 |

Chaque test porte dans son nom la règle qu'il vérifie (`a03_…`, `i10_…`, `s13_…`, `f26_…`) : la liste des tests se lit comme la liste des règles du projet (docs/03 à 06).

Choix de méthode :

- **Base remise à zéro à chaque test** (DAMADoctrineTestBundle : transaction annulée à la fin) : chaque test crée ses propres données, aucun ne dépend d'un autre ni de l'ordre d'exécution.
- **Les tests n'utilisent pas les fixtures** : elles servent à la démonstration et aux tests de bout en bout. Un test qui dépendrait de « Camille a 286 articles » casserait au moindre changement du jeu de données ; chaque test crée exactement les 2 ou 3 objets dont il a besoin.
- **Fixtures reproductibles** quand même (graine fixe 2026) : deux chargements donnent les mêmes 1 000 articles, vérifié par une somme de contrôle sur toutes les lignes. Seuls les identifiants changent (l'auto-incrément de MySQL n'est pas remis à zéro par la purge).

## Tests de bout en bout (Cypress)

Dossier `e2e/` : un vrai navigateur, contre l'application lancée par Docker (`make front` puis `make fixtures`, puis `cd e2e && npx cypress run`). Les parcours testés : connexion et protection des pages (P1), inscription puis création guidée du logement (P1), ajout d'un article par photo puis retour dans sa pièce (P2, P3), sécurité (logement d'un autre, e-mail d'un autre) et mise en page mobile. Résultats et captures : [ci.md](ci.md).

Ces tests ont révélé un défaut que les tests PHP ne pouvaient pas voir : un clic sur « Se connecter » avant que Vue prenne la main envoyait le **mot de passe dans l'adresse** (D-035). Corrigé.
