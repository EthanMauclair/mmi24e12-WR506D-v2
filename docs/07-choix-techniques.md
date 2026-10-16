# Choix techniques – Version 1

- **Rédigé par** : Ethan Mauclair
- **Date** : 13/10/2026
- **Sources** : [le projet (consignes du module)](https://docs.mmi-troyes.fr/books/2627-wr506d-developpement-back-avance/page/le-projet), [périmètre V1](03-perimetre-v1.md), [modèle d'autorisation](06-modele-autorisation.md), [versions de Symfony](https://symfony.com/releases)
- **Statut** : à valider au pitch 1

Chaque choix est justifié par une contrainte du projet, pas par la nouveauté.

## Vue d'ensemble

```
Téléphone ──► Front Nuxt (rendu serveur) ──► API Symfony 7.4 + API Platform ──► MySQL
                                                     │
                                                     ├──► Stockage des photos (Flysystem : disque, puis S3)
                                                     └──► Google AI Studio (reconnaissance des articles, D-040)
```

| Couche | Choix |
|---|---|
| Back | **Symfony 7.4 LTS**, PHP 8.4, **API Platform** |
| Authentification | **JWT** (LexikJWTAuthenticationBundle), jeton en cookie httpOnly |
| Front | Application **découplée** en **Nuxt** (Vue), rendu côté serveur |
| Base de données | **MySQL** |
| Photos | **Flysystem** : disque (volume Docker) en V1, stockage objet S3 ensuite |
| Reconnaissance | **Google AI Studio**, modèle de vision Gemini (D-040) ; OpenRouter possible par configuration |
| Environnement | **Docker** (imposé), tests d'API avec **Postman** (imposé) |

---

## 1. Symfony 7.4 LTS plutôt que 8.x

**Situation au 13/10/2026** (source : symfony.com/releases) :

| Version | Corrections de bugs jusqu'à | Sécurité jusqu'à | PHP minimum |
|---|---|---|---|
| **7.4 LTS** | novembre 2028 | novembre 2029 | 8.2 |
| 8.1 (courante) | janvier 2027 | janvier 2027 | 8.4 |
| 8.2 (prévue en novembre 2026) | juillet 2027 | juillet 2027 | 8.4 |

**Justification, selon les quatre critères du module :**
- **Durée de vie du projet** : le module dure jusqu'en janvier, et l'objectif de la cliente est d'ouvrir l'application à tout le monde, donc de la faire vivre au-delà. Avec 8.x, il faudrait monter de version **pendant** le module (8.1 n'est plus maintenue en janvier 2027), puis tous les six mois. 7.4 reste maintenue jusqu'en 2028-2029 sans changement de version.
- **Qui maintiendra l'application** : une seule personne, sans équipe dédiée aux montées de version. Une LTS réduit ce travail au minimum.
- **Disponibilité des bundles** : les bundles nécessaires (API Platform, LexikJWT, Flysystem, Doctrine, NelmioCors) visent tous la LTS ; avec 8.x, chaque bundle doit d'abord suivre la nouvelle version majeure.
- **Version de PHP** : 7.4 accepte PHP 8.2 à 8.4. On retient **PHP 8.4** pour bénéficier de sa période de maintenance la plus longue, ce qui garde la porte ouverte vers Symfony 8 plus tard.

**Ce qu'on perd** : les nouveautés propres à Symfony 8. Aucune n'est nécessaire au périmètre V1.

## 2. Front découplé en Nuxt

**Les trois options du module et leurs conséquences :**

| Critère | Twig + Stimulus + Turbo | **Nuxt (découplé)** | Rendu serveur sans JS |
|---|---|---|---|
| API | À côté du front : deux portes d'entrée à sécuriser | **Le front ne passe que par l'API** : une seule porte d'entrée, réellement testée | Pas d'API utilisée par le front |
| Responsive / mobile | Bon | **Bon**, interactions riches (photo, filtres, sélection d'articles) | Correct, interactions limitées |
| Référencement | Bon | **Bon grâce au rendu serveur** de Nuxt | Bon |
| Partage public sans compte (profils, liens) | Bon | **Bon** : aperçus des liens générés côté serveur | Bon |
| Projets à maintenir | Un | **Deux** | Un |

**Justification** :
- Le module porte sur une **API**. Avec un front découplé, chaque écran consomme cette API : le modèle d'autorisation (A-01 à A-15) n'a qu'**un seul point d'application**, et chaque faille serait visible depuis le front comme depuis Postman.
- La cliente vit sur son **téléphone** : la prise de photo, les filtres par catégorie et la composition de tenues demandent une interface interactive.
- **Nuxt plutôt qu'une application monopage** : les profils publics et les liens de partage (F-21, F-22) doivent s'afficher correctement quand on les partage (aperçu sur Instagram ou dans une messagerie) et être référencés. Cela exige un rendu côté serveur.
- **Vue plutôt que React** : prise en main plus rapide, pour un front qui n'est pas l'objet principal du module.

**Conséquences** :
- Deux applications dans le même dépôt (`api/` et `front/`), lancées ensemble par Docker.
- Configuration **CORS** (NelmioCorsBundle) pour n'autoriser que l'origine du front.
- Le contrat d'API (OpenAPI) devient le **contrat entre les deux projets** : il doit être écrit avant l'implémentation, comme l'exige le module.

## 3. API Platform

**Justification** :
- Génère la documentation **OpenAPI** à partir du code : le contrat écrit avant l'implémentation pourra être comparé à celui généré.
- La sécurité se déclare **opération par opération** (`security`, voters) : chaque règle A-xx correspond à une expression ou un voter testable.
- Pagination, filtres (par catégorie : F-27) et validation sont fournis.

**Risque assumé** : API Platform expose par défaut **tous** les champs d'une entité. C'est exactement le défaut n°5 de la séance 8-9 (un champ qui ne devrait pas être visible). Parade : des **groupes de sérialisation** explicites sur chaque entité, et le test S-05 (aucun stockage, pièce, e-mail ni date de naissance dans la réponse d'un visiteur).

**Alternative écartée** : des contrôleurs écrits à la main. Contrôle total, mais documentation OpenAPI à maintenir séparément, et beaucoup de code répétitif pour la pagination et les filtres.

## 4. Authentification par JWT

**Justification** :
- Standard pour une API consommée par un front séparé, et simple à utiliser dans **Postman** (imposé).
- Ouvre la porte à une application mobile native plus tard, sans changer l'API.

**Précautions** :
- Le jeton est stocké dans un **cookie httpOnly, Secure, SameSite**, jamais dans le stockage du navigateur, pour qu'un script injecté ne puisse pas le lire.
- **Durée de vie courte** du jeton, avec un jeton de rafraîchissement.
- Les clés de signature ne sont **jamais commitées** (variables d'environnement, secrets Symfony).

**Alternative écartée** : session et cookie classiques. Plus simple sur un même domaine, mais moins pratique avec Postman et pour une future application mobile.

## 5. MySQL

**Justification** : base la plus répandue chez les hébergeurs, déjà maîtrisée, entièrement supportée par Doctrine.

**Limite assumée** : MySQL ne propose pas d'**index unique partiel**. La règle « un seul propriétaire par logement » (**I-05**) ne peut donc pas être garantie directement par une contrainte simple de la base. Parade :
- vérification dans le code (validation et service de transfert de propriété) ;
- **test unitaire** dédié à I-05 ;
- transfert de propriété (D-011) exécuté dans une **transaction**.

**Alternative écartée** : PostgreSQL, qui permettrait de garantir I-05 par un index partiel. Écartée pour rester sur une base maîtrisée ; le compromis est documenté ici.

## 6. Photos : Flysystem, disque puis S3

**Justification** :
- En V1, les photos sont écrites sur le **disque**, dans un volume Docker : rien de plus à faire tourner.
- **Flysystem** isole le code du lieu de stockage : passer à un stockage objet (S3 ou équivalent) en production se fait par configuration, sans toucher au code métier.

**Traitement à l'envoi** (découvertes du test de la séance 5) : l'image est **réduite**, convertie en **JPG**, et ses métadonnées (dont la localisation GPS) sont **supprimées** avant stockage.

**Sécurité** : les photos ne sont **pas** servies directement depuis un dossier public. Elles passent par l'API, qui applique les règles de visibilité : sinon, l'URL d'une photo d'article personnel suffirait à la voir.

## 7. Reconnaissance des articles : modèle de vision

**Justification** : déjà testé lors de la preuve de concept (séance 5).

**Fonctionnement retenu** :
- l'image réduite est convertie en **base64** et envoyée au modèle ;
- le prompt impose de choisir la **catégorie dans la liste fixe** (D-013), et de renvoyer une réponse structurée (catégorie, marque, couleur) ;
- la réponse n'est qu'une **proposition** : l'utilisateur corrige avant d'enregistrer (H6) ;
- en cas d'échec ou de délai dépassé, la saisie se fait à la main : l'article n'est jamais perdu (P2).

**Précautions** : la clé d'API reste côté serveur, jamais dans le front ni dans le dépôt. Les photos envoyées à un service tiers devront être mentionnées dans la politique de confidentialité.

**V1** (D-040, qui remplace D-023) : **Google AI Studio en direct**, modèle `gemini-3.5-flash-lite`, niveau sans frais. Le modèle gratuit d'OpenRouter, prévu au départ, était saturé (erreur 429). Adresse, clé et modèle se règlent par `RECONNAISSANCE_URL`, `RECONNAISSANCE_CLE` et `RECONNAISSANCE_MODELE`. La fiche s'ouvre sans attendre et se remplit à l'arrivée de la proposition (D-041), à vérifier ; un modèle de secours prend le relais si le premier refuse ou tarde, et si Google ne répond pas, la saisie se fait à la main.

## 8. Encore à décider

| Sujet | Quand |
|---|---|
| Envoi d'e-mails (invitation d'un co-propriétaire, mot de passe oublié) : Symfony Mailer avec Mailpit en développement ; prestataire en production à choisir | Avant le lot 6 |
| Hébergement de production | Avant le pitch 2 |
| Remplissage automatique par photo : niveau payant de Google ou politique de confidentialité avant de vraies utilisatrices (D-040) | Avant la mise en production |
