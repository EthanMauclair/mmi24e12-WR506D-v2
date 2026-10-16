<script setup lang="ts">
/**
 * Gérer une pièce (F-06) : la renommer, renommer ou supprimer ses rangements, la supprimer.
 * Une pièce ou un rangement ne se supprime que vide (I-10) ; seuls les membres majeurs modifient (A-10).
 * Ces règles sont tenues par l'API : son refus (409, 403) s'affiche tel quel.
 */
interface Rangement {
  id: number
  nom: string
}

const props = defineProps<{ piece: { id: number, nom: string, stockages: Rangement[] } }>()
const emit = defineEmits<{
  /** Un nom a changé ou un rangement a été supprimé : la page relit le logement. */
  modifiee: [rangementSupprime?: number]
  /** La pièce n'existe plus. */
  supprimee: []
}>()

const api = useApi()
// Corps de PATCH au format attendu par API Platform.
const ENTETE_PATCH = { 'Content-Type': 'application/merge-patch+json' }

// ---------- Noms modifiables, resynchronisés quand la page relit la pièce ----------

const nomPiece = ref(props.piece.nom)
const nomsRangements = reactive<Record<number, string>>({})
watch(() => props.piece, (piece) => {
  nomPiece.value = piece.nom
  for (const stockage of piece.stockages) {
    nomsRangements[stockage.id] = stockage.nom
  }
}, { immediate: true, deep: true })

const envoi = ref(false)
const erreur = ref<string | null>(null)
const confirmation = ref<string | null>(null)

async function appeler(action: () => Promise<unknown>, message: string): Promise<boolean> {
  envoi.value = true
  erreur.value = null
  confirmation.value = null
  try {
    await action()
    confirmation.value = message
    return true
  }
  catch (e) {
    const { general, champs } = lireErreur(e)
    erreur.value = general ?? champs.nom ?? 'Nom vide ou trop long (80 caractères maximum).'
    return false
  }
  finally {
    envoi.value = false
  }
}

// ---------- Renommer ----------

async function renommerPiece() {
  const nom = nomPiece.value.trim()
  if (nom === props.piece.nom) {
    return
  }
  const ok = await appeler(
    () => api(`/api/pieces/${props.piece.id}`, { method: 'PATCH', body: { nom }, headers: ENTETE_PATCH }),
    `Pièce renommée en « ${nom} ».`,
  )
  if (ok) {
    emit('modifiee')
  }
}

async function renommerRangement(rangement: Rangement) {
  const nom = (nomsRangements[rangement.id] ?? '').trim()
  if (nom === rangement.nom) {
    return
  }
  const ok = await appeler(
    () => api(`/api/stockages/${rangement.id}`, { method: 'PATCH', body: { nom }, headers: ENTETE_PATCH }),
    `Rangement renommé en « ${nom} ».`,
  )
  if (ok) {
    emit('modifiee')
  }
}

// ---------- Supprimer, après confirmation ----------

const dialogue = ref<HTMLDialogElement | null>(null)
const aSupprimer = ref<{ type: 'piece' } | { type: 'rangement', rangement: Rangement } | null>(null)

function demanderSuppression(cible: NonNullable<typeof aSupprimer.value>) {
  aSupprimer.value = cible
  erreur.value = null
  confirmation.value = null
  dialogue.value?.showModal()
}

async function supprimer() {
  const cible = aSupprimer.value
  if (!cible) {
    return
  }
  if (cible.type === 'piece') {
    const ok = await appeler(() => api(`/api/pieces/${props.piece.id}`, { method: 'DELETE' }), 'Pièce supprimée.')
    dialogue.value?.close()
    if (ok) {
      emit('supprimee')
    }
    return
  }
  const { rangement } = cible
  const ok = await appeler(
    () => api(`/api/stockages/${rangement.id}`, { method: 'DELETE' }),
    `Rangement « ${rangement.nom} » supprimé.`,
  )
  dialogue.value?.close()
  if (ok) {
    emit('modifiee', rangement.id)
  }
}
</script>

<template>
  <section id="gestion-piece" class="gestion carte" aria-labelledby="titre-gestion" data-cy="gestion-piece">
    <h2 id="titre-gestion">Gérer la pièce</h2>

    <p v-if="confirmation" class="confirmation" role="status" data-cy="confirmation">{{ confirmation }}</p>
    <p v-if="erreur" class="alerte" role="alert" data-cy="erreur">{{ erreur }}</p>

    <form class="ligne" novalidate @submit.prevent="renommerPiece">
      <div class="champ">
        <label for="nom-piece">Nom de la pièce</label>
        <input id="nom-piece" v-model="nomPiece" maxlength="80" required data-cy="nom-piece">
      </div>
      <button type="submit" class="bouton-contour" :disabled="envoi" data-cy="renommer-piece">Renommer</button>
    </form>

    <h3>Rangements</h3>
    <p v-if="!piece.stockages.length" class="aide">Aucun rangement dans cette pièce.</p>
    <ul v-else class="rangements">
      <li v-for="rangement in piece.stockages" :key="rangement.id">
        <form class="ligne" novalidate @submit.prevent="renommerRangement(rangement)">
          <div class="champ">
            <label :for="`rangement-${rangement.id}`" class="visuellement-cache">Nom du rangement {{ rangement.nom }}</label>
            <input :id="`rangement-${rangement.id}`" v-model="nomsRangements[rangement.id]" maxlength="80" required :data-cy="`nom-rangement-${rangement.nom}`">
          </div>
          <button type="submit" class="bouton-contour" :disabled="envoi" :aria-label="`Renommer le rangement ${rangement.nom}`">Renommer</button>
          <button
            type="button"
            class="bouton-danger"
            :disabled="envoi"
            :aria-label="`Supprimer le rangement ${rangement.nom}`"
            :data-cy="`supprimer-rangement-${rangement.nom}`"
            @click="demanderSuppression({ type: 'rangement', rangement })"
          >
            Supprimer
          </button>
        </form>
      </li>
    </ul>

    <div class="zone-danger">
      <button type="button" class="bouton-danger" :disabled="envoi" data-cy="supprimer-piece" @click="demanderSuppression({ type: 'piece' })">
        Supprimer la pièce
      </button>
      <p class="aide">Une pièce ou un rangement ne se supprime que vide : déplacez d'abord ses articles.</p>
    </div>

    <dialog ref="dialogue" class="dialogue" aria-labelledby="titre-suppression" aria-describedby="texte-suppression">
      <template v-if="aSupprimer">
        <h2 id="titre-suppression">
          {{ aSupprimer.type === 'piece' ? `Supprimer la pièce « ${piece.nom} » ?` : `Supprimer le rangement « ${aSupprimer.rangement.nom} » ?` }}
        </h2>
        <p id="texte-suppression">
          {{ aSupprimer.type === 'piece' ? 'La pièce et ses rangements seront supprimés, s\'ils ne contiennent aucun article.' : 'Le rangement sera supprimé, s\'il ne contient aucun article.' }}
        </p>
      </template>
      <div class="actions-dialogue">
        <button type="button" class="bouton bouton-secondaire" @click="dialogue?.close()">Annuler</button>
        <button type="button" class="bouton bouton-danger-plein" :disabled="envoi" data-cy="confirmer-suppression" @click="supprimer">
          {{ envoi ? 'Suppression…' : 'Supprimer' }}
        </button>
      </div>
    </dialog>
  </section>
</template>

<style scoped>
.gestion {
  display: grid;
  gap: 14px;
  margin-bottom: 20px;
}

h2 {
  margin: 0;
  font-size: 18px;
}

h3 {
  margin: 4px 0 0;
  font-size: 15px;
}

.confirmation,
.alerte {
  margin: 0;
}

.confirmation {
  padding: 0.75rem 1rem;
  border-radius: var(--rayon);
  background: #e8f3ec;
  color: #1e5631;
}

.ligne {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 8px;
}

.ligne .champ {
  flex: 1 1 180px;
}

.champ input {
  height: var(--zone-tactile);
}

.rangements {
  display: grid;
  gap: 10px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.bouton-contour,
.bouton-danger,
.bouton-danger-plein {
  min-height: var(--zone-tactile);
  padding: 0 14px;
  border-radius: var(--rayon);
  font: 700 14px var(--police-texte);
  cursor: pointer;
}

.bouton-contour {
  border: 1px solid var(--bordeaux);
  background: var(--surface);
  color: var(--bordeaux);
}

.bouton-danger {
  border: 1px solid var(--erreur);
  background: var(--surface);
  color: var(--erreur);
}

.bouton-danger-plein {
  border: 0;
  background: var(--erreur);
  color: #fff;
}

button:disabled {
  opacity: 0.6;
  cursor: wait;
}

.zone-danger {
  display: grid;
  justify-items: start;
  gap: 6px;
  padding-top: 14px;
  border-top: 1px solid var(--ligne);
}

.zone-danger .aide {
  font-size: 13px;
}

.dialogue {
  width: min(420px, calc(100% - 32px));
  padding: 24px;
  border: 0;
  border-radius: 18px;
  background: var(--surface);
  color: var(--texte);
}

.dialogue::backdrop {
  background: rgb(31 21 23 / 0.5);
}

.dialogue h2 {
  margin-bottom: 8px;
  font-size: 1.2rem;
}

.actions-dialogue {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 8px;
}

.actions-dialogue .bouton {
  min-height: var(--zone-tactile);
}
</style>
