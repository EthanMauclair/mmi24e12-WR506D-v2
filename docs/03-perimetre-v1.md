# Périmètre de la version 1

- **Rédigé par** : Ethan Mauclair
- **Date** : 12/10/2026
- **Sources** : [compte rendu de lancement](01-compte-rendu-lancement.md), [échanges client](02-echanges-client.md), [journal de décisions](journal-decisions.md)
- **Statut** : à faire valider par le client

La V1 est construite autour des **trois priorités de la cliente** (Q-01) : **l'ajout d'article par photo**, **la création de compte** et **le partage sur Instagram**. Tout ce qui est nécessaire pour que ces trois fonctions aient un sens est inclus. Le reste est exclu, avec sa raison.

---

## 1. Ce qui est dans la V1

### Comptes
| # | Fonction | Source |
|---|---|---|
| F-01 | Créer un compte, se connecter, se déconnecter | Q-01 |
| F-02 | Choisir si son compte est **privé ou public** | Q-18, D-006 |
| F-03 | Supprimer son compte, **uniquement s'il ne possède plus aucun logement** | Q-12 (voir E-04) |
| F-04 | Page **profil** : informations du compte et tenues publiées | Q-07 |

### Logements et articles
| # | Fonction | Source |
|---|---|---|
| F-05 | Créer, modifier et supprimer un **logement** (supprimer : propriétaire uniquement) | CR § 4-5 |
| F-06 | Créer, modifier et supprimer des **pièces** et des **stockages** | CR § 4 |
| F-07 | Ajouter un **article en le prenant en photo** : la **catégorie**, la marque et la couleur sont remplies automatiquement, et **corrigeables** | Q-01, CR § 6, H6 |
| F-08 | Ajouter un **bloc-notes** à un article | CR § 6 |
| F-09 | Modifier, déplacer et supprimer un article | CR § 5 |
| F-10 | Marquer un article comme **personnel** | CR § 5 |
| F-27 | **Filtrer les articles d'un logement par catégorie**, y compris pour un visiteur qui y a accès (ex. : un abonné ne voit que les vinyles). Ne montre jamais les articles personnels, ni ceux d'une autre personne que celle qu'il suit. | Échange client 12/10, D-012 |
| F-26 | **Rechercher un article** (par catégorie, marque, couleur, mot du bloc-notes) et voir **où il est rangé** (logement > pièce > stockage) | CR § 2 : le problème à résoudre |

### Co-propriété
| # | Fonction | Source |
|---|---|---|
| F-11 | Ajouter un **co-propriétaire** à un logement, le retirer | CR § 3, § 6 |
| F-12 | Créer un **compte mineur** rattaché à un compte majeur, et l'ajouter comme co-propriétaire | CR § 3, § 5 |
| F-13 | Passage **automatique en compte majeur** à 18 ans | Q-11 |
| F-14 | Suppression du compte mineur **et de ses articles** quand le compte majeur de rattachement est supprimé | Q-11b |

### Visibilité et relations
| # | Fonction | Source |
|---|---|---|
| F-15 | **S'abonner** à un compte : direct si le compte est public, sur acceptation s'il est privé | Q-03b, Q-09 |
| F-16 | Accepter ou refuser une demande d'abonnement, retirer un abonné | Q-03b |
| F-17 | **Ajouter un ami** (manuellement, par le propriétaire) | Q-01 tour 2, CR § 3 |
| F-18 | **Retirer un ami** : le rétrograder en abonné ou le supprimer, avec le choix de **garder ou supprimer ses commentaires** | Q-08, D-004 |
| F-19 | **Commenter** un article ou une tenue (amis uniquement) | Q-13 tour 2 |
| F-20 | **Supprimer un commentaire** sur son logement (propriétaire et co-propriétaires) | Q-13 |
| F-21 | Consulter les logements et articles **non personnels** d'un compte public sans être inscrit | CR § 3, Q-03b |
| F-22 | Partager **un article par lien**, ouvrable **une seule fois**, valable **24 h**, même pour un article personnel | Q-05, Q-19 |

### Tenues
| # | Fonction | Source |
|---|---|---|
| F-23 | **Composer une tenue à la main** en sélectionnant librement ses propres articles, avec un tri par catégorie | D-005, D-009 |
| F-24 | **Publier** la tenue sur son profil | Q-07 |
| F-25 | **Partager** l'image de la tenue sur Instagram, **sans pastilles** | Q-01 (voir E-03) |

### Restrictions du compte mineur (applicables en V1)
- Ne peut pas créer de logement.
- Son compte est **toujours privé** (Q-22).
- Ne peut pas publier de tenue (ni sur le profil, ni sur Instagram).
- Voit les articles personnels du logement dont il est co-propriétaire.

---

## 2. Ce qui est exclu de la V1

| # | Exclusion | Raison | Quand |
|---|---|---|---|
| E-01 | **Générateur de tenue** (filtres personnalisés enregistrés, météo, changement de lieu) | Placé en V2 par la cliente (Q-16). Les filtres enregistrés arrivent avec lui (D-009). | V2 |
| E-02 | **Vente** (mise en vente, paiement, espace de vente) | Placée en V2 par la cliente (Q-17). Implique un prestataire de paiement, des obligations légales et la gestion des litiges (D-007). | V2 |
| E-03 | **Pastilles** sur la tenue partagée | La cliente n'a pas tranché leur destination ni le cas d'un article personnel ou d'un compte privé (R-02). Les concevoir maintenant, c'est décider à sa place. | Quand la cliente aura tranché |
| E-04 | **Léguer un logement** | Hors des trois priorités. En V1, on peut supprimer son compte après avoir supprimé ses logements : la règle du client (« léguer ou supprimer avant ») reste respectée, avec une seule des deux options. | V2 |
| E-05 | **Journal des prêts** | Hors des trois priorités. Ne bloque aucune fonction de la V1. | V2 |
| E-06 | **Valise** (contenu selon la durée et la destination) | Reportée par la cliente (R-01). Dépend du générateur (E-01). | Après le générateur |
| E-07 | **Vente par un compte mineur** (restriction) | Sans objet tant que la vente n'existe pas (E-02). | V2, avec la vente |

---

## 3. Hypothèses prises là où le client n'a pas répondu

| # | Hypothèse | Pourquoi |
|---|---|---|
| H2 | Le **rôle dépend du logement** : un compte peut être propriétaire de l'un et co-propriétaire d'un autre. | Plusieurs logements par compte, co-propriété de plusieurs logements. |
| H5 | Un non-inscrit voit les logements et articles non personnels des **comptes publics uniquement**, et ce qu'on lui envoie par lien. | Modèle Instagram, Q-03b, Q-19. |
| H6 | Les informations remplies par la photo sont **corrigeables**. | Une catégorie mal détectée fausserait plus tard le générateur (N-01). |
| H7 | La **date de naissance** est demandée à la création d'un compte mineur. | Nécessaire au passage automatique en majeur (F-13). |
| H9 | Un **ami** a au minimum les mêmes droits de lecture qu'un abonné accepté, même si le compte est privé. | « Les deux statuts voient la même chose » (tour 2, Q-01). |
| H10 | Les **visiteurs** (amis, abonnés, non-inscrits) voient le **logement** et ses **articles**, mais pas les pièces ni les stockages. | CR § 5. |
| H11 | La reconnaissance de l'article par photo passe par un **modèle d'IA externe** (OpenRouter), avec une image réduite, convertie en JPG puis en base64. | Preuve de concept de la séance 5. |
| H18 | Un compte **mineur** ne peut être invité que dans un logement dont son **référent** est membre. | Le compte rendu dit qu'il est « obligatoirement rattaché à un compte majeur pour le logement ». À confirmer avec la cliente. |

---

## 4. Point de vigilance : la taille de la V1

La V1 contient **27 fonctions**. Le pitch 2 demande une **tranche verticale qui fonctionne de bout en bout**, pas toutes les fonctions à moitié. Ordre de réalisation proposé, chaque lot devant être démontrable seul :

1. **Lot 1 – Le cœur** : comptes (F-01, F-02), logement, pièces, stockages, articles, recherche (F-05 à F-09, F-26). *Tranche verticale du pitch 2.*
2. **Lot 2 – La photo** : ajout par photo (F-07 complet).
3. **Lot 3 – La visibilité** : articles personnels, abonnés, non-inscrits, filtre par catégorie (F-10, F-15, F-16, F-21, F-27).
4. **Lot 4 – Les tenues** : composer, publier, partager (F-04, F-23 à F-25).
5. **Lot 5 – Le social** : amis, commentaires, lien de partage (F-17 à F-20, F-22).
6. **Lot 6 – La co-propriété** : co-propriétaires et compte mineur (F-11 à F-14), suppression de compte (F-03).
