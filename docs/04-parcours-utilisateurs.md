# Parcours utilisateurs – Version 1

- **Rédigé par** : Ethan Mauclair
- **Date** : 12/10/2026
- **Sources** : [périmètre V1](03-perimetre-v1.md), [échanges client](02-echanges-client.md)

Les parcours sont décrits **du point de vue de la personne** : ce qu'elle veut, ce qu'elle fait, ce qu'elle obtient. Les écrans viendront avec les maquettes.

## Les personnes

Personnages d'illustration, construits à partir des rôles décrits par la cliente.

| Personne | Rôle | Situation |
|---|---|---|
| **Camille** | Propriétaire, compte **public** | La cliente : 18 000 abonnés, revend des articles, en possède beaucoup trop pour s'y retrouver. Vit sur son téléphone. |
| **Léo** | Co-propriétaire du logement de Camille | Partage l'appartement, range aussi ses affaires dedans. |
| **Jules**, 15 ans | Co-propriétaire **mineur** | Rattaché au compte de Camille. |
| **Sophie** | **Amie** de Camille | Ajoutée manuellement par Camille, commente ses tenues. |
| **Nora** | **Abonnée** de Camille | Suit Camille pour ses tenues. |
| **Un visiteur** | **Non inscrit** | Tombe sur le profil de Camille, ou reçoit un lien. |

---

## P1 – Camille découvre l'application et range ses premiers articles

**Ce qu'elle veut** : commencer à cataloguer ses affaires sans se perdre.

1. Camille crée son compte. Elle choisit que son compte sera **public**.
2. L'application la **guide** : elle donne un nom à son logement (« Appartement Lyon »), crée une première pièce (« Chambre »), puis un premier stockage (« Grande armoire »).
3. L'application l'invite à **photographier son premier article** (voir P2).
4. Elle arrive sur son logement, avec son premier article rangé.

**Ce qu'elle obtient** : un logement prêt, compris en moins de deux minutes.
**Fonctions** : F-01, F-02, F-05, F-06, F-07.

## P2 – Camille ajoute un article en le prenant en photo

**Ce qu'elle veut** : ajouter un article vite, sans tout saisir à la main.

1. Où qu'elle soit dans l'application, Camille touche le bouton **photo** et photographie un pull.
2. L'application remplit **la catégorie, la marque et la couleur**. Camille vérifie et **corrige** la marque, mal reconnue.
3. Elle ajoute une note : « acheté à Milan, à faire retoucher ».
4. Elle **choisit où le ranger** : Appartement Lyon > Chambre > Grande armoire.
5. S'il s'agit d'un article qu'elle ne veut montrer à personne, comme une montre de luxe, elle le marque **personnel**.

**Ce qu'elle obtient** : l'article est rangé et retrouvable.
**Règles** : si la reconnaissance échoue, Camille remplit les informations elle-même ; l'article n'est jamais perdu.
**Fonctions** : F-07, F-08, F-09, F-10.

## P3 – Camille retrouve un article

**Ce qu'elle veut** : savoir où est sa veste en jean noire, sans fouiller partout. *C'est le problème d'origine de la cliente.*

1. Camille cherche « veste », puis restreint à la couleur noire.
2. Elle voit la veste et **où elle est rangée** : Appartement Lyon > Entrée > Penderie.
3. Elle peut la déplacer si elle l'a rangée ailleurs.

**Ce qu'elle obtient** : l'emplacement de l'article en quelques secondes.
**Règles** : la recherche couvre tous les logements dont elle est propriétaire ou co-propriétaire, articles personnels compris.
**Fonctions** : F-26, F-09.

## P4 – Camille compose une tenue, la publie et la partage sur Instagram

**Ce qu'elle veut** : montrer sa tenue du jour à ses abonnés.

1. Camille crée une tenue et **sélectionne ses articles** dans sa liste, triée par catégorie : un pull, un pantalon, des bottines.
2. Elle **publie** la tenue : elle apparaît sur son **profil**.
3. Elle la **partage sur Instagram** : l'image de la tenue part vers Instagram.

**Ce qu'elle obtient** : une tenue visible sur son profil et sur Instagram.
**Règles** :
- Seuls ses **propres articles** sont proposés, pas ceux de Léo.
- Jules, mineur, peut composer une tenue mais **ne peut ni la publier ni la partager**.
- Une tenue qui contient un article **personnel** est **privée par défaut**. Camille peut la rendre publique : l'article est alors visible **dans cette tenue uniquement** (voir H12).

**Fonctions** : F-04, F-23, F-24, F-25.

## P5 – Nora découvre Camille et s'abonne

**Ce qu'elle veut** : voir les articles et les tenues de Camille.

1. Sans compte, Nora consulte le profil de Camille, qui est **public** : elle voit ses tenues, ses logements et ses articles, **sauf les articles personnels**. Elle ne voit ni les pièces ni les stockages.
2. Nora crée un compte et **s'abonne** : l'abonnement est **immédiat**.

**Variante – compte privé** : si le compte de Camille était privé, le visiteur ne verrait rien. Nora enverrait une **demande d'abonnement** ; Camille l'accepterait ou la refuserait.

**Variante – recherche ciblée** : Nora s'intéresse aux vinyles. Dans le logement de Camille, elle **filtre par catégorie** « vinyle » et ne voit que les vinyles **de Camille**, non personnels.

**Fonctions** : F-15, F-16, F-21, F-27.

## P6 – Sophie commente une tenue, puis Camille la retire de ses amis

**Ce qu'elle veut** (Sophie) : réagir aux tenues de son amie.

1. Camille **ajoute Sophie à ses amis**.
2. Sophie **commente** la tenue du jour de Camille. Nora, abonnée, ne peut pas commenter.
3. Léo, co-propriétaire, **supprime** un commentaire déplacé.
4. Plus tard, Camille **retire Sophie de ses amis** : elle choisit de la **rétrograder en abonnée** et de **garder ses commentaires**.

**Fonctions** : F-17, F-18, F-19, F-20.

## P7 – Camille envoie un article par lien

**Ce qu'elle veut** : montrer un article précis à quelqu'un qui n'a pas l'application.

1. Camille crée un **lien de partage** pour sa montre, même si elle est personnelle.
2. Elle l'envoie par message à sa sœur.
3. La sœur ouvre le lien : elle voit la **fiche de l'article** et une invitation à **créer un compte**.
4. Si elle transfère le lien, le destinataire ne voit **plus rien** : le lien ne s'ouvre **qu'une fois**.
5. Un lien jamais ouvert **expire après 24 h**.

**Fonctions** : F-22.

## P8 – Camille partage son logement avec Léo et Jules

**Ce qu'elle veut** : que toute la maison range ses affaires au même endroit.

1. Camille **ajoute Léo** comme co-propriétaire de son logement. Léo voit tout : pièces, stockages, articles, y compris personnels. Il peut tout modifier, mais **pas supprimer le logement**.
2. Camille **crée un compte mineur** pour Jules, avec sa date de naissance, et l'ajoute comme co-propriétaire.
3. Jules ajoute ses articles. Il ne peut pas créer de logement à lui.
4. Le jour de ses 18 ans, le compte de Jules devient **automatiquement majeur** : il peut créer son propre logement et publier.

**Fonctions** : F-11, F-12, F-13.

## P9 – Camille supprime son logement, puis son compte

1. Camille demande la suppression de son compte.
2. Elle possède encore un logement : l'application **refuse** et lui explique qu'elle doit d'abord **supprimer son logement** (le legs volontaire arrivera en V2).
3. Camille supprime son logement. **Seuls ses articles à elle sont supprimés.** Léo, co-propriétaire, **garde le logement**, ses pièces, ses stockages et **les articles qu'il a ajoutés**. Il en devient le propriétaire, car c'est le **co-propriétaire majeur arrivé en premier**.
4. Camille supprime son compte. Le compte de Jules, rattaché au sien, est **supprimé avec ses articles**.

**Règles** :
- S'il reste plusieurs co-propriétaires, le logement passe au **majeur arrivé en premier**.
- **Un mineur n'est jamais propriétaire** : s'il ne reste que des mineurs, ou personne, le logement est entièrement supprimé.
- Il n'y a **pas** de transfert à la suppression du compte : il faut d'abord supprimer (ou, en V2, léguer) son logement.
**Fonctions** : F-03, F-05, F-14.

---

## Hypothèses nées des parcours

| # | Hypothèse | Pourquoi |
|---|---|---|
| H12 | Une tenue contenant un article **personnel** est **privée par défaut** ; le propriétaire peut la rendre publique, ce qui montre l'article dans cette tenue uniquement. | Réponse « si, mais la tenue est privée et peut être rendue publique ». Interprétation à confirmer. |
| H13 | On ajoute un co-propriétaire **par son nom d'utilisateur**, et il doit **accepter** l'invitation. | Personne ne doit se retrouver dans un logement sans l'avoir voulu. |
| H14 | Le compte mineur a **ses propres identifiants**, créés par le compte majeur. | Jules doit pouvoir se connecter sur son téléphone. |
| H15 | Quand Jules devient majeur, il **reste co-propriétaire** du logement de Camille. | Rien n'indique qu'il doive en sortir. |
| ~~H16~~ | ~~Quand un logement est supprimé, tous ses articles le sont aussi.~~ | **Infirmée** : seuls les articles du propriétaire sont supprimés ; un co-propriétaire garde le logement et ses articles (D-011). |
| H17 | Chaque article a un **auteur** (celui qui l'a ajouté), distinct du propriétaire du logement. | Nécessaire pour savoir quels articles supprimer (D-011). |
