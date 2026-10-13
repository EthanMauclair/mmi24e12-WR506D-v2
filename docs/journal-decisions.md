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
