# Contrat d'API – Version 1

- **Rédigé par** : Ethan Mauclair
- **Date** : 13/10/2026
- **Fichier** : [`api/openapi.yaml`](api/openapi.yaml) (OpenAPI 3.1)
- **Statut** : à valider au pitch 1. Écrit **avant l'implémentation**, comme l'exige le module.

**Pour le lire** : coller le fichier dans [editor.swagger.io](https://editor.swagger.io), ou l'importer dans **Postman** (Import → fichier), qui génère toute la collection de requêtes.

---

## 1. Conventions

| Sujet | Règle | Pourquoi |
|---|---|---|
| Format | JSON simple, dates ISO 8601 | Lisible depuis Nuxt et Postman. API Platform devra être configuré en `application/json` plutôt qu'en JSON-LD par défaut. |
| Langue des routes | **Français** (`/api/logements`, `/api/tenues`) | Même vocabulaire que le modèle de données et que la cliente (D-016). |
| Authentification | JWT en cookie httpOnly (front) **ou** en en-tête `Authorization: Bearer` (Postman) | D-015 |
| Ressource non visible | **404**, jamais 403 | Ne pas confirmer qu'une ressource existe (D-014). |
| Action interdite sur une ressource visible | **403** | |
| Contraire à un invariant | **409** (ex. supprimer son compte en étant propriétaire) | |
| Données invalides | **422**, avec la liste des champs en erreur | |
| Champs protégés | `auteur`, `role`, `proprietaire`, `photo` d'un article : **jamais** acceptés en entrée. Les schémas d'entrée refusent tout champ non prévu. | Règle A-15, test S-03 |
| Pagination | `?page=`, 30 éléments par défaut | Volume de fixtures attendu au pitch 2 (un millier d'articles). |

## 2. Deux vues d'un même article

Le point le plus important du contrat : un article n'a **pas la même forme** selon qui le demande.

| Schéma | Pour qui | Contient |
|---|---|---|
| `ArticleMembre` | Membre du logement | Tout : note, `estPersonnel`, **emplacement** (logement > pièce > stockage) |
| `ArticlePublic` | Visiteur autorisé, lien de partage, article dans une tenue | Photo, catégorie, marque, couleur, pseudo de l'auteur, **nom** du logement. **Ni** pièce, **ni** stockage, **ni** note, **ni** e-mail ou date de naissance. |

C'est la réponse au défaut n°5 de la séance 8-9 (« il y a dans la réponse quelque chose que vous ne devriez pas voir ») et au test S-05.

## 3. L'ajout par photo en deux temps

1. `POST /api/reconnaissances` : envoi de la photo. Le serveur la réduit, la convertit en JPG, retire ses métadonnées et la stocke. Réponse immédiate : l'identifiant de la photo.
   `POST /api/reconnaissances/proposition` (`{"photo": …}`) : un modèle de vision (Google AI Studio, D-040) lit la photo et propose catégorie, marque et couleur, **vide** si le modèle ne répond pas. Cela prend de quelques secondes à plusieurs dizaines : le front ouvre la fiche sans attendre et la remplit à l'arrivée de la proposition (D-041).
2. `POST /api/articles` : l'utilisateur a vérifié et corrigé la proposition, et choisi le rangement. L'article est créé.

**Pourquoi deux temps** : c'est le parcours P2 (photo d'abord, rangement ensuite, D-010), et la proposition de l'IA n'est jamais enregistrée sans validation humaine (H6).

## 4. Correspondance avec les fonctions

| Fonction | Routes |
|---|---|
| F-01 Compte, connexion | `POST /api/auth/inscription`, `/connexion`, `/rafraichir`, `/deconnexion` |
| F-02 Privé / public | `PATCH /api/moi` |
| F-03 Supprimer son compte | `DELETE /api/moi` (409 si propriétaire) |
| F-04 Profil | `GET /api/utilisateurs/{pseudo}`, `/tenues` |
| F-05 Logements | `GET, POST /api/logements`, `GET, PATCH, DELETE /api/logements/{id}` |
| F-06 Pièces, stockages | `POST /api/logements/{id}/pieces`, `PATCH, DELETE /api/pieces/{id}`, `POST /api/pieces/{id}/stockages`, `PATCH, DELETE /api/stockages/{id}` |
| F-07, F-08 Ajout par photo, note | `POST /api/reconnaissances`, `POST /api/reconnaissances/proposition`, `POST /api/articles` |
| F-09, F-10 Modifier, déplacer, personnel | `PATCH, DELETE /api/articles/{id}` |
| F-11 Co-propriétaires | `GET, POST /api/logements/{id}/membres`, `DELETE /api/membres/{id}`, `POST /api/membres/{id}/acceptation`, `GET /api/moi/invitations` |
| F-12 Compte mineur | `GET, POST /api/moi/comptes-mineurs` |
| F-13, F-14 Majorité, suppression en cascade | Traitements serveur (tâche planifiée, suppression du compte), sans route dédiée |
| F-15 S'abonner | `POST, DELETE /api/utilisateurs/{pseudo}/abonnement` |
| F-16 Demandes d'abonnement | `GET /api/moi/demandes-abonnement`, `PATCH, DELETE /api/relations/{id}` |
| F-17, F-18 Amis | `POST /api/moi/amis`, `GET /api/moi/relations`, `PATCH, DELETE /api/relations/{id}` |
| F-19, F-20 Commentaires | `GET, POST /api/articles/{id}/commentaires`, `/api/tenues/{id}/commentaires`, `PATCH, DELETE /api/commentaires/{id}` |
| F-21, F-27 Consulter, filtrer par catégorie | `GET /api/utilisateurs/{pseudo}/articles?categorie=`, `GET /api/articles/{id}` |
| F-22 Lien de partage | `POST /api/articles/{id}/liens`, `GET /api/liens/{jeton}` |
| F-23, F-24 Tenues | `GET, POST /api/tenues`, `GET, PATCH, DELETE /api/tenues/{id}` |
| F-25 Partage Instagram | Côté front : l'image de la tenue est composée par Nuxt à partir de `GET /api/tenues/{id}` |
| F-26 Recherche | `GET /api/articles?q=&categorie=&couleur=&marque=&logement=` |
| — | `GET /api/categories`, `GET /api/articles/{id}/photo` |

## 5. Ce que le contrat ne couvre pas encore

- La **gestion des catégories** par l'administrateur (ajout à la liste fixe) : par commande Symfony en V1, sans route publique.
- Le **mot de passe oublié** : dépend du choix du service d'e-mails (07, § 8).
- L'**image de partage Instagram** générée côté serveur : à réévaluer si la composition côté front ne suffit pas.
