# Modèle d'autorisation – Version 1

- **Rédigé par** : Ethan Mauclair
- **Date** : 13/10/2026
- **Sources** : [modèle de données](05-modele-donnees.md), [échanges client](02-echanges-client.md), [journal de décisions](journal-decisions.md), séance 8-9 « Qui a le droit de voir ? »
- **Statut** : à valider au pitch 1

**Principe** : tout est **interdit par défaut**. Chaque droit ci-dessous est une exception justifiée. Une personne inconnue sur un compte privé ne voit **rien**.

---

## 1. Les profils

| Profil | Définition |
|---|---|
| **Propriétaire** | Membre du logement avec le rôle PROPRIETAIRE. Toujours majeur. |
| **Co-propriétaire** | Membre accepté du logement, majeur. |
| **Co-propriétaire mineur** | Membre accepté du logement, moins de 18 ans. |
| **Ami** | Relation acceptée de niveau AMI **vers l'auteur** de la ressource. |
| **Abonné** | Relation acceptée de niveau ABONNE **vers l'auteur** de la ressource. |
| **Connecté sans relation** | Compte existant, sans relation acceptée avec l'auteur (dont un abonné en attente). |
| **Non-inscrit** | Visiteur sans compte. |
| **Porteur d'un lien** | Toute personne qui présente un lien de partage valide (I-20). |

> On s'abonne à une **personne**, pas à un logement (D-013). Ami et abonné s'évaluent toujours par rapport à **l'auteur** de l'article ou de la tenue, pas par rapport au propriétaire du logement.

## 2. Le tableau

Situation : Camille est propriétaire, son compte est **public**. Léo est co-propriétaire, Jules co-propriétaire mineur, Sophie amie de Camille, Nora abonnée de Camille.

**Légende** : **MODIFIER** inclut voir. **VOIR**. **RIEN**.

| | Camille (propriétaire) | Léo (co-pro) | Jules (co-pro mineur) | Sophie (amie) | Nora (abonnée) | Non-inscrit | Lien de partage |
|---|---|---|---|---|---|---|---|
| **Logement (nom)** | MODIFIER, supprimer | MODIFIER | VOIR | VOIR | VOIR | VOIR | RIEN |
| **Pièces et stockages** | MODIFIER | MODIFIER | VOIR, en ajouter | RIEN | RIEN | RIEN | RIEN |
| **Article normal de Camille** | MODIFIER | MODIFIER | VOIR | VOIR, commenter | VOIR | VOIR | VOIR (cet article seul) |
| **Article personnel de Camille** | MODIFIER | MODIFIER | VOIR | RIEN | RIEN | RIEN | VOIR (cet article seul) |
| **Article de Jules** | MODIFIER | MODIFIER | MODIFIER | selon sa relation avec Jules | selon sa relation avec Jules | selon le compte de Jules | VOIR (cet article seul) |
| **Article de Léo** | MODIFIER | MODIFIER | VOIR | selon sa relation avec Léo | selon sa relation avec Léo | selon le compte de Léo | VOIR (cet article seul) |
| **Tenue publiée de Camille** | MODIFIER | VOIR | VOIR | VOIR, commenter | VOIR | VOIR | RIEN |
| **Tenue privée de Camille** | MODIFIER | VOIR | VOIR | VOIR, commenter | RIEN | RIEN | RIEN |
| **Commentaire de Sophie** | VOIR, supprimer | VOIR, supprimer | VOIR | MODIFIER, supprimer | VOIR | VOIR | RIEN |

**Si le compte de Camille est privé** : la colonne « Non-inscrit » passe à **RIEN** partout. Nora voit comme dans le tableau **uniquement** si Camille a accepté sa demande ; sinon, RIEN.

### Ce que disent les cases « ça dépend »

| Cas | Règle |
|---|---|
| Article d'un co-propriétaire | Sa visibilité dépend du **compte de son auteur** (public/privé) et des relations **avec son auteur**, jamais de celles du propriétaire du logement. |
| Article personnel dans une tenue | Visible **dans la tenue uniquement**, pour qui voit la tenue. Il reste invisible dans le logement, les recherches et les filtres (Q-21). |
| Tenue privée | Visible par son **auteur**, ses **amis** et les **membres d'un logement qu'il partage** avec l'auteur. Jamais par les abonnés ni les non-inscrits. |

## 3. Règles d'accès

### Lecture
| # | Règle |
|---|---|
| A-01 | Un **membre** d'un logement voit tout le logement : pièces, stockages, tous les articles, personnels compris. |
| A-02 | Un **non-membre** voit le **nom** du logement, jamais ses pièces ni ses stockages. |
| A-03 | Un non-membre voit un article **non personnel** si le compte de son **auteur** est public, ou s'il a une relation **acceptée** (abonné ou ami) avec cet auteur. |
| A-04 | Un article **personnel** n'est visible que par les **membres** du logement, dans une **tenue** visible, ou par un **lien** valide. |
| A-05 | Une tenue **publiée** suit la règle A-03, appliquée à son auteur. |
| A-06 | Une tenue **privée** est visible par son auteur, ses **amis** et les **membres** d'un logement qu'il partage avec l'auteur. |
| A-07 | Les **commentaires** sont visibles par tous ceux qui voient l'article ou la tenue commentés. |
| A-08b | Un compte **mineur** est toujours privé : un non-inscrit ne voit **rien** de ses articles ni de ses tenues, et un abonné doit être accepté (I-21). |
| A-08 | Un **lien de partage** donne accès à **un seul article**, une seule fois, pendant 24 h, sans aucun accès au reste. |

### Écriture
| # | Règle |
|---|---|
| A-09 | Seul le **propriétaire** supprime le logement. |
| A-10 | Propriétaire et co-propriétaires **majeurs** modifient le logement, ses pièces, ses stockages et **tous** ses articles. |
| A-11 | Un co-propriétaire **mineur** ajoute des pièces, des stockages et des articles, mais ne modifie **que ses propres articles**. Il ne renomme pas le logement. |
| A-12 | Une tenue n'est modifiable que par son **auteur** : elle appartient à une personne, pas au logement. |
| A-13 | Seul un **ami** de l'auteur commente. Il peut **modifier et supprimer ses propres** commentaires. |
| A-14 | Propriétaire et co-propriétaires **majeurs** suppriment les commentaires sur le contenu de leur logement. Un mineur ne supprime pas de commentaire. |
| A-15 | Personne ne modifie l'**auteur** d'un article, le **rôle** d'un membre ou le **propriétaire** d'un logement par une requête directe : ces champs ne changent que par les actions prévues (transfert D-011). |

## 4. Les quatre situations de la séance 8-9

| # | Situation | Réponse | Règle |
|---|---|---|---|
| 1 | Camille partage sa penderie d'été avec Sophie ; un objet est marqué « personnel ». Sophie le voit ? | **Non.** On ne partage jamais une partie d'un logement (Q-02), et Sophie, amie, ne voit pas les articles personnels. | A-04 |
| 2 | Camille publie un look ; un vêtement est en vente. Que voit un abonné ? | **Sans objet en V1** : la vente est reportée en V2 (D-007). | — |
| 3 | Camille envoie un lien à Sophie, sans compte, qui le transfère à sa sœur. Que voit la sœur ? | **Rien.** Le lien ne s'ouvre qu'une fois (Q-05, Q-19). | A-08 |
| 4 | Camille retire Sophie de ses amis. Sophie voit encore ce qu'elle voyait hier ? | **Rétrogradée** en abonnée : elle voit tout sauf les tenues privées, et ne commente plus. **Supprimée** : elle voit ce que voit un non-inscrit (tout si le compte est public, rien s'il est privé). Ses commentaires restent ou disparaissent selon le choix de Camille (D-004). | A-03, A-06, A-13 |

## 5. Tests de sécurité à passer (partie 4 de la séance)

Chaque essai doit **échouer** ; chacun deviendra un test fonctionnel de l'API.

| # | Essai | Résultat attendu |
|---|---|---|
| S-01 | Nora demande un article **personnel** de Camille par son identifiant | Refusé (404) |
| S-02 | Nora essaie de **modifier** un article de Camille | Refusé (403) |
| S-03 | Léo essaie de changer l'**auteur** d'un de ses articles pour Camille | Champ ignoré ou refusé |
| S-04 | Un non-inscrit demande la **liste** des articles d'un logement d'un compte privé | Liste vide ou refus |
| S-05 | Nora lit un article de Camille : la réponse contient-elle le **stockage**, la **pièce**, l'e-mail ou la date de naissance de l'auteur ? | Aucun de ces champs |
| S-06 | Jules essaie de **supprimer** un article de Camille | Refusé (403) |
| S-07 | Jules essaie de **renommer** le logement ou de **publier** une tenue | Refusé (403) |
| S-08 | Léo essaie de **supprimer** le logement de Camille | Refusé (403) |
| S-09 | La sœur de Sophie ouvre un lien **déjà utilisé** ou **expiré** | Refusé |
| S-10 | Nora, abonnée, essaie de **commenter** | Refusé (403) |
| S-11 | Sophie essaie de modifier le commentaire de **quelqu'un d'autre** | Refusé (403) |
| S-12 | Nora demande une tenue **privée** de Camille | Refusé (404) |
| S-13 | Un utilisateur crée un article dans le stockage d'un logement **dont il n'est pas membre** | Refusé (403) |

> **Choix 404 plutôt que 403** pour une ressource qu'on n'a pas le droit de voir : un 403 confirmerait que l'article existe.
