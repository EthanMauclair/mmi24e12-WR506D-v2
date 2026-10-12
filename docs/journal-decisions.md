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
