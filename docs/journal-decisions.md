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

## D-018 – Les règles métier vivent dans les entités

- **Date** : 13/10/2026
- **Statut** : Validée
- **Contexte** : début du lot 1. Les invariants (05-modele-donnees.md) doivent être garantis et testés.
- **Décision** :
  - Les entités n'ont **pas de setters libres** : on crée un logement par `Logement::creer()`, un article par `Article::creer()`, etc. Chaque méthode refuse une action qui violerait un invariant, en levant une `RegleMetierException` qui porte son numéro (I-xx).
  - L'heure courante est **passée en paramètre** (`$maintenant`) pour que les règles d'âge soient testables à date fixe.
  - Les champs protégés (auteur d'un article, rôle d'un membre) n'ont **aucune méthode** pour être modifiés directement (A-15).
  - Les invariants sont testés **sans base de données** (tests unitaires), donc rapides.
- **Alternatives écartées** :
  - Entités « anémiques » avec getters/setters générés et règles dans des services : une règle oubliée dans un service suffit à violer un invariant.
  - Contraintes de validation seules : elles ne couvrent pas les règles qui portent sur plusieurs objets (ex. I-11).
- **Hypothèse ajoutée** : H18, un mineur ne rejoint que le logement de son référent.
- **IA** : l'assistant a écrit les entités et les tests ; j'ai validé le choix d'entités riches et l'hypothèse H18. Tests exécutés sous PHP 8.4 : 20 tests, 43 assertions.

## D-019 – API Platform avec des ressources dédiées (DTO)

- **Date** : 13/10/2026
- **Statut** : Validée (précise D-015)
- **Contexte** : nos entités n'ont pas de setters (D-018), alors qu'API Platform modifie par défaut les entités champ par champ.
- **Décision** : les ressources de l'API sont des **classes dédiées** (`src/ApiResource/`), distinctes des entités :
  - un **provider** (`src/State/*Provider`) charge les données et applique les droits ;
  - un **processor** (`src/State/*Processor`) exécute l'action en appelant les méthodes métier des entités ;
  - un **voter** (`LogementVoter`) porte les règles A-01 à A-11, un service (`AccesLogement`) applique la règle 404 / 403 (D-014) ;
  - les ressources listent leurs champs un par un, et les corps de requête refusent tout champ non prévu (A-15).
- **Codes de réponse** : 401 sans connexion, 404 pour une ressource qu'on ne voit pas, 403 pour une action interdite sur une ressource visible, 409 pour un invariant violé.
- **Alternatives écartées** :
  - Contrôleurs Symfony classiques (comme l'authentification) : plus simples, mais pas de documentation `/api/docs` générée.
  - Exposer directement les entités : impossible sans setters, et risque d'exposer des champs internes (défaut n°5 de la séance 8-9).
- **Hypothèse ajoutée** : H19, on ne retire pas un co-propriétaire qui a encore des articles. **Remplacée par D-020** après réponse de la cliente.
- **IA** : l'assistant a proposé les contrôleurs ; j'ai choisi API Platform pour rester fidèle à D-015. 51 tests, dont les tests de sécurité S-01, S-04, S-05, S-07 et S-08.

## D-020 – Retrait d'un co-propriétaire

- **Date** : 13/10/2026
- **Statut** : Validée
- **Contexte** : hypothèse H19 soumise à la cliente (Q-23).
- **Décision** : seul le **propriétaire** retire un co-propriétaire ; un co-propriétaire peut **partir** de lui-même. Dans les deux cas, **ses articles sont supprimés** (A-17). Les autres co-propriétaires majeurs ne retirent plus personne.
- **Conséquence** : action irréversible ; le front devra demander une confirmation explicite en indiquant le nombre d'articles supprimés.
- **IA** : l'assistant avait proposé de refuser le retrait (H19) ; la règle vient de la cliente.

## D-021 – Liste initiale des catégories

- **Date** : 13/10/2026
- **Statut** : Proposée (à montrer à la cliente)
- **Contexte** : D-013 fixe une liste de catégories extensible par l'administrateur, sans en donner le contenu (« vinyle, pull, baskets… »).
- **Décision** :
  - 31 catégories au singulier, remplies par une migration de données : vêtements (T-shirt, Chemise, Pull, Sweat, Veste, Manteau, Pantalon, Jean, Short, Jupe, Robe, Pyjama, Sous-vêtement, Maillot de bain), chaussures (Baskets, Bottines, Bottes, Sandales, Chaussures de ville), accessoires (Sac, Ceinture, Écharpe, Chapeau, Bonnet, Bijou, Montre, Lunettes), objets (Vinyle, Livre, Jeu vidéo) et **Autre**, que la reconnaissance photo choisira quand rien ne convient.
  - `GET /api/categories` est public et en lecture seule ; l'administrateur ajoute une catégorie avec `php bin/console app:categorie:ajouter <nom>`.
  - Les doublons sont refusés sans tenir compte de la casse ni des accents (« vinyle », « Echarpe ») : l'interclassement MySQL `utf8mb4_0900_ai_ci` et l'index unique sur `nom` le garantissent.
- **Alternatives écartées** : deux niveaux (famille > catégorie), déjà écartés par D-012 ; une route d'administration, inutile en V1 (docs/08-contrat-api.md).
- **IA** : l'assistant a proposé la liste ; elle reste à valider avec la cliente.

## D-022 – Articles : photo, formes de réponse et droits

- **Date** : 13/10/2026
- **Statut** : Validée
- **Contexte** : mise en œuvre de `POST /api/reconnaissances` et de `/api/articles` (F-07 à F-10, F-26).
- **Décisions** :
  - **Photo** : réduite à 1 600 px, redressée, réencodée en JPG (ce qui supprime les métadonnées, dont la position GPS), rangée sous `photos/{id de l'auteur}/`. Elle ne sert qu'à celui qui l'a envoyée et qu'à un seul article. Formats acceptés : **JPG, PNG, WebP**. Le **HEIC** est retiré du contrat : l'extension GD ne le lit pas, et les navigateurs des iPhone envoient en général un JPG.
  - **Proposition vide** tant qu'OpenRouter n'est pas branché : le contrat prévoit déjà ce cas (saisie à la main).
  - **Une seule ressource** pour les deux vues : les champs réservés aux membres (note, `estPersonnel`, emplacement, date) restent nuls pour un visiteur et API Platform omet les valeurs nulles. Conséquence : une marque ou une couleur vide est aussi omise dans la vue membre.
  - **S-13** : créer ou déplacer un article vers un rangement d'un logement dont on n'est pas membre répond **403**, que le rangement existe ou non, pour ne rien révéler.
  - **Droits d'écriture** : on applique A-10 et A-11 (un membre majeur modifie et supprime tous les articles du logement, un mineur seulement les siens), plutôt que la phrase du modèle de données qui réserve la visibilité et la suppression à l'auteur (H17). H17 sert à savoir quels articles supprimer quand un membre part (D-011, D-020).
  - **Visiteurs** : en attendant les relations, un non-membre voit un article non personnel si le compte de son auteur est public (A-03 sans abonnés ni amis).
- **IA** : l'assistant a proposé ces choix pendant l'implémentation ; le point H17 / A-10 reste à confirmer avec la cliente si besoin.

## D-023 – Remplissage automatique par photo reporté après la V1

- **Date** : 14/10/2026
- **Statut** : Validée (à annoncer à la cliente)
- **Contexte** : F-07 prévoit que la catégorie, la marque et la couleur soient remplies automatiquement à partir de la photo (docs/07-choix-techniques.md, § 7). Le branchement sur OpenRouter a été écrit et testé, mais l'essai réel avec le modèle gratuit `google/gemma-4-31b-it:free` a échoué : le fournisseur (Google AI Studio) renvoie « 429 temporarily rate-limited upstream » sur le quota partagé des modèles gratuits, indépendamment de notre compte.
- **Décision** : en V1, l'ajout par photo s'arrête au **scan** : `POST /api/reconnaissances` enregistre la photo (réduite, redressée, sans métadonnées) et renvoie `proposition: null` ; l'utilisateur remplit catégorie, marque et couleur **à la main** avant `POST /api/articles`. Le contrat d'API ne change pas : le front gère déjà la proposition vide (P2).
- **Alternatives écartées** :
  - Garder le modèle gratuit : réponses aléatoires (429 fréquents), donc une fonction qui marche « parfois », difficile à démontrer.
  - Changer de modèle gratuit ou ajouter une clé Google personnelle (BYOK) : possible, mais le résultat reste soumis aux quotas gratuits ; à réévaluer après la V1.
  - Un modèle payant : coût à valider, hors du temps du lot 2.
- **Conséquences** :
  - F-07 est livrée **partiellement** en V1 (photo sans remplissage automatique) : à présenter à la cliente.
  - Aucune photo n'est envoyée à un service tiers en V1 : rien à ajouter à la politique de confidentialité pour l'instant.
  - Le travail est conservé sur la branche locale `feature/reconnaissance-ia` (service `ReconnaissanceArticles`, double de test `OpenRouterSimule`, 7 tests) : catégorie hors liste → « Autre », toute panne → proposition vide, clé dans `.env.local`.
- **IA** : l'assistant a écrit le branchement et diagnostiqué l'erreur 429 ; j'ai décidé de reporter la fonction plutôt que de dépendre d'un quota gratuit.

## D-024 – Relations : abonnés et amis

- **Date** : 14/10/2026
- **Statut** : Validée (un point à confirmer avec la cliente)
- **Contexte** : mise en œuvre de F-15 à F-18 (entité `Relation`, routes de docs/08-contrat-api.md § 4) et élargissement de A-03 aux relations acceptées.
- **Décisions** :
  - **Qui gère une relation** : seule la personne **suivie** accepte, rétrograde ou supprime par `/api/relations/{id}` ; le suiveur y reçoit un 403 et se désabonne par `DELETE /api/utilisateurs/{pseudo}/abonnement`. Une relation reste invisible (404) pour les autres comptes. `GET /api/relations/{id}` est ajouté au contrat pour la relire.
  - **Ajouter un ami** (`POST /api/moi/amis`) : crée une relation AMI acceptée de cette personne vers moi, ou élève sa relation existante (abonné ou demande en attente, qui est alors acceptée). 409 si elle est déjà amie.
  - **I-17 répond 409** : se suivre soi-même ou suivre deux fois la même personne.
  - **Passer son compte en public** accepte les demandes encore en attente, pour rester cohérent avec I-18.
  - **Commentaires (D-004)** : le choix « garder ou supprimer » (`supprimerCommentaires`) est déjà accepté par `PATCH` et `DELETE /api/relations/{id}` pour ne pas changer le contrat, mais reste **sans effet** jusqu'aux commentaires (lot 5, F-19, F-20), où il sera branché.
  - **A-03** : un non-membre voit un article non personnel si le compte de son auteur est public **ou** s'il a une relation acceptée (abonné ou ami) avec cet auteur. Une demande en attente ne donne rien. On suit une personne, pas un logement (D-013).
- **À confirmer** : un compte **mineur** gère lui-même ses demandes d'abonnement (comme Jules dans les tests). Faut-il que son référent les valide à sa place ?
- **IA** : l'assistant a proposé ces choix pendant l'implémentation.

## D-025 – Front : session, accès aux pages et mise en page

- **Date** : 14/10/2026
- **Statut** : Validée
- **Contexte** : premiers écrans du front Nuxt (écrans 1 et 2 des maquettes), branchés sur l'authentification de l'API (D-015).
- **Décisions** :
  - **Le front ne lit jamais le jeton.** Il reste dans le cookie httpOnly `BEARER`. Pour savoir qui est connecté, le front demande `GET /api/moi` ; un 401 veut dire « personne ». Le jeton renvoyé aussi dans la réponse de connexion (pour Postman) est ignoré.
  - **Deux adresses pour l'API** : pendant le rendu serveur, Nuxt joint l'API par le réseau Docker (`http://nginx`) et recopie l'en-tête `Cookie` du navigateur ; dans le navigateur, il appelle `http://localhost:8080` avec `credentials: 'include'`. Le front (port 3000) et l'API (port 8080) sont sur le **même site** (`localhost`) : le cookie `SameSite=Lax` est donc bien envoyé.
  - **Pages réservées par défaut**, comme l'API (D-014) : un middleware global renvoie vers la connexion, sauf pour les pages marquées `public` (profils publics, liens de partage, à venir) ou `invite` (connexion, inscription). Ce n'est qu'un confort : la protection reste l'API.
  - **Retour après connexion** : la page demandée est passée dans `?retour=`, et seul un chemin interne est accepté, pour ne pas servir de redirection vers un autre site.
  - **Connexion refusée** : un seul message (« identifiant ou mot de passe incorrect »), qui ne dit pas si le compte existe.
  - **CSS écrit à la main**, sans bibliothèque de composants : six écrans, une couleur, deux polices ; une bibliothèque ajouterait du poids sans rien apporter.
  - **Navigation complète dès maintenant** : les entrées qui arrivent avec les prochaines branches (recherche, tenues, profil, ajout) sont affichées mais inactives, pour ne pas changer la mise en page à chaque étape. « Mes logements » sert d'accueil.
  - **Fidèle au canevas « Maquettes PENDERIE »**, avec trois écarts volontaires :
    - « Photographiez un article, il se range tout seul » devient « rangez-le dans le bon placard » : en V1, la photo ne remplit rien (D-023) et c'est la personne qui choisit le rangement (D-010) ;
    - le lien « Découvrir sans compte » n'est pas affiché : il mène aux profils publics (F-21), pas encore construits ;
    - l'icône « réglages » de l'en-tête mobile est remplacée par « se déconnecter », seule action de compte disponible pour l'instant.
- **Conséquences** :
  - Les messages de validation par défaut de Symfony (longueur, e-mail…) sont en anglais : le formulaire d'inscription vérifie donc les mêmes règles en français avant l'envoi. Traduire les messages de l'API reste à faire.
  - Un jeton expiré dans le cookie fait répondre 401 à toutes les routes, même publiques, tant que le navigateur garde le cookie. Le cookie et le jeton ont la même durée (1 h), donc le cas reste rare ; il sera réglé avec `/api/auth/rafraichir`.
- **IA** : l'assistant a proposé et écrit ces choix ; les essais ont été faits dans le navigateur (inscription, connexion, rendu serveur, déconnexion, mauvais mot de passe, mobile et desktop).

## D-026 – Police sans empattements et mot de passe visible

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : à l'essai du front, les titres en Cormorant Garamond (police avec empattements, fine) étaient jugés peu lisibles. Rien ne permettait non plus de vérifier le mot de passe saisi, ce qui est pénible au téléphone.
- **Décisions** :
  - **Une seule police, Atkinson Hyperlegible**, pour les titres comme pour le texte. Elle est dessinée par le Braille Institute pour les personnes malvoyantes : chaque lettre se distingue des autres (l, I, 1, O, 0). Elle n'existe qu'en deux graisses (normale et grasse), ce qui simplifie aussi la hiérarchie. Les tailles des titres sont réduites, une police sans empattements en gras paraissant plus grande.
  - **Bouton « Afficher le mot de passe »** sur la connexion et l'inscription (composant `ChampMotDePasse`), avec un libellé lu par les lecteurs d'écran et l'état `aria-pressed`. Le mot de passe reste masqué par défaut.
- **Alternatives écartées** :
  - Garder Manrope seule : sans empattements aussi, mais ses lettres « l » et « I » se confondent.
  - Un second champ « confirmer le mot de passe » : il double la saisie ; l'affichage en clair permet la même vérification.
- **Conséquences** : les canevas de maquettes gardent l'ancienne police, le front fait foi (docs/09-maquettes.md).
- **IA** : demandé par moi après essai ; l'assistant a proposé la police et écrit le composant.

## D-027 – Dépendances et cache gardés dans des volumes Docker

- **Date** : 14/10/2026
- **Statut** : Validée
- **Contexte** : sous Windows, chaque requête à l'API prenait **plus de 10 s** et le front Nuxt presque 2 min pour démarrer. Mesure : la même requête, sur une copie du projet placée à l'intérieur du conteneur, prend 0,3 s. En mode dev, Symfony vérifie à chaque requête que son cache est à jour, en relisant la date de milliers de fichiers (`vendor/`, `src/`, `config/`) ; à travers le partage de fichiers Windows de Docker Desktop, chaque lecture est lente.
- **Décision** : `api/vendor/`, `api/var/` et `front/node_modules/` deviennent des **volumes Docker** ; seul le code qu'on modifie reste partagé avec Windows. Le conteneur PHP tourne en `www-data` (l'utilisateur de PHP-FPM), pour que le cache et les photos écrits en ligne de commande restent modifiables par l'application.
- **Alternatives écartées** :
  - Désactiver le mode debug en dev : rapide, mais chaque modification demanderait de vider le cache à la main.
  - Mettre tout le projet dans WSL 2 : la vraie solution sous Windows, mais elle change l'installation de chaque poste ; hors de portée maintenant.
  - Faire tourner PHP-FPM en root : règle les droits, mais un processus web en root n'est pas défendable, même en local.
- **Conséquences** :
  - Requêtes de l'API : **de 11 s à 1-2 s** ; suite de tests : **de 4 min à 30 s** ; démarrage du front : **de 1 min 48 à 10 s**.
  - Les dépendances s'installent uniquement dans les conteneurs ; les dossiers `vendor/` et `node_modules/` de la machine ne servent plus (l'éditeur peut perdre l'autocomplétion des bibliothèques).
  - Les fichiers de `var/` (cache, `phpunit.txt`, photos) se lisent par `docker compose exec php …`. `docker compose down -v` efface aussi ces volumes.
- **IA** : l'assistant a mesuré la lenteur et proposé la correction ; les 93 tests passent après le changement.

## D-028 – Profil et paramètres du compte

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : après la connexion, le front affichait directement l'e-mail et la date de naissance. Les maquettes mènent au profil (écran 4), et ces informations relèvent des réglages du compte. Le profil attendait `GET /api/utilisateurs/{pseudo}` (F-04), pas encore codé.
- **Décisions** :
  - **API F-04** : `GET /api/utilisateurs/{pseudo}` renvoie le pseudo, la visibilité, le nombre d'abonnés (abonnés et amis acceptés) et, si je suis connecté, ma relation avec ce compte. Accessible sans compte, jamais d'e-mail ni de date de naissance (S-05).
  - **`nombreAbonnes` est toujours visible**, même pour un compte privé : il ne dit rien du contenu.
  - **`nombreArticles` (ajouté au contrat) suit A-03** : absent si je ne peux pas voir les articles de la personne, pour que le compteur ne révèle pas ce que la liste cache. La règle est écrite une seule fois (`AccesProfil`) et partagée avec `GET /api/utilisateurs/{pseudo}/articles`.
  - **Front** : la connexion mène à `/profil` (écran 4). L'e-mail, la date de naissance, la visibilité, le mot de passe et la déconnexion passent dans **`/profil/parametres`**, ouvert par l'icône des réglages (mobile) ou « Paramètres » (desktop), comme sur les maquettes. Seuls les champs modifiés sont envoyés à `PATCH /api/moi`.
  - **Tenues** : le compteur affiche 0 et l'onglet annonce la fonction, en attendant F-23.
- **Dette assumée** : changer son mot de passe ou son e-mail ne demande pas le mot de passe actuel. Quelqu'un qui trouve une session ouverte pourrait s'approprier le compte. À ajouter côté API (champ `motDePasseActuel` exigé pour ces deux changements).
- **IA** : demandé par moi après essai ; l'assistant a écrit la route, ses tests et les deux écrans.

## D-029 – Création guidée du logement (écran 3)

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : écran 3 des maquettes (F-05, F-06, D-010) ; branche `feature/front-logement`.
- **Décisions** :
  - **Tout est préparé dans la page, puis envoyé à la validation** : le logement, puis chaque pièce, puis chaque rangement (`POST /logements`, `/logements/{id}/pieces`, `/pieces/{id}/stockages`). On peut ajouter, renommer et retirer avant d'enregistrer, sans appel à l'API.
  - **Un nouvel essai ne recrée rien** : si une requête échoue en cours de route, ce qui est déjà enregistré est retenu et sauté au nouvel essai, pour ne pas créer deux fois le même logement.
  - **Parcours guidé seulement pour le premier logement** : l'inscription y mène (D-010), avec « Étape 1 sur 2 », « Plus tard » et « Photographier un premier article ». Pour un logement suivant, l'écran devient « Nouveau logement » avec « Créer le logement ».
  - **« Mes logements »** liste chaque logement avec ses pièces et rangements (un appel `GET /logements/{id}` par logement : la liste ne contient pas les pièces).
- **Conséquences** : une pièce ou un rangement ne se renomme ou ne se supprime pas encore depuis « Mes logements » (les routes existent : à brancher).
- **IA** : l'assistant a écrit l'écran d'après la maquette ; essais dans le navigateur (création complète, nom vide refusé, desktop et mobile).

## D-030 – Ajout d'un article par photo (écrans 5 et 6)

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : écrans 5 et 6 des maquettes (F-07, F-08, F-10, P2) ; branche `feature/front-ajout-article`. Le remplissage automatique est reporté (D-023).
- **Décisions** :
  - **Une seule page `/ajout` en deux étapes** (photo, puis fiche) : la photo et son aperçu ne se perdent pas entre deux pages, et « Changer de photo » revient simplement à l'étape 1.
  - **Photo** : sur téléphone, la caméra s'ouvre dans la page (`getUserMedia`) ; si elle est refusée ou indisponible, le déclencheur ouvre l'appareil photo du téléphone (`<input capture>`). Sur ordinateur : glisser-déposer, choix d'un fichier, ou webcam. Formats et taille vérifiés avant l'envoi (JPG, PNG, WebP, 10 Mo), comme l'API.
  - **Fiche saisie à la main** (D-023) : le bandeau « Proposition automatique » de la maquette devient « Fiche de l'article ». Le lien « Saisir à la main, sans photo » n'est pas repris : l'API exige une photo pour créer un article (P2 : la photo est toujours gardée, seule la reconnaissance peut échouer).
  - **Rangement** choisi après la photo (D-010), dans une liste « Logement › Pièce › Rangement » ; s'il n'y en a aucun, la fiche renvoie vers la création du logement.
- **Dette assumée** : une photo envoyée puis abandonnée (fiche annulée) reste dans le stockage sans article. À nettoyer par une tâche planifiée (photos de plus de 24 h sans article).
- **Limite connue** : la caméra intégrée exige HTTPS ou `localhost`. Depuis un téléphone sur le réseau local, il faudra un certificat (et ouvrir le CORS à cette adresse) ; en attendant, le repli sur l'appareil photo du téléphone fonctionne.
- **IA** : l'assistant a écrit les deux étapes d'après la maquette ; essai complet dans le navigateur (photo déposée, fiche, article visible sur le profil avec sa photo).

## D-031 – Le front relit lui-même ses fichiers sous Docker

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : après la fusion des PR #26 et #27 et un `git pull`, `/logements` et `/logements/nouveau` répondaient 404 alors que les fichiers étaient bien dans `develop`. Docker Desktop sous Windows ne transmet pas au conteneur les événements « fichier modifié » du dossier partagé : le serveur Nuxt ne voyait pas les nouvelles pages et servait l'état d'avant le `git pull`.
- **Décision** : avec `SCRUTATION_FICHIERS=1` (posé dans `compose.yaml`), Nuxt et Vite relisent les fichiers toutes les demi-secondes (`usePolling`), au lieu d'attendre une notification.
- **Alternatives écartées** :
  - Redémarrer le conteneur après chaque `git pull` : facile à oublier, et c'est ce qui a fait croire que les écrans n'étaient pas codés.
  - Placer le projet dans WSL 2 : règle aussi la lenteur (D-027), mais change l'installation du poste.
- **Conséquences** : un peu de processeur en plus pendant le développement ; une page ajoutée ou modifiée apparaît sans redémarrer.
- **IA** : l'assistant avait contourné le problème pendant ses essais en redémarrant le conteneur, sans le corriger ni le signaler. Corrigé après le retour de l'étudiant.

## D-032 – Voir les articles d'une pièce et compléter la saisie des rangements

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : retour de l'étudiant après un essai du parcours P1 et P2 :
  - les pièces de « Mes logements » n'étaient pas cliquables, on ne pouvait pas voir ce qu'elles contiennent (P3 : « où est ma veste ? ») ;
  - la fiche d'un nouvel article ne proposait pas un logement tout juste créé. Ses pièces n'avaient pas de rangement, et la fiche ne listait que les pièces qui en ont un. Aucun message ne l'expliquait, et le modèle exige un rangement pour chaque article.
- **Décisions** :
  - **API** : `GET /api/articles` accepte `?piece=` et `?stockage=` (contrat mis à jour), toujours limités aux logements dont je suis membre (A-01). On filtre côté serveur et non dans le navigateur : la liste est paginée par 30.
  - **Page d'une pièce** (`/logements/{id}/pieces/{piece}`) : ses articles, personnels compris, avec leur rangement ; filtre par rangement repris dans l'adresse (`?rangement=`), pour qu'un clic sur un rangement dans « Mes logements » y mène ; « Voir plus » au-delà de 30.
  - **Ajouter un rangement là où il manque** : depuis la page de la pièce (« + Rangement ») et depuis la fiche d'un nouvel article, sans quitter l'ajout.
  - **La fiche liste toutes les pièces**, y compris celles sans rangement (non sélectionnables, avec une invitation à en créer un).
- **IA** : le défaut de la fiche venait du code écrit par l'assistant (filtre silencieux sur les pièces sans rangement) ; reproduit puis corrigé.

## D-033 – Outils de qualité et vulnérabilités acceptées

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : séance 15 (qualité du code). Résultats détaillés dans [audit.md](../audit.md).
- **Décisions** :
  - **PHPStan niveau 6, sans baseline**, sur `src/` et `tests/`, avec les extensions Symfony, Doctrine et PHPUnit. Les 16 erreurs trouvées sont corrigées, aucune n'est ignorée.
  - **Style `@Symfony`** (PHP-CS-Fixer), sauf deux règles : les noms de tests restent en snake_case, préfixés par la règle testée (convention du projet), et pas de point ajouté après « … ».
  - **Makefile** à la racine : chaque commande passe par Docker ; `make qualite` lance tout ce que lancera la CI.
  - **Front : 2 vulnérabilités critiques corrigées** (`simple-git`, via `@nuxt/devtools`) en imposant `@nuxt/devtools` 3.4.3 (`overrides`).
  - **Front : 11 vulnérabilités hautes acceptées**, faute de version corrigée publiée :
    - `node-forge` ≤ 1.4.0 sert seulement au certificat HTTPS du serveur de développement, et la faille porte sur une vérification de signature que nous n'utilisons pas ;
    - `braces` ≤ 3.0.3 et les paquets qui en dépendent : déni de service possible sur des motifs de fichiers, mais ces motifs sont écrits par nous et utilisés à la compilation, jamais saisis par un utilisateur.
    - Aucun des deux n'est dans le serveur de production. À revoir à chaque `make audit`.
- **Alternatives écartées** :
  - `npm audit fix --force` : rétrograde Nuxt en 4.1.3 sans corriger ces deux paquets.
  - Une baseline PHPStan : inutile pour 16 erreurs, et elle cacherait la dette au lieu de la montrer.
  - `php:8.6-rc-zts-alpine3.24`, proposée par Snyk : version candidate de PHP 8.6, en mode multi-thread inutile ici.
  - Garder Debian en mettant les paquets à jour : 220 failles sur 288 n'ont pas de correction.
  - **Snyk** est branché sur le dépôt GitHub (branche `develop`), sans les PR de correction automatiques : elles contourneraient le Gitflow (`feature/…` vers `develop`) et le journal.
  - **Image PHP sur Alpine** (`php:8.4-fpm-alpine` au lieu de `php:8.4-fpm`). Snyk relevait 288 failles dans le système Debian de l'image (3 critiques dans `perl`, 23 hautes), dont 220 sans correction publiée ; Trivy en compte 2 critiques et 124 hautes. Avec Alpine, Trivy n'en trouve plus que 2 moyennes, pour la même version de PHP. `www-data` garde l'identifiant 33 pour que les volumes existants restent utilisables.
- **IA** : l'assistant a installé et configuré les outils, corrigé les erreurs, lancé axe-core sur les écrans et rédigé l'audit ; les vulnérabilités acceptées sont un choix de l'étudiant.

## D-034 – Correctifs de sécurité appliqués à la construction de l'image PHP

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : après le passage à `php:8.4-fpm-alpine` (D-033), Snyk relevait encore 22 failles dans l'image : 0 critique, 1 haute (`zlib`), 1 moyenne (`nghttp2`), 20 faibles (`curl`). Toutes sont corrigées dans des paquets Alpine publiés après l'image de base. Côté front, Snyk relève une faille haute de plus que `npm audit` : `ioredis`.
- **Décisions** :
  - Le Dockerfile lance **`apk upgrade`** à la construction. Trivy ne trouve plus **aucune faille** dans l'image construite. Snyk affiche toujours les 22 failles, parce qu'il analyse l'image de base déclarée, pas l'image construite.
  - **`ioredis` accepté**, comme `node-forge` et `braces` (D-033). `unstorage` ne s'en sert que pour son connecteur Redis, que l'application n'utilise pas, et il n'existe pas de version compatible avec Nuxt.
- **Conséquences** : reconstruire l'image régulièrement (`docker compose build php`) suffit à récupérer les correctifs suivants. Côté front, Snyk liste 3 failles hautes et 1 moyenne, toutes héritées de Nuxt et sans correctif.
- **IA** : l'assistant a lu les résultats de Snyk, vérifié l'image construite avec Trivy et relancé les 98 tests.

## D-035 – Formulaires sensibles en POST et attente de l'hydratation

- **Date** : 15/10/2026
- **Statut** : Validée
- **Contexte** : premiers tests Cypress (séance 16). En mode développement, la page arrive du serveur quelques secondes avant que Vue prenne la main dans le navigateur (hydratation). Un clic sur « Se connecter » pendant ce délai envoyait le formulaire comme un formulaire HTML ordinaire, **en GET** : l'identifiant et le **mot de passe partaient dans l'adresse** (`/connexion?identifiant=…&motDePasse=…`), donc dans l'historique du navigateur et dans les journaux du serveur. Une vraie personne peut le provoquer sur une connexion lente.
- **Décisions** :
  - Les formulaires qui contiennent un mot de passe ou un e-mail (connexion, inscription, paramètres) déclarent **`method="post"`**. Un envoi trop tôt ne met plus jamais ces données dans l'adresse. Une fois Vue prêt, rien ne change : l'envoi passe toujours par l'API.
  - Un plugin client pose **`data-pret`** sur `<html>` quand l'hydratation est terminée ; les tests de bout en bout l'attendent avant d'agir.
- **Alternatives écartées** :
  - Désactiver les boutons jusqu'à l'hydratation : la page resterait inutilisable sans JavaScript, et l'adresse pourrait toujours être construite par une autre voie.
  - Faire attendre les tests un délai fixe : lent, et fragile.
- **IA** : défaut révélé par les tests Cypress écrits par l'assistant (échecs aléatoires analysés) ; l'assistant a proposé la correction.

## D-037 – Recherche et fiche d'un article

- **Date** : 16/10/2026
- **Statut** : Validée
- **Contexte** : fin du lot 1 (F-09, F-10, F-26, parcours P3) ; branche `feature/recherche-fiche-article`. Les maquettes n'ont pas d'écran de recherche ni de fiche d'article : on reprend les composants des écrans 4 et 6 (grille, fiche, puces, boutons). Dans P3, Camille cherche « veste », qui est une **catégorie** : la recherche par texte ne regardait que la marque, la couleur et la note, et ne trouvait donc rien.
- **Décisions** :
  - Le texte de `GET /api/articles?q=` porte aussi sur le **nom de la catégorie**.
  - Écran **Rechercher** (`/recherche`) : un champ de texte et six filtres (logement, pièce, rangement, catégorie, couleur, marque). Tous les critères sont **dans l'adresse** : la recherche se retrouve telle quelle au retour de la fiche, et se partage. Le texte part après une pause de 350 ms dans la frappe ; les listes déroulantes, dès le choix. Sur téléphone, les filtres sont repliés derrière un bouton « Filtres (n) ».
  - Pagination par **30**, avec « Page précédente » et « Page suivante ». L'API ne renvoie pas de total : une page pleine annonce une page suivante (au pire, la dernière est vide).
  - **Fiche d'un article** (`/articles/{id}`) : photo, catégorie, marque, couleur, note et **emplacement** (logement › pièce › rangement, avec des liens vers la pièce). Actions : **Déplacer** (liste des rangements de tous mes logements), **Modifier** (champ vidé = information retirée), interrupteur **Article personnel** (enregistré aussitôt), **Supprimer** (confirmation dans une fenêtre `<dialog>`). Les droits restent à l'API (A-10, A-11) : un refus s'affiche tel quel.
  - Les vignettes de la page d'une pièce et du profil mènent à la fiche.
  - Les zones utiles aux tests de bout en bout portent un attribut **`data-cy`** (barre du bas, menu latéral, grilles, recherche, fiche) : les tests ne dépendent plus des classes CSS.
- **Alternatives écartées** :
  - Défilement infini : moins accessible (le pied de page devient inatteignable, on perd sa place) ; le bouton « Voir plus » de la page d'une pièce reste, il s'agit d'une liste courte.
  - Ajouter un total à la réponse de l'API (format JSON-LD ou en-tête) : changement de contrat inutile pour la V1.
- **Conséquences** : l'entrée « Rechercher » du menu est active ; seule « Mes tenues » reste grisée. Restent du lot 1 : renommer et supprimer une pièce ou un rangement.
- **IA** : écrans, extension de la recherche et test fonctionnel écrits par l'assistant, vérifiés dans le navigateur (compte de démonstration, mobile et desktop) et avec axe-core.

## D-038 – Intégration continue GitHub Actions

- **Date** : 16/10/2026
- **Statut** : Validée
- **Contexte** : séances 17-18. À partir d'ici, la CI est bloquante : un workflow à chaque push et à chaque PR, `composer audit` compris, et une PR rouge ne se fusionne pas. Branche `feature/ci`.
- **Décisions** :
  - Un seul workflow, `.github/workflows/qualite.yml`, qui lance **`make qualite`** (linters, PHPStan, style, audit des dépendances, tests PHPUnit) **dans les mêmes conteneurs Docker que sur le poste**. Il tourne à chaque push, sur toutes les branches, et sur chaque PR vers `develop` ou `main`. Un nouveau push annule la vérification en cours sur la même branche.
  - **Aucun secret stocké sur GitHub** : à chaque exécution, la CI tire au sort `APP_SECRET` et la phrase de passe JWT (`api/.env.local`, `api/.env.test.local`), puis génère des clés JWT jetables. Rien ne sort de la machine de CI, qui est détruite à la fin.
  - **Audit du front** : `npm audit` passe par `front/scripts/audit-npm.mjs`, qui contient la liste des failles acceptées (D-033 : `node-forge`, `braces`, par leur identifiant GHSA). Toute autre faille haute ou critique fait échouer `make audit`, donc la CI. Accepter une nouvelle faille demande de l'ajouter à la liste **et** au journal.
  - `make audit` lance `npm audit` dans un conteneur `front` temporaire (`docker compose run`) : il n'a plus besoin du front démarré, sur le poste comme en CI.
  - Les règles `.gitignore` de `e2e/` et `api/coverage/` (jusqu'ici sur `feature/test` seulement) passent sur `develop`.
- **Alternatives écartées** :
  - `npm audit --audit-level=critical` : la CI passerait, mais une nouvelle faille haute passerait aussi, sans que personne ne la voie.
  - Installer PHP et MySQL directement sur la machine de CI (`setup-php`, service MySQL) : plus rapide, mais une deuxième configuration à maintenir, qui pourrait différer de Docker. Avec les mêmes conteneurs, « ça passe sur mon poste » veut dire « ça passe en CI ».
  - Stocker de vrais secrets dans les « secrets » GitHub : inutile pour des tests, et un secret de plus à protéger.
- **Conséquences** :
  - La règle « PR rouge non fusionnable » se règle sur GitHub, dans la protection des branches `develop` et `main` (vérification `make qualite` obligatoire).
  - La couverture et Cypress n'y sont pas encore : ils dépendent des fixtures et de pcov, qui sont sur `feature/test` (D-036). Ils seront ajoutés avec Thomas, comme prévu dans `ci.md`.
  - Vérifié en local avant le premier push : les étapes du workflow, rejouées sur une copie neuve du dépôt (volumes Docker vides, sans clés JWT), donnent `make qualite` vert.
- **IA** : workflow, script d'audit et vérification sur une copie neuve écrits par l'assistant ; l'étudiant active la protection des branches sur GitHub.
