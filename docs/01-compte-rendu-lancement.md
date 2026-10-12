# Compte rendu – Réunion de lancement du projet PENDERIE

- **Rédigé par** : Ethan Mauclair
- **Date de rédaction** : 12/10/2026
- **Source** : réunion de lancement orale, aucun support remis. Compte rendu reconstitué de mémoire (voir [D-003](journal-decisions.md#d-003--compte-rendu-de-lancement-reconstitué-de-mémoire)).
- **Statut** : à faire valider par le client

Ce document sépare trois choses :

1. **Ce que le client a dit** (§ 1 à 7) : reformulé, sans ajout.
2. **Ce que j'en déduis** (§ 8) : mes interprétations, à confirmer.
3. **Ce qui reste ouvert** (§ 9) : les questions sans réponse, détaillées dans [02-echanges-client.md](02-echanges-client.md).

---

## 1. Le client et l'objectif

- La cliente est une **personnalité publique suivie par environ 18 000 abonnés**, spécialisée dans la **revente d'articles**.
- Dans un premier temps, l'application lui est **utile à elle**. L'objectif est ensuite de la **rendre disponible à tout le monde**.

## 2. Le problème à résoudre

La cliente possède de nombreux articles. Elle veut les **cataloguer** pour ne plus avoir à les chercher partout, sans savoir où chercher.

## 3. Les utilisateurs

N'importe qui peut utiliser l'application. **On peut y accéder sans compte**, sans s'inscrire.

| Profil | Ce qu'il fait |
|---|---|
| **Propriétaire** | Crée un logement, puis y ajoute des pièces, des stockages et des articles. |
| **Co-propriétaire** | Ajouté à un logement existant. Ajoute des pièces, des stockages et des articles. Mêmes permissions que le propriétaire, sauf la suppression du logement. |
| **Co-propriétaire mineur** (compte enfant) | Comme un co-propriétaire, avec des restrictions (voir § 5). |
| **Ami** | Voit le logement et les articles du propriétaire, et peut **commenter**. |
| **Abonné** | Voit la même chose qu'un ami, sans pouvoir commenter. |
| **Non-inscrit** | Voit les articles des comptes publics. |

**Comment on devient ami ou abonné** :

- **Abonné** : sur demande, comme sur Instagram.
- **Ami** : c'est le propriétaire qui ajoute la personne manuellement.

## 4. L'organisation des articles

Les articles sont rangés selon une hiérarchie : **Logement > Pièce > Stockage > Article**.

- Chaque utilisateur peut créer **son propre logement**, avec ses pièces, ses stockages et ses articles.
- Un utilisateur peut avoir **plusieurs logements**, et être co-propriétaire de **plusieurs logements**.
- Les articles sont des **vêtements, chaussures, objets**, etc.

## 5. Qui voit quoi, qui fait quoi

### Confidentialité

- Un logement peut être **privé ou public**, sur le modèle d'Instagram.
  > **Révisé le 12/10/2026** : c'est le **compte** qui est privé ou public (Q-18, D-006).
- **Propriétaire et co-propriétaires** voient tout leur logement : logement, pièces, stockages et articles.
- **Amis, abonnés et non-inscrits** voient seulement **le logement et les articles**, pas les pièces ni les stockages.
- Un article peut être marqué **« personnel »**. Il n'apparaît alors pas aux autres, même si le reste est visible.
  - Exemple du client : une montre de luxe répertoriée dans le logement, mais invisible pour les utilisateurs non propriétaires.
  - Un article personnel reste **visible par les co-propriétaires, y compris mineurs**.

### Droits particuliers

- **Seul le créateur du logement** peut le supprimer.
- Un **co-propriétaire** peut modifier et supprimer les articles du propriétaire : il a les mêmes permissions.
- Le **co-propriétaire mineur** :
  - ne peut **pas mettre un article en vente** ;
  - ne peut **pas publier de tenue**, seulement en générer ;
  - ne peut **pas créer de logement** ;
  - est **obligatoirement rattaché à un compte majeur** pour le logement.
- Seuls les **amis** peuvent commenter, sur les **articles** et sur les **tenues**.

## 6. Les fonctionnalités évoquées

### Gestion du logement
- Ajouter un co-propriétaire ou un co-propriétaire mineur à un logement.
- Choisir la confidentialité du logement (privé ou public).

### Ajout d'un article par photo
- On **prend une photo** de l'article.
- Les informations sont **remplies automatiquement** : famille, marque, couleur, etc.
- Un **bloc-notes** permet d'ajouter des informations supplémentaires.

### Générateur de tenue
- Il pioche **uniquement dans les articles ajoutés par l'utilisateur**, pas dans ceux des co-propriétaires.
- Il tient compte de la **météo** pour ne pas proposer un pull quand il fait 25 °C.
  - La météo vient de la **position du téléphone**.
  - On peut **changer de lieu**, par exemple en prévision d'un voyage.
- Il propose des **filtres que l'utilisateur crée lui-même** (travail, sport, détente, soirée…).
  - Un filtre est défini **dans le générateur**, pas sous forme d'étiquette posée sur les articles.
  - Exemple du client : un filtre « travail » qui exclut jogging, short et baskets. Comme les informations de l'article sont remplies à l'ajout, le jogging est automatiquement exclu.

### Partage d'une tenue
- Partager une tenue **sur Instagram**.
- Ajouter des **pastilles** indiquant les références des articles portés.

### Journal des prêts
- Suivre les articles prêtés : **date du prêt**, **personne**, **rendu ou non**.
- Si l'emprunteur a l'application, on mentionne **son nom (son compte)**. Sinon, on note simplement l'objet prêté.
- Un article en prêt **n'est plus dans les logements** :
  - il n'est plus visible par les abonnés ni les amis ;
  - il n'est plus utilisé pour générer une tenue.

### Évoqué, reporté
- **Génération du contenu d'une valise** selon le nombre de jours et la destination : ajoutée par le client en séance 1-2, **à traiter plus tard**.

## 7. Contraintes et références

- **Délais et budget** : non pris en compte.
- **Appareil** : **mobile first**, la cliente vit sur son téléphone.
- **Langue** : français.
- **Technologies** : imposées par le module (Symfony avec une API, Docker, Postman, Git avec Gitflow).
- **Applications de référence citées** : **Vinted** et **Yuka**.

---

## 8. Ce que j'en déduis

> Ces points sont **mes interprétations**, pas des paroles du client. Chacun devient une **hypothèse** tant que le client ne l'a pas confirmé.

| # | Déduction | Pourquoi | Statut |
|---|---|---|---|
| H1 | L'application doit être **multi-utilisateurs dès le départ**, même si la première utilisatrice est la cliente. | L'objectif annoncé est d'ouvrir à tout le monde. La création de compte est une priorité (Q-01). | Confirmée (Q-01) |
| H2 | Le **rôle dépend du logement**, pas du compte : un même compte peut être propriétaire d'un logement et co-propriétaire d'un autre. | Plusieurs logements par utilisateur, et co-propriété de plusieurs logements. | Hypothèse |
| H3 | Le « propriétaire » d'un logement est **son créateur**, et il n'y en a qu'un. | Seul le créateur peut le supprimer. | Révisée : la propriété peut être **léguée** (Q-12). Il y a toujours un seul propriétaire, mais ce n'est plus forcément le créateur. |
| H4 | Un article prêté **n'est pas supprimé** : il change d'état et devient invisible. À son retour, il réapparaît. | Le journal des prêts suit le rendu. | Confirmée (Q-10) : il revient à sa place d'origine. |
| H5 | Un non-inscrit voit **uniquement les logements publics**, jamais un logement privé. | Modèle Instagram cité par le client. | Hypothèse, sauf accès par lien à usage unique (Q-05). |
| H6 | Les **informations remplies automatiquement** par la photo doivent pouvoir être **corrigées** par l'utilisateur. | Le générateur filtre uniquement grâce à la famille (N-01) : une famille mal détectée fausse les tenues. | Hypothèse |
| H7 | L'**âge** du compte doit être connu (date de naissance) pour appliquer les restrictions du compte mineur. | Le compte passe automatiquement en majeur à 18 ans (Q-11). | Hypothèse renforcée par Q-11 |
| H8 | Les **amis** sont liés à un **compte** et voient tous ses logements. | — | Confirmée (Q-03). **Révisée** pour la confidentialité : elle est réglée **par compte**, pas par logement (Q-18, D-006). |

**Point de vigilance** : l'application stocke des données de mineurs. Le recueil du consentement et les données collectées sur ces comptes devront être justifiés (RGPD).

---

## 9. Questions restées sans réponse

> **Mise à jour du 12/10/2026** : la plupart de ces questions ont reçu une réponse. Le suivi à jour est dans [02-echanges-client.md](02-echanges-client.md).

- **Priorités** : qu'est-ce qui est indispensable pour la version 1, et qu'est-ce qui peut attendre ?
- **Vocabulaire** : quels mots la cliente utilise-t-elle (penderie, dressing, article, objet, look, tenue…) ?
- **Confidentialité** : réglée par compte ou par logement ?
- **Partage partiel** : peut-on partager une seule pièce ou un seul stockage avec une personne précise ?
- **Vente** : un article peut-il être « en vente » ? Avec un prix, un lien Vinted ?
- **Lien de partage** : peut-on envoyer un lien à quelqu'un qui n'a pas de compte ? Et s'il le transfère ?
- **Pastilles Instagram** : vers quoi renvoient-elles ? Que se passe-t-il pour un article personnel ou un logement privé ?
- **Retrait d'un ami** : que voit-il encore ?

**Contradiction à lever** : le compte mineur ne peut pas « mettre un article en vente », mais la vente n'apparaît nulle part ailleurs dans les fonctionnalités.
