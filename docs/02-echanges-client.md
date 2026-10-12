# Échanges avec le client

Le module demande **au moins un échange client consigné par séance**. Une réponse donnée à un étudiant vaut pour tous : les réponses entendues sur le canal public sont consignées ici aussi, avec leur source.

## Questions en attente

Classées par impact : une question **bloquante** empêche de figer le modèle de données ou le modèle d'autorisation.

| # | Question | Impact | Pourquoi c'est important | Statut |
|---|---|---|---|---|
| Q-01 | Parmi toutes les fonctionnalités, lesquelles sont **indispensables** pour la première version, et lesquelles peuvent attendre ? | Bloquante | Sans priorités, impossible de fixer le périmètre de la V1 ni la liste des exclusions. | À poser |
| Q-02 | Peut-on partager **une seule pièce ou un seul stockage** avec une personne précise (« Sophie doit pouvoir regarder ma penderie d'été ») ? | Bloquante | Si oui, les droits ne s'appliquent plus seulement au logement mais à chaque pièce ou stockage. Le modèle d'autorisation change complètement. | À poser |
| Q-03 | Le réglage **privé / public** s'applique-t-il au **compte** ou à **chaque logement** ? Les abonnés et amis sont-ils ceux du compte ou ceux d'un logement ? | Bloquante | La réponse donnée (« oui ») ne tranche pas entre les deux. Détermine où sont stockés les abonnements et la confidentialité. | À poser |
| Q-04 | Un article peut-il être **en vente** ? Si oui : avec un prix, un lien Vinted, un statut « vendu » ? Un article vendu disparaît-il ? | Bloquante | La cliente fait de la revente, et le compte mineur « ne peut pas mettre en vente ». Pourtant la vente n'apparaît dans aucune autre fonctionnalité. | À poser |
| Q-05 | Peut-on envoyer un **lien de partage** à quelqu'un qui n'a pas de compte ? S'il transfère le lien, que voit la personne suivante ? Le lien expire-t-il ? | Bloquante | Crée un troisième mode d'accès (ni compte, ni public), qui doit être sécurisé. | À poser |
| Q-06 | Les **pastilles** sur une tenue partagée sur Instagram renvoient vers quoi ? Et si l'article est **personnel** ou dans un **logement privé** ? | Importante | Une pastille ne doit pas révéler un article censé être caché. | À poser |
| Q-07 | Une tenue « publiée » l'est **où** : uniquement sur Instagram, ou aussi dans l'application (fil, profil) ? | Importante | Le compte mineur peut générer mais pas « publier » : il faut savoir ce que « publier » veut dire. | À poser |
| Q-08 | Quand on **retire quelqu'un de ses amis**, devient-il abonné, ou perd-il tout accès ? Ses commentaires restent-ils ? | Importante | Détermine si ami et abonné sont deux relations distinctes ou deux niveaux d'une même relation. | À poser |
| Q-09 | Sur un compte **public**, faut-il quand même une demande pour s'abonner, ou l'abonnement est-il immédiat ? | Importante | Sur Instagram, l'abonnement à un compte public est immédiat. | À poser |
| Q-10 | Quand un article **prêté est rendu**, revient-il automatiquement à son ancien stockage ? | Moyenne | Faut-il garder en mémoire l'emplacement d'origine ? | À poser |
| Q-11 | Que devient un **compte mineur à ses 18 ans** ? Et si le compte majeur auquel il est rattaché est supprimé ? | Moyenne | Cycle de vie du compte enfant. | À poser |
| Q-12 | Le créateur d'un logement peut-il **transmettre la propriété** à un co-propriétaire ? Que devient le logement si le créateur supprime son compte ? | Moyenne | Seul le créateur peut supprimer le logement : sans transfert, un logement peut se retrouver sans propriétaire. | À poser |
| Q-13 | Le propriétaire peut-il **supprimer ou masquer un commentaire** d'un ami ? | Moyenne | Modération, surtout sur un compte suivi par 18 000 personnes. | À poser |
| Q-14 | Quels **mots** utilisez-vous au quotidien : penderie, dressing, article, objet, pièce, look, tenue… ? | Faible | L'interface doit parler le langage de l'utilisatrice. | À poser |
| Q-15 | Quelles **informations** doivent être remplies automatiquement après la photo : famille, marque, couleur… et quoi d'autre (taille, matière, saison) ? | Importante | Les filtres du générateur ne peuvent exclure que ce qui est renseigné. | À poser |

## Questions reportées

| # | Sujet | Raison |
|---|---|---|
| R-01 | Génération du contenu d'une **valise** selon la durée et la destination | Reportée d'un commun accord (« on verra plus tard »). Hors V1 tant que le client ne la remonte pas en priorité. |

## Journal des échanges

| Date | Canal | Question | Réponse du client | Conséquence |
|---|---|---|---|---|
| 07/09/2026 | Réunion de lancement | — | Voir le [compte rendu](01-compte-rendu-lancement.md) | Questions Q-01 à Q-15 |
| 07/09/2026 | Séance 1-2 | — | Ajout : générer le contenu de sa valise selon le nombre de jours et la destination | R-01 |
