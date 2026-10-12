# Échanges avec le client

Le module demande **au moins un échange client consigné par séance**. Une réponse donnée à un étudiant vaut pour tous : les réponses entendues sur le canal public sont consignées ici aussi, avec leur source.

Statuts : **À poser**, **Répondue**, **Partielle** (réponse incomplète ou ambiguë), **Reportée** (le client a dit « on verra plus tard »).

## Questions ouvertes

| # | Question | Impact | Pourquoi c'est important | Statut |
|---|---|---|---|---|
| Q-03b | Vous avez répondu « oui » à « les abonnés voient-ils tous les logements publics, ou seulement certains ? ». Je comprends **tous les logements publics** : est-ce bien ça ? | Bloquante | La réponse « oui » ne tranche pas entre les deux options. | À confirmer |
| Q-05b | Le lien est « à usage unique » : il ne marche **qu'à la première ouverture** ? Combien de temps reste-t-il valable si personne ne l'ouvre ? Montre-t-il tout le logement ? | Importante | Définit précisément ce qu'est un accès par lien. | À poser |
| Q-08b | Quand on retire un ami, vous parlez d'une option pour supprimer ou garder ses commentaires : c'est bien le propriétaire qui choisit au moment du retrait ? | Moyenne | Réponse notée avec un point d'interrogation, donc à confirmer. | À confirmer |
| Q-11b | « Le compte enfant est supprimé également » : si le compte adulte auquel l'enfant est rattaché est supprimé, le compte enfant l'est aussi ? Et ses articles ? | Moyenne | Ambiguïté dans la réponse à Q-11. | À confirmer |
| Q-14b | « Dressing », ça désigne quoi : toute l'application, un logement, ou un stockage ? | Faible | L'interface doit utiliser le bon mot au bon endroit. | À poser |
| Q-16 | Les priorités sont le scan, la création de compte et le partage Instagram. Le partage concerne une **tenue** : le **générateur** (filtres, météo) est-il dans la première version, ou suffit-il de composer une tenue à la main ? | Bloquante | Le générateur est la fonctionnalité la plus complexe : il change complètement la taille de la V1. | À poser |
| Q-17 | La **vente** se fait dans l'application, paiement compris. Est-elle dans la première version ? Qui paie qui (l'application prend-elle une commission) ? Que se passe-t-il pour la livraison, un litige, un remboursement ? | Bloquante | Un paiement intégré implique un prestataire de paiement, des obligations légales et la gestion des litiges : c'est un projet à lui seul. | À poser |

## Questions reportées

| # | Sujet | Raison |
|---|---|---|
| R-01 | Génération du contenu d'une **valise** selon la durée et la destination | « On verra plus tard ». Hors V1. |
| R-02 (Q-06) | Destination des **pastilles** Instagram, cas d'un article personnel ou d'un logement privé | « On verra plus tard ». |
| R-03 (Q-15) | Liste complète des **informations remplies par la photo** | Pas de taille, matière ni saison. Le reste sera précisé plus tard. |

## Questions répondues

| # | Question | Réponse du client | Conséquence |
|---|---|---|---|
| Q-01 | Qu'est-ce qui est indispensable dans la première version ? | « Le scan, la création de compte, le partage sur Instagram. » | Base du périmètre V1. Voir Q-16 pour le générateur. |
| Q-02 | Peut-on partager une seule pièce ou un seul stockage avec une personne ? | « Non, on partage un logement entier. » | Les droits s'appliquent au **logement**, jamais à une pièce ou un stockage. |
| Q-03 | Privé / public : par compte ou par logement ? | « Séparément pour chaque logement. Nos amis voient tous nos logements. » Pour les abonnés : « Oui » (voir Q-03b). | La confidentialité est un attribut du **logement**. Hypothèse H8 confirmée pour les amis. |
| Q-04 | Un article peut-il être en vente ? | « Le système de vente se passe sur le site, de la mise en vente au paiement, pas de plateforme externe. C'est un espace à part, les articles en vente ne sont pas dans le logement. » | Un article en vente sort du logement, comme un article prêté. Voir Q-17. |
| Q-05 | Peut-on envoyer un lien à quelqu'un sans compte ? | « Oui, mais le lien est à usage unique. » | Si la personne transfère le lien déjà ouvert, le destinataire ne voit rien. Voir Q-05b. |
| Q-07 | « Publier » une tenue, c'est où ? | « La tenue s'affiche sur notre profil aussi. » | Il existe une **page profil**. Le compte mineur ne peut ni publier sur Instagram, ni afficher une tenue sur son profil. |
| Q-08 | Quand on retire un ami ? | « C'est au choix du propriétaire : rétrograder en abonné ou le supprimer. » Option pour supprimer ou garder ses commentaires (voir Q-08b). | Ami et abonné sont deux niveaux d'une même relation. |
| Q-09 | Abonnement à un compte public : demande ou direct ? | « L'abonnement est direct. » | Demande d'abonnement uniquement pour un logement privé. |
| Q-10 | Un article prêté rendu revient-il à sa place ? | « Oui. » | Il faut garder l'**emplacement d'origine** de l'article pendant le prêt. |
| Q-11 | Compte mineur à 18 ans, suppression du compte adulte ? | « À 18 ans, le compte obtient les mêmes permissions que le compte propriétaire. Le compte enfant est supprimé également. » | Passage automatique en compte majeur. Voir Q-11b. |
| Q-12 | Transmettre un logement ? Suppression du compte du créateur ? | « Si une personne supprime son compte, elle doit avant léguer son logement ou supprimer le logement, pour avoir un compte "vierge" en tant que propriétaire. » | Transfert de propriété nécessaire. Suppression du compte **bloquée** tant qu'il possède un logement. |
| Q-13 | Peut-on supprimer le commentaire d'un ami ? | « Le propriétaire, le co-propriétaire, oui. » | Modération par propriétaire et co-propriétaires. |
| Q-14 | Quel vocabulaire ? | « Dressing, article. » | « Article » confirmé. Voir Q-14b pour « dressing ». |
| N-01 | Sans saison ni matière, comment le générateur juge-t-il la météo ? | « Uniquement grâce à la famille. » | Chaque **famille** d'article doit indiquer à quelles températures elle convient. |

## Journal des échanges

| Date | Canal | Questions | Conséquence |
|---|---|---|---|
| 07/09/2026 | Réunion de lancement | — | Voir le [compte rendu](01-compte-rendu-lancement.md), questions Q-01 à Q-15 |
| 07/09/2026 | Séance 1-2 | — | Ajout de la valise (R-01) |
| 12/10/2026 | Échange client | Q-01 à Q-15 | 11 réponses, nouvelles questions N-01, Q-16, Q-17 |
| 12/10/2026 | Échange client | Q-02 à Q-08, Q-14, Q-15, N-01 | Partage par logement uniquement, vente intégrée, lien à usage unique, profil |
