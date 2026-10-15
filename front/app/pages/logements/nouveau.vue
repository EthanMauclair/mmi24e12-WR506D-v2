<script setup lang="ts">
/**
 * Écran 3 – Création du logement, de ses pièces et de leurs rangements (F-05, F-06, P1, D-010).
 * Pour un premier logement, c'est l'étape guidée qui suit l'inscription : elle mène ensuite au
 * premier article. Tout est préparé dans la page puis envoyé à l'API à la validation.
 */
definePageMeta({ layout: false })
useHead({ title: 'Nouveau logement – Penderie' })

interface PieceBrouillon {
  cle: number
  nom: string
  stockages: string[]
  // Saisie en cours d'un rangement, ou du nouveau nom de la pièce (null : pas de saisie ouverte).
  saisieRangement: string | null
  saisieNom: string | null
  // Ce qui est déjà enregistré, pour qu'un nouvel essai après une erreur ne crée rien en double.
  id?: number
  stockagesEnregistres: number
}

const api = useApi()
const { data: existants } = await useAsyncData('logements-existants', () => api<{ id: number }[]>('/api/logements'))
const premier = computed(() => (existants.value?.length ?? 0) === 0)

const nom = ref('')
const pieces = ref<PieceBrouillon[]>([])
const nouvellePiece = ref('')
const envoi = ref(false)
const erreur = ref<string | null>(null)
const erreurNom = ref<string | null>(null)
let logementId: number | null = null
let compteur = 0

const SUGGESTIONS = ['Chambre', 'Entrée', 'Salon', 'Salle de bain', 'Bureau', 'Dressing', 'Cave']
const suggestions = computed(() => SUGGESTIONS
  .filter(s => !pieces.value.some(p => p.nom.toLowerCase() === s.toLowerCase()))
  .slice(0, 4))

/** Place le curseur dans un champ qui vient d'apparaître. */
const vFocus = { mounted: (el: HTMLElement) => el.focus() }

function ajouterPiece(valeur: string) {
  const nomPiece = valeur.trim().slice(0, 80)
  if (!nomPiece) {
    return
  }
  pieces.value.push({ cle: ++compteur, nom: nomPiece, stockages: [], saisieRangement: null, saisieNom: null, stockagesEnregistres: 0 })
  nouvellePiece.value = ''
}

function retirerPiece(piece: PieceBrouillon) {
  pieces.value = pieces.value.filter(p => p !== piece)
}

function validerRangement(piece: PieceBrouillon) {
  const nomRangement = (piece.saisieRangement ?? '').trim().slice(0, 80)
  if (nomRangement && !piece.stockages.includes(nomRangement)) {
    piece.stockages.push(nomRangement)
  }
  piece.saisieRangement = null
}

function validerNom(piece: PieceBrouillon) {
  const nouveau = (piece.saisieNom ?? '').trim().slice(0, 80)
  if (nouveau) {
    piece.nom = nouveau
  }
  piece.saisieNom = null
}

async function enregistrer(destination: string) {
  erreur.value = null
  erreurNom.value = nom.value.trim() ? null : 'Donnez un nom à votre logement.'
  if (erreurNom.value) {
    return
  }

  envoi.value = true
  try {
    if (logementId === null) {
      logementId = (await api<{ id: number }>('/api/logements', { method: 'POST', body: { nom: nom.value.trim() } })).id
    }
    for (const piece of pieces.value) {
      piece.id ??= (await api<{ id: number }>(`/api/logements/${logementId}/pieces`, { method: 'POST', body: { nom: piece.nom } })).id
      for (const rangement of piece.stockages.slice(piece.stockagesEnregistres)) {
        await api(`/api/pieces/${piece.id}/stockages`, { method: 'POST', body: { nom: rangement } })
        piece.stockagesEnregistres++
      }
    }
    await navigateTo(destination)
  }
  catch (e) {
    erreur.value = lireErreur(e).general ?? 'Un nom est vide ou trop long (80 caractères maximum).'
  }
  finally {
    envoi.value = false
  }
}
</script>

<template>
  <div class="ecran">
    <header class="entete">
      <NuxtLink to="/profil" class="logo">Penderie</NuxtLink>

      <ol v-if="premier" class="etapes" aria-label="Étapes">
        <li class="fait"><span class="pastille">✓</span>Compte</li>
        <li class="trait" aria-hidden="true" />
        <li aria-current="step" class="courante"><span class="pastille">2</span>Logement et pièces</li>
        <li class="trait" aria-hidden="true" />
        <li><span class="pastille">3</span>Premier article</li>
      </ol>

      <span class="etape-mobile">{{ premier ? 'Étape 1 sur 2' : 'Nouveau logement' }}</span>
      <NuxtLink :to="premier ? '/profil' : '/logements'" class="plus-tard">{{ premier ? 'Plus tard' : 'Annuler' }}</NuxtLink>
    </header>
    <div v-if="premier" class="progression" role="progressbar" aria-label="Progression de la création" aria-valuemin="0" aria-valuemax="2" aria-valuenow="1">
      <div />
    </div>

    <main class="contenu">
      <div class="titre-ligne">
        <div>
          <h1>{{ premier ? 'Votre premier logement' : 'Nouveau logement' }}</h1>
          <p class="sous-titre">Nommez-le, puis ajoutez ses pièces et leurs rangements.</p>
        </div>
        <div class="champ nom-logement">
          <label for="nom-logement">Nom du logement</label>
          <input
            id="nom-logement"
            v-model="nom"
            placeholder="ex. Appartement Lyon"
            maxlength="80"
            :aria-invalid="!!erreurNom"
            aria-describedby="nom-logement-erreur"
          >
          <span v-if="erreurNom" id="nom-logement-erreur" class="message-champ">{{ erreurNom }}</span>
        </div>
      </div>

      <h2 class="titre-pieces">Pièces</h2>
      <div class="pieces">
        <article v-for="piece in pieces" :key="piece.cle" class="piece">
          <div class="piece-entete">
            <form v-if="piece.saisieNom !== null" class="renommer" @submit.prevent="validerNom(piece)">
              <label :for="`nom-piece-${piece.cle}`" class="visuellement-cache">Nouveau nom de la pièce</label>
              <input :id="`nom-piece-${piece.cle}`" v-model="piece.saisieNom" v-focus maxlength="80" @blur="validerNom(piece)">
            </form>
            <h3 v-else>{{ piece.nom }}</h3>
            <div class="piece-actions">
              <button type="button" class="icone-bouton" :aria-label="`Renommer la pièce ${piece.nom}`" @click="piece.saisieNom = piece.nom">
                <Icone nom="crayon" :taille="18" :epaisseur="1.8" />
              </button>
              <button type="button" class="icone-bouton" :aria-label="`Retirer la pièce ${piece.nom}`" @click="retirerPiece(piece)">
                <Icone nom="croix" :taille="18" :epaisseur="1.8" />
              </button>
            </div>
          </div>

          <span class="etiquette-rangements">Rangements</span>
          <ul class="rangements">
            <li v-for="(rangement, index) in piece.stockages" :key="rangement" class="puce">
              {{ rangement }}
              <button
                type="button"
                class="retirer-puce"
                :aria-label="`Retirer le rangement ${rangement}`"
                @click="piece.stockages.splice(index, 1)"
              >
                <Icone nom="croix" :taille="14" :epaisseur="2" />
              </button>
            </li>
            <li>
              <form v-if="piece.saisieRangement !== null" class="nouveau-rangement" @submit.prevent="validerRangement(piece)">
                <label :for="`rangement-${piece.cle}`" class="visuellement-cache">Nom du rangement dans {{ piece.nom }}</label>
                <input
                  :id="`rangement-${piece.cle}`"
                  v-model="piece.saisieRangement"
                  v-focus
                  placeholder="ex. Grande armoire"
                  maxlength="80"
                  @blur="validerRangement(piece)"
                >
              </form>
              <button v-else type="button" class="ajouter-puce" @click="piece.saisieRangement = ''">+ Rangement</button>
            </li>
          </ul>
        </article>

        <div class="ajout-piece">
          <form class="ajout-piece-form" @submit.prevent="ajouterPiece(nouvellePiece)">
            <label for="nouvelle-piece" class="ajout-libelle">Ajouter une pièce</label>
            <div class="ajout-ligne">
              <input id="nouvelle-piece" v-model="nouvellePiece" placeholder="ex. Salon" maxlength="80">
              <button type="submit" class="bouton-contour">Ajouter</button>
            </div>
          </form>
          <div class="suggestions" role="group" aria-label="Suggestions de pièces">
            <button v-for="suggestion in suggestions" :key="suggestion" type="button" class="suggestion" @click="ajouterPiece(suggestion)">
              + {{ suggestion }}
            </button>
          </div>
        </div>
      </div>

      <p v-if="erreur" class="alerte" role="alert">{{ erreur }}</p>

      <div class="final">
        <template v-if="premier">
          <button type="button" class="bouton bouton-principal" :disabled="envoi" @click="enregistrer('/ajout')">
            {{ envoi ? 'Enregistrement…' : 'Photographier un premier article' }}
          </button>
          <button type="button" class="lien-discret" :disabled="envoi" @click="enregistrer('/logements')">
            Enregistrer sans ajouter d'article
          </button>
        </template>
        <button v-else type="button" class="bouton bouton-principal" :disabled="envoi" @click="enregistrer('/logements')">
          {{ envoi ? 'Enregistrement…' : 'Créer le logement' }}
        </button>
      </div>
    </main>
  </div>
</template>

<style scoped>
.ecran {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  min-height: 100dvh;
}

/* ---------- En-tête ---------- */

.entete {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px 8px;
}

.logo,
.etapes {
  display: none;
}

.etape-mobile {
  color: var(--texte-doux);
  font-size: 13px;
  font-weight: 700;
}

.plus-tard {
  display: flex;
  align-items: center;
  min-height: var(--zone-tactile);
  color: var(--texte-doux);
  font-size: 14px;
  font-weight: 700;
}

.progression {
  height: 4px;
  margin: 0 20px;
  border-radius: 999px;
  background: var(--ligne);
  overflow: hidden;
}

.progression div {
  width: 50%;
  height: 100%;
  background: var(--bordeaux);
}

/* ---------- Contenu ---------- */

.contenu {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  padding: 20px 20px 24px;
}

h1 {
  margin: 0 0 4px;
  font-size: 26px;
  color: var(--bordeaux);
}

.sous-titre {
  margin: 0 0 18px;
  color: var(--texte-doux);
  font-size: 14px;
}

.titre-pieces {
  margin: 22px 0 10px;
  font-size: 15px;
}

.pieces {
  display: grid;
  gap: 10px;
}

.piece {
  display: grid;
  gap: 10px;
  padding: 12px 12px 12px 14px;
  border: 1px solid var(--ligne);
  border-radius: var(--rayon-bouton);
  background: var(--surface);
}

.piece-entete {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  min-height: 24px;
}

h3 {
  margin: 0;
  font-size: 15px;
  overflow-wrap: anywhere;
}

.piece-actions {
  display: flex;
  margin: -10px -6px -10px 0;
}

.icone-bouton {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--zone-tactile);
  height: var(--zone-tactile);
  border: 0;
  background: transparent;
  color: var(--texte-doux);
  cursor: pointer;
}

.renommer {
  flex-grow: 1;
}

.renommer input,
.nouveau-rangement input,
.ajout-ligne input {
  width: 100%;
  min-width: 0;
  height: 44px;
  padding: 0 12px;
  border: 1px solid var(--bordure);
  border-radius: var(--rayon);
  background: var(--surface);
  color: var(--texte);
  font: inherit;
  font-size: 15px;
}

.etiquette-rangements {
  display: none;
}

.rangements {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.puce {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 2px 2px 2px 11px;
  border-radius: 999px;
  background: var(--bordeaux-clair);
  color: var(--bordeaux);
  font-size: 13px;
  font-weight: 700;
}

.retirer-puce {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: var(--bordeaux);
  cursor: pointer;
}

.ajouter-puce,
.suggestion {
  min-height: 36px;
  padding: 5px 11px;
  border-radius: 999px;
  color: #4a3d40;
  font: 700 13px var(--police-texte);
  cursor: pointer;
}

.ajouter-puce {
  border: 1px dashed #b8a9ac;
  background: transparent;
}

.ajout-piece {
  display: grid;
  gap: 10px;
  margin-top: 2px;
}

.ajout-libelle {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
}

.ajout-ligne {
  display: flex;
  gap: 8px;
}

.ajout-ligne input {
  height: 46px;
}

.bouton-contour {
  flex-shrink: 0;
  height: 46px;
  padding: 0 16px;
  border: 1px solid var(--bordeaux);
  border-radius: var(--rayon);
  background: var(--surface);
  color: var(--bordeaux);
  font: 700 14px var(--police-texte);
  cursor: pointer;
}

.suggestions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.suggestion {
  border: 1px solid var(--ligne);
  background: var(--surface);
  font-weight: 400;
}

.alerte {
  margin: 16px 0 0;
}

.final {
  display: grid;
  gap: 8px;
  margin-top: auto;
  padding-top: 24px;
}

.lien-discret {
  min-height: var(--zone-tactile);
  border: 0;
  background: transparent;
  color: var(--texte-doux);
  font: 700 14px var(--police-texte);
  text-decoration: underline;
  cursor: pointer;
}

/* ---------- Desktop ---------- */

@media (min-width: 1024px) {
  .entete {
    height: 76px;
    padding: 0 48px;
    border-bottom: 1px solid var(--ligne);
    background: var(--surface);
  }

  .logo {
    display: block;
    color: var(--bordeaux);
    font-size: 22px;
    font-weight: 700;
    text-decoration: none;
  }

  .etape-mobile,
  .progression {
    display: none;
  }

  .etapes {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    padding: 0;
    color: var(--texte-doux);
    font-size: 14px;
    font-weight: 700;
    list-style: none;
  }

  .etapes li:not(.trait) {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .trait {
    width: 32px;
    height: 1px;
    background: var(--bordure);
  }

  .pastille {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border: 1px solid var(--bordure);
    border-radius: 50%;
    font-size: 12px;
  }

  .fait .pastille {
    border: 0;
    background: var(--ligne);
  }

  .courante {
    color: var(--bordeaux);
  }

  .courante .pastille {
    border: 0;
    background: var(--bordeaux);
    color: #fff;
  }

  .contenu {
    gap: 0;
    padding: 48px 120px 40px;
  }

  .titre-ligne {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 40px;
  }

  h1 {
    font-size: 34px;
  }

  .sous-titre {
    margin: 0;
    font-size: 15px;
  }

  .nom-logement {
    width: 380px;
  }

  .titre-pieces {
    margin: 28px 0 14px;
  }

  .pieces {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
  }

  .piece,
  .ajout-piece {
    align-content: start;
    min-height: 180px;
    padding: 18px;
    border-radius: 16px;
  }

  .piece {
    gap: 14px;
  }

  h3 {
    font-size: 18px;
  }

  .etiquette-rangements {
    display: block;
    color: var(--texte-aide);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .puce,
  .ajouter-puce {
    font-size: 14px;
  }

  .ajout-piece {
    margin: 0;
    border: 1.5px dashed #b8a9ac;
    gap: 12px;
  }

  .ajout-libelle {
    position: static;
    width: auto;
    height: auto;
    clip: auto;
    font-size: 15px;
    font-weight: 700;
  }

  .ajout-piece-form {
    display: grid;
    gap: 12px;
  }

  .final {
    display: flex;
    flex-direction: row-reverse;
    align-items: center;
    justify-content: flex-start;
    gap: 20px;
  }

  .final .bouton {
    padding: 0 32px;
  }
}
</style>
