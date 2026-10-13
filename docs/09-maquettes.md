# Maquettes – Version 1

- **Rédigé par** : Ethan Mauclair
- **Date** : 13/10/2026
- **Maquettes** : canevas en ligne « Maquettes PENDERIE » (lien privé, à partager depuis son menu Partager avant de l'envoyer à l'enseignant ou au client)
- **Statut** : à valider avec la cliente

Écrans conçus **mobile d'abord** (390 × 844), la cliente vivant sur son téléphone, puis déclinés en **desktop** (1440 × 900). Les écrans sont reliés entre eux : le mode Lecture permet de parcourir le flux, en mobile comme en desktop.

## Les écrans

| # | Écran | Parcours | Points clés |
|---|---|---|---|
| 1 | Connexion | P1 | Connexion par e-mail ou pseudo ; accès « Découvrir sans compte » (F-21). |
| 2 | Inscription | P1 | Date de naissance (H7) ; choix **public / privé** dès l'inscription, expliqué en une ligne (F-02, D-006) ; annonce de la création guidée du logement (D-010). |
| 3 | Création du logement et des pièces | P1 | Étape 1 de la création guidée (D-010) : nom du logement, pièces avec leurs rangements (stockages), suggestions de pièces ; « Plus tard » pour passer ; puis invitation à photographier un premier article (F-05, F-06). |
| 4 | Profil | P4, P5 | Compteurs, badge public/privé, tenues avec mention « Privée » (I-14) ; barre de navigation avec le **bouton photo au centre**, accessible partout (D-010). |
| 5 | Scan d'un article | P2 | Cadre de prise de vue, conseil de cadrage (fond uni, article à plat : découvertes de la séance 5), import depuis la galerie, saisie à la main si la photo échoue. |
| 6 | Ajout de l'article | P2 | Bandeau « Proposition automatique, vérifiez avant d'enregistrer » (H6) ; catégorie dans la liste fixe (D-013) ; choix du rangement **après** la photo (D-010) ; case « Article personnel » expliquée. |

## Version desktop

Les six mêmes écrans, adaptés à un grand écran :

| Écran | Adaptation |
|---|---|
| Connexion, inscription | Écran coupé en deux : à gauche un panneau bordeaux (promesse de l'application, étapes de l'inscription), à droite le formulaire. |
| Création du logement | Étapes affichées en haut (compte, logement et pièces, premier article) ; pièces en grille de cartes. |
| Profil, ajout, scan | **Menu latéral** à la place de la barre du bas ; bouton « Ajouter un article » toujours en tête du menu. |
| Ajout par photo | Sur ordinateur, pas d'appareil photo en main : **dépôt d'un fichier** par glisser-déposer, ou webcam ; conseils de prise de vue à côté. |
| Ajout de l'article | Photo à gauche, formulaire à droite, sur une seule vue. |

Conséquence technique : le front Nuxt doit être **responsive** avec deux mises en page (barre du bas sur mobile, menu latéral sur desktop). Le contrat d'API ne change pas : `POST /api/reconnaissances` reçoit une image, qu'elle vienne de l'appareil photo ou d'un fichier.

## Choix visuels

| Choix | Raison |
|---|---|
| **Bordeaux** (`#6E1A2B`) comme couleur principale | Demande de la cliente (échange du 13/10/2026). Contraste suffisant avec le blanc pour les boutons. |
| Fond blanc cassé, texte presque noir | Laisser la place aux photos des articles. |
| Titres en Cormorant Garamond, texte en Manrope | Une touche « mode » pour les titres, un texte courant lisible sur petit écran. |
| Boutons et zones tactiles ≥ 44 px | Utilisation au pouce. |

## Reste à faire

- Faire valider les écrans par la cliente.
- Écrans suivants : vue d'un logement, recherche, composition d'une tenue, profil vu par un visiteur.
