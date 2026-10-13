# Journal de décisions

Chaque décision est consignée **au moment où elle est prise**, avec ce qui a été écarté et pourquoi.
La colonne « IA » indique ce que l'assistant de code a proposé, et ce que j'en ai gardé ou refusé : c'est l'écart entre ce qui a été demandé et ce qui a été livré qui compte.

Statuts possibles : **Validée**, **Hypothèse** (en attente de confirmation du client), **Révisée** (remplacée par une décision plus récente, qu'on référence).

---

## D-001 – Documentation versionnée en Markdown dans le dépôt

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : le module impose que le dépôt soit le seul lieu de vérité et qu'il ne contienne que de la documentation avant le pitch 1.
- **Décision** : toute la documentation de cadrage (compte rendu, périmètre, modèles, choix techniques) est écrite en Markdown dans `docs/`.
- **Alternatives écartées** :
  - Google Docs / Notion : hors du dépôt, donc pas d'historique Git et pas de relecture par pull request.
  - Word / PDF : pas de diff lisible d'une version à l'autre.
- **IA** : l'assistant a proposé la structure du dossier `docs/` ; j'ai demandé de ne créer chaque fichier qu'au moment de le remplir, pour éviter des coquilles vides.

## D-002 – Hébergement sur GitHub, flux Gitflow et pull requests

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : le module exige Gitflow, des merge requests même en solo, et une CI GitHub Actions à partir de la séance 17.
- **Décision** : dépôt hébergé sur GitHub (`EthanMauclair/mmi24e12-WR506D-v2`). Les « merge requests » du module correspondent aux **pull requests** de GitHub. Branches `main`, `develop`, `feature/…`, `hotfix/…`.
- **Alternatives écartées** :
  - GitLab : propose aussi la CI, mais le module cite explicitement GitHub Actions.
- **Conséquence** : `main` et `develop` doivent être protégées sur GitHub (pull request obligatoire) pour qu'un commit direct soit impossible, pas seulement interdit.
- **IA** : rien à signaler.

## D-003 – Compte rendu de lancement reconstitué de mémoire

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : je n'ai pas de notes écrites de la réunion de lancement, et je produis le compte rendu en retard.
- **Décision** : le compte rendu est reconstitué par questions-réponses avec l'assistant. L'assistant pose des questions ouvertes **sans proposer de réponses** ; tout ce que je ne sais pas va dans les questions ouvertes au client au lieu d'être complété.
- **Risque assumé** : des oublis ou des déformations de mémoire. Le client est invité à corriger le compte rendu.
- **IA** : risque principal identifié par le module (l'assistant comble les trous avec ce qu'une application de ce genre contient d'habitude). Parade : le compte rendu sépare strictement « ce que le client a dit », « ce que j'en déduis » et « ce qui reste ouvert ».

## D-004 – Commentaires d'un ami retiré : choix laissé au propriétaire

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : le client permet au propriétaire de retirer un ami (le rétrograder en abonné ou le supprimer) et m'a laissé décider du sort de ses commentaires (Q-08b).
- **Décision** : au moment de changer le statut d'une personne, le propriétaire choisit de **garder ou supprimer ses commentaires**.
- **Alternatives écartées** :
  - Toujours supprimer : efface un historique que le propriétaire peut vouloir garder.
  - Toujours garder : laisse en ligne des commentaires d'une personne qu'on a justement voulu écarter.
- **IA** : rien à signaler.

## D-005 – Générateur de tenue hors version 1

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : le client place le générateur (filtres, météo) en version 2 (Q-16).
- **Décision** : en V1, une tenue est **composée à la main** à partir de ses articles, puis partagée sur Instagram et affichée sur le profil.
- **Conséquence** : le modèle de données de la V1 contient déjà la notion de tenue ; seuls les filtres et la météo sont reportés.
- **IA** : rien à signaler.

## D-006 – La confidentialité se règle par compte

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : le client a d'abord dit que chaque logement est privé ou public (Q-03), puis que c'est le compte (Q-03b). Contradiction relevée (Q-18).
- **Décision** : le **compte** est privé ou public. Sur un compte privé, un abonné doit être accepté avant de voir les articles. Sur un compte public, l'abonnement est direct.
- **Alternatives écartées** :
  - Confidentialité par logement : plus fine, mais contredit la dernière réponse du client et le modèle Instagram qu'il cite.
- **Conséquence** : l'abonnement et l'amitié portent sur un compte, et donnent accès à tous les articles non personnels de ses logements.
- **IA** : la contradiction a été repérée par l'assistant en comparant deux réponses.

## D-007 – Vente hors version 1

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : la vente intégrée (mise en vente jusqu'au paiement) implique un prestataire de paiement, des obligations légales et la gestion des litiges. Le client la place en version 2 (Q-17).
- **Décision** : aucune vente en V1.
- **Conséquence** : la restriction « un compte mineur ne peut pas mettre en vente » n'est pas à implémenter en V1, mais sera à reprendre en V2.
- **IA** : rien à signaler.

## D-008 – Périmètre de la version 1

- **Date** : 12/10/2026
- **Statut** : Validée (à faire valider par le client)
- **Contexte** : priorités de la cliente : ajout par photo, création de compte, partage Instagram (Q-01).
- **Décision** : la V1 comprend les comptes, les logements, l'ajout par photo, les tenues composées à la main, toute la visibilité (abonnés, compte privé/public, articles personnels, amis, commentaires, lien de 24 h) et la co-propriété, y compris le compte mineur. Détail dans [03-perimetre-v1.md](03-perimetre-v1.md).
- **Exclusions** : générateur, vente, pastilles, legs du logement, journal des prêts, valise (E-01 à E-07).
- **Alternatives écartées** :
  - V1 limitée aux trois priorités strictes : le partage et la visibilité n'auraient pas de sens sans abonnés ni comptes privés, et le modèle d'autorisation (cœur de la séance 8-9) ne serait pas testé.
  - Inclure le journal des prêts : hors priorités, ne débloque rien.
- **Risque assumé** : V1 large (25 fonctions). Parade : réalisation par lots démontrables, le lot 1 sert de tranche verticale au pitch 2.
- **IA** : l'assistant a proposé de reporter les pastilles en V2 (recommandation suivie) et l'ordre des lots. Le choix des fonctions incluses est le mien.

## D-009 – Recherche d'article en V1, filtres de tenue en V2

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : en préparant les parcours, deux points sont apparus :
  1. Le problème de la cliente est de **retrouver** ses articles (CR § 2), mais le périmètre ne contenait aucune fonction de recherche.
  2. J'ai d'abord voulu composer les tenues avec des **filtres enregistrés** (« travail »…), ce qui contredisait D-005 : les filtres font partie du générateur, placé en V2.
- **Décision** :
  1. Ajout de **F-26 – Rechercher un article et voir où il est rangé**.
  2. En V1, une tenue se compose par **sélection libre avec un tri par famille**. Les filtres enregistrés arrivent en V2 avec le générateur.
- **Alternatives écartées** :
  - Filtres enregistrés en V1 : utiles sans le générateur, mais ajoutent une fonction à une V1 déjà large.
- **IA** : l'assistant a relevé l'absence de recherche et la contradiction avec D-005. J'ai choisi de garder les filtres en V2.

## D-010 – Choix d'expérience dans les parcours

- **Date** : 12/10/2026
- **Statut** : Validée
- **Décisions** :
  - Après l'inscription, une **création guidée** (logement, pièce, stockage, premier article), plutôt qu'un profil vide : la valeur de l'application n'apparaît qu'une fois un article rangé.
  - Ajout d'article : **photo d'abord, rangement ensuite**, avec un bouton photo accessible partout : on photographie un article quand on l'a en main, pas quand on est devant le bon meuble dans l'application.
  - Lien de partage : la personne sans compte voit **la fiche de l'article et une invitation à créer un compte**.
- **Alternatives écartées** : profil vide après inscription ; choix du rangement avant la photo ; fiche seule sans invitation.
- **IA** : l'assistant a proposé les options ; les choix sont les miens.

## D-011 – Suppression d'un logement : seuls les articles du propriétaire disparaissent

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : hypothèse H16 infirmée. Quand le propriétaire supprime son logement, les co-propriétaires ne doivent pas perdre leurs articles.
- **Décision** : supprimer un logement **retire le propriétaire et ses articles**. Si des co-propriétaires restent, le logement subsiste avec ses pièces, ses stockages et leurs articles, et le **co-propriétaire majeur arrivé en premier** en devient propriétaire (Q-20). **Un mineur n'est jamais propriétaire** : s'il ne reste que des mineurs, ou personne, le logement est entièrement supprimé.
- **Conséquences** :
  - Chaque article doit connaître son **auteur** (H17), indépendamment du propriétaire du logement.
  - C'est un **transfert automatique** de propriété, alors que le legs volontaire est exclu de la V1 (E-04). Les deux devront partager la même règle en V2.
- **Alternatives écartées** :
  - Tout supprimer : les co-propriétaires perdraient des articles qui sont les leurs.
  - Articles des co-propriétaires « à ranger », sans logement : le logement qu'ils partagent existe toujours pour eux.
- **IA** : l'assistant avait supposé l'inverse (H16). Hypothèse corrigée par moi.

## D-012 – Catégorie : un seul champ, filtrable par les visiteurs

- **Date** : 12/10/2026
- **Statut** : Validée
- **Contexte** : le client parle de « catégorie » (vinyle, pull…) pour filtrer les articles d'un logement, et confirme que c'est la même chose que la « famille » remplie par la photo.
- **Décision** :
  - Un **seul champ, « catégorie »** (mot du client), rempli par la photo et corrigeable. Il remplace « famille » dans la documentation.
  - Ajout de **F-27 – Filtrer les articles d'un logement par catégorie**, y compris pour un visiteur.
- **Alternatives écartées** : deux niveaux (famille « vêtement » > catégorie « pull ») : le client n'en a pas exprimé le besoin.
- **IA** : l'assistant a demandé si catégorie et famille étaient la même chose, pour éviter un doublon dans le modèle de données.

## D-013 – Modèle de données

- **Date** : 12/10/2026
- **Statut** : Validée (à présenter au pitch 1)
- **Décisions** : détail dans [05-modele-donnees.md](05-modele-donnees.md).
  - **On s'abonne à une personne, pas à un logement** : un abonné ne voit que les articles de la personne suivie, selon la confidentialité de **son** compte. Les articles d'un co-propriétaire rangés dans le même logement ne sont pas visibles via cet abonnement.
  - **Catégories** : liste fixe, extensible par l'administrateur.
  - Une tenue devenue **vide** est **supprimée**.
  - À **18 ans**, le lien avec le référent disparaît : le compte devient **indépendant**.
  - Une seule table **Membre** (rôle, date d'arrivée) pour propriétaire et co-propriétaires ; une seule table **Relation** (niveau abonné / ami).
- **Alternatives écartées** :
  - Abonnement donnant accès à tout le logement : un co-propriétaire verrait ses articles exposés aux abonnés d'un autre, selon une confidentialité qui n'est pas la sienne.
  - Catégories libres : doublons (« Vinyle », « vinyles », « vynile ») qui rendent les filtres inutilisables.
  - Clé `proprietaire` sur Logement plus une table de co-propriétaires : un transfert de propriété toucherait deux tables.
  - Deux tables Abonné et Ami : rétrograder un ami imposerait de supprimer puis recréer une ligne.
- **IA** : l'assistant a proposé la structure et les invariants ; les quatre arbitrages ci-dessus sont les miens.

## D-014 – Modèle d'autorisation

- **Date** : 13/10/2026
- **Statut** : Validée (à présenter au pitch 1)
- **Contexte** : exercice « Qui a le droit de voir ? » (séance 8-9). Détail dans [06-modele-autorisation.md](06-modele-autorisation.md).
- **Décisions** (mes arbitrages sur les cases non tranchées par le client) :
  - Tout est **interdit par défaut** ; chaque droit est une exception écrite.
  - Le **co-propriétaire mineur** ne renomme pas le logement et ne modifie **que ses propres articles** ; il voit ceux des autres, personnels compris.
  - Une **tenue appartient à une personne**, pas au logement : seuls son auteur la modifie. Les co-propriétaires voient ses tenues, y compris privées, sans pouvoir les modifier.
  - Une **tenue privée** est visible par l'auteur, ses **amis** et les membres d'un logement partagé ; jamais par les abonnés.
  - Un **ami** modifie et supprime ses propres commentaires. Un **mineur** ne supprime pas de commentaire. Un **non-inscrit** voit les commentaires d'un compte public.
  - Un **article personnel** dans une tenue visible est montré **dans la tenue uniquement** (Q-21).
  - Une ressource non autorisée répond **404** plutôt que 403, pour ne pas confirmer son existence.
- **Point de vigilance assumé** : une tenue privée contenant un article personnel est visible par les **amis** ; ils voient donc cet article dans la tenue. C'est cohérent avec Q-21, mais à rappeler à la cliente.
- **IA** : l'assistant a pré-rempli uniquement les cases déjà tranchées par le client et m'a laissé les autres, conformément à la consigne de la séance (« aucune case vide, sinon c'est l'assistant qui décide »). Il a proposé la liste des tests S-01 à S-13.

## D-015 – Choix techniques

- **Date** : 13/10/2026
- **Statut** : Validée (à présenter au pitch 1)
- **Décisions** : détail et justifications dans [07-choix-techniques.md](07-choix-techniques.md).
  - **Symfony 7.4 LTS** et PHP 8.4 : maintenue jusqu'en 2028-2029, alors que 8.1 s'arrête en janvier 2027, pendant le module.
  - **Front découplé en Nuxt** : une seule porte d'entrée (l'API) à sécuriser, interface riche sur mobile, rendu serveur pour les pages publiques et les aperçus de liens.
  - **API Platform**, avec des groupes de sérialisation explicites.
  - **JWT** en cookie httpOnly.
  - **MySQL** : I-05 garanti par le code, une transaction et un test, faute d'index partiel.
  - **Flysystem** : disque en V1, S3 ensuite ; photos servies par l'API, jamais en accès direct.
  - **OpenRouter** pour la reconnaissance, avec catégorie imposée dans la liste fixe.
  - **Un seul dépôt** contenant `api/` et `front/`.
- **IA** : l'assistant a présenté les options et leurs conséquences, et vérifié les dates de maintenance de Symfony au 13/10/2026. Les choix sont les miens ; j'ai retenu MySQL contre sa recommandation (PostgreSQL), en assumant la limite sur I-05.

## D-016 – Contrat d'API

- **Date** : 13/10/2026
- **Statut** : Validée (à présenter au pitch 1)
- **Décisions** : détail dans [08-contrat-api.md](08-contrat-api.md) et [api/openapi.yaml](api/openapi.yaml).
  - Routes **en français**, comme le modèle de données et le vocabulaire de la cliente.
  - **Deux vues d'un article** : `ArticleMembre` (avec emplacement) et `ArticlePublic` (sans pièce, stockage, note ni données personnelles).
  - **Ajout par photo en deux temps** : reconnaissance, puis enregistrement après validation par l'utilisateur.
  - **404** pour une ressource non visible, **403** pour une action interdite sur une ressource visible, **409** pour un invariant violé.
  - Les schémas d'entrée **refusent tout champ non prévu** (`additionalProperties: false`), pour qu'on ne puisse pas envoyer `auteur` ou `role` (A-15).
  - Recherche du propriétaire (`/api/articles`) et consultation d'un visiteur (`/api/utilisateurs/{pseudo}/articles`) sur deux routes distinctes, car elles n'appliquent pas les mêmes règles.
- **Alternatives écartées** :
  - Routes en anglais : plus courant, mais un vocabulaire de plus à traduire entre la cliente, la documentation et le code.
  - Un seul format d'article avec des champs vides selon le profil : un oubli de filtrage exposerait l'emplacement.
  - Création d'article et photo en une seule requête : impossible de corriger la proposition de l'IA avant enregistrement.
- **Vérification** : fichier analysé par un script (syntaxe YAML, 36 chemins, 56 opérations, références et paramètres de chemin cohérents). Une validation complète par un outil OpenAPI reste à faire (éditeur Swagger, ou comparaison avec la documentation générée par API Platform).
- **IA** : l'assistant a rédigé le contrat à partir du périmètre, du modèle de données et du modèle d'autorisation, et proposé les conventions. Relecture complète à faire de mon côté avant le pitch.

## D-017 – Compte mineur toujours privé

- **Date** : 13/10/2026
- **Statut** : Validée
- **Contexte** : question Q-22 posée à la cliente pour protéger les comptes mineurs.
- **Décision** : un compte mineur est **toujours privé** jusqu'à ses 18 ans (I-21). Son passage en public est refusé par l'API (422). À 18 ans, il garde son réglage et peut le changer.
- **Conséquences** : les articles et tenues d'un mineur ne sont jamais visibles par un non-inscrit ; ses abonnés doivent être acceptés. Les articles de Jules dans le logement de Camille ne sont jamais visibles par les abonnés de Camille (règle D-013, renforcée).
- **IA** : l'assistant a proposé la question au client ; la réponse vient de la cliente.
