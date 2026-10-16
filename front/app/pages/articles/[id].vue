<script setup lang="ts">
import type { ArticleMembre } from '~/composables/useLogements'

/**
 * Fiche d'un article (F-09, F-10, P3) : où il est rangé, et ce qu'on peut en faire.
 * - modifier la catégorie, la marque, la couleur et la note ;
 * - le déplacer dans un autre rangement, de n'importe lequel de mes logements ;
 * - le marquer personnel ou non ;
 * - le supprimer, après confirmation.
 * C'est l'API qui décide des droits (A-10, A-11 : un mineur ne modifie que ses articles) : un refus s'affiche tel quel.
 */
const api = useApi()
const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)

const { data: article, error } = await useAsyncData(`article-${id}`, () => api<ArticleMembre>(`/api/articles/${id}`))
if (error.value || !article.value) {
  throw createError({ statusCode: 404, statusMessage: 'Article introuvable', fatal: true })
}
useHead({ title: () => `${article.value?.categorie.nom ?? 'Article'} – Penderie` })

// Vue membre : l'emplacement n'est donné qu'aux membres du logement (contrat, § 2).
const membre = computed(() => !!article.value?.emplacement)

const [{ data: logements }, { data: categories }] = await Promise.all([useLogements(), useCategories()])

const groupesRangements = computed(() => (logements.value ?? []).flatMap(logement =>
  (logement.pieces ?? [])
    .filter(piece => piece.stockages.length > 0)
    .map(piece => ({ pieceId: piece.id, libelle: `${logement.nom} › ${piece.nom}`, stockages: piece.stockages })),
))

const photo = computed(() => `${config.public.apiBase}${article.value!.photoUrl}`)
const details = computed(() => [article.value!.marque, article.value!.couleur].filter(Boolean).join(' · '))

/** Retour à la page précédente (souvent la recherche, avec ses critères), sinon à la recherche. */
function revenir(evenement?: MouseEvent) {
  if (window.history.state?.back) {
    evenement?.preventDefault()
    router.back()
  }
  else if (!evenement) {
    navigateTo('/recherche', { replace: true })
  }
}

// ---------- Enregistrement commun ----------

type Mode = 'lecture' | 'modifier' | 'deplacer'
const mode = ref<Mode>('lecture')
const envoi = ref(false)
const erreur = ref<string | null>(null)
const erreurs = ref<Record<string, string>>({})
const confirmation = ref<string | null>(null)

async function enregistrer(corps: Record<string, unknown>, message: string): Promise<boolean> {
  envoi.value = true
  erreur.value = null
  erreurs.value = {}
  confirmation.value = null
  try {
    article.value = await api<ArticleMembre>(`/api/articles/${id}`, {
      method: 'PATCH',
      body: corps,
      headers: { 'Content-Type': 'application/merge-patch+json' },
    })
    confirmation.value = message
    return true
  }
  catch (e) {
    const { general, champs } = lireErreur(e)
    erreur.value = general
    erreurs.value = champs
    return false
  }
  finally {
    envoi.value = false
  }
}

function changerDeMode(nouveau: Mode) {
  erreur.value = null
  erreurs.value = {}
  confirmation.value = null
  mode.value = nouveau
  if (nouveau === 'modifier') {
    Object.assign(fiche, {
      categorie: article.value!.categorie.id,
      marque: article.value!.marque ?? '',
      couleur: article.value!.couleur ?? '',
      note: article.value!.note ?? '',
    })
  }
  if (nouveau === 'deplacer') {
    destination.value = article.value!.emplacement?.stockage.id ?? null
  }
}

// ---------- Modifier (F-09) ----------

const fiche = reactive({ categorie: null as number | null, marque: '', couleur: '', note: '' })

async function modifier() {
  // Un champ vidé est envoyé à null : l'information est retirée.
  const ok = await enregistrer({
    categorie: fiche.categorie,
    marque: fiche.marque.trim() || null,
    couleur: fiche.couleur.trim() || null,
    note: fiche.note.trim() || null,
  }, 'Article modifié.')
  if (ok) {
    mode.value = 'lecture'
  }
}

// ---------- Déplacer (F-09, P3) ----------

const destination = ref<number | null>(null)

async function deplacer() {
  if (!destination.value || destination.value === article.value!.emplacement?.stockage.id) {
    mode.value = 'lecture'
    return
  }
  const ok = await enregistrer({ stockage: destination.value }, 'Article déplacé.')
  if (ok) {
    mode.value = 'lecture'
  }
}

// ---------- Personnel (F-10) ----------

async function basculerPersonnel(evenement: Event) {
  const coche = (evenement.target as HTMLInputElement).checked
  const ok = await enregistrer(
    { estPersonnel: coche },
    coche ? 'Article personnel : visible seulement dans le logement.' : 'Article visible selon la visibilité de votre compte.',
  )
  if (!ok) {
    (evenement.target as HTMLInputElement).checked = !coche
  }
}

// ---------- Supprimer (F-09) ----------

const dialogue = ref<HTMLDialogElement | null>(null)
const suppression = ref(false)

async function supprimer() {
  suppression.value = true
  erreur.value = null
  try {
    await api(`/api/articles/${id}`, { method: 'DELETE' })
    dialogue.value?.close()
    revenir()
  }
  catch (e) {
    dialogue.value?.close()
    erreur.value = lireErreur(e).general
  }
  finally {
    suppression.value = false
  }
}
</script>

<template>
  <article v-if="article" class="fiche-article">
    <NuxtLink to="/recherche" class="retour" @click="revenir">
      <Icone nom="retour" :taille="20" :epaisseur="1.8" /> Retour
    </NuxtLink>

    <div class="colonnes">
      <div class="colonne-photo">
        <img :src="photo" :alt="[article.categorie.nom, details].filter(Boolean).join(', ')" class="photo">
        <span v-if="article.estPersonnel" class="badge-personnel">Personnel</span>
      </div>

      <div class="colonne-infos">
        <header>
          <h1>{{ article.categorie.nom }}</h1>
          <p v-if="details" class="details">{{ details }}</p>
        </header>

        <section v-if="article.emplacement" class="emplacement carte" aria-labelledby="titre-emplacement" data-cy="emplacement">
          <h2 id="titre-emplacement">Rangé dans</h2>
          <p>
            {{ article.emplacement.logement.nom }}
            <span aria-hidden="true">›</span>
            <NuxtLink :to="`/logements/${article.emplacement.logement.id}/pieces/${article.emplacement.piece.id}`">{{ article.emplacement.piece.nom }}</NuxtLink>
            <span aria-hidden="true">›</span>
            <NuxtLink :to="`/logements/${article.emplacement.logement.id}/pieces/${article.emplacement.piece.id}?rangement=${article.emplacement.stockage.id}`">{{ article.emplacement.stockage.nom }}</NuxtLink>
          </p>
        </section>

        <p v-if="confirmation" class="confirmation" role="status" data-cy="confirmation">{{ confirmation }}</p>
        <p v-if="erreur" class="alerte" role="alert">{{ erreur }}</p>

        <!-- ==================== Lecture ==================== -->
        <template v-if="mode === 'lecture'">
          <section v-if="article.note" class="note" aria-labelledby="titre-note">
            <h2 id="titre-note">Note</h2>
            <p>{{ article.note }}</p>
          </section>
          <p class="auteur">Ajouté par @{{ article.auteur.pseudo }}</p>

          <template v-if="membre">
            <label class="personnel">
              <span>
                <strong>Article personnel</strong>
                <span class="aide">Visible seulement par vous et les co-propriétaires du logement</span>
              </span>
              <input type="checkbox" role="switch" :checked="article.estPersonnel" :disabled="envoi" data-cy="personnel" @change="basculerPersonnel">
            </label>

            <div class="actions">
              <button type="button" class="bouton bouton-principal" data-cy="deplacer" @click="changerDeMode('deplacer')">Déplacer</button>
              <button type="button" class="bouton bouton-secondaire" data-cy="modifier" @click="changerDeMode('modifier')">Modifier</button>
              <button type="button" class="bouton bouton-danger" data-cy="supprimer" @click="dialogue?.showModal()">Supprimer</button>
            </div>
          </template>
        </template>

        <!-- ==================== Déplacer ==================== -->
        <form v-else-if="mode === 'deplacer'" class="formulaire" novalidate @submit.prevent="deplacer">
          <div class="champ">
            <label for="destination">Nouveau rangement</label>
            <select id="destination" v-model="destination" :aria-invalid="!!erreurs.stockage" aria-describedby="destination-erreur" data-cy="destination">
              <optgroup v-for="groupe in groupesRangements" :key="groupe.pieceId" :label="groupe.libelle">
                <option v-for="stockage in groupe.stockages" :key="stockage.id" :value="stockage.id">{{ groupe.libelle }} › {{ stockage.nom }}</option>
              </optgroup>
            </select>
            <span v-if="erreurs.stockage" id="destination-erreur" class="message-champ">{{ erreurs.stockage }}</span>
          </div>
          <div class="actions">
            <button type="submit" class="bouton bouton-principal" :disabled="envoi" data-cy="valider-deplacement">{{ envoi ? 'Déplacement…' : 'Déplacer ici' }}</button>
            <button type="button" class="bouton bouton-secondaire" @click="changerDeMode('lecture')">Annuler</button>
          </div>
        </form>

        <!-- ==================== Modifier ==================== -->
        <form v-else class="formulaire" novalidate @submit.prevent="modifier">
          <div class="champ">
            <label for="categorie">Catégorie</label>
            <select id="categorie" v-model="fiche.categorie" :aria-invalid="!!erreurs.categorie">
              <option v-for="categorie in categories" :key="categorie.id" :value="categorie.id">{{ categorie.nom }}</option>
            </select>
            <span v-if="erreurs.categorie" class="message-champ">{{ erreurs.categorie }}</span>
          </div>
          <div class="duo">
            <div class="champ">
              <label for="marque">Marque</label>
              <input id="marque" v-model="fiche.marque" maxlength="80" :aria-invalid="!!erreurs.marque">
              <span v-if="erreurs.marque" class="message-champ">{{ erreurs.marque }}</span>
            </div>
            <div class="champ">
              <label for="couleur">Couleur</label>
              <input id="couleur" v-model="fiche.couleur" maxlength="40" :aria-invalid="!!erreurs.couleur">
              <span v-if="erreurs.couleur" class="message-champ">{{ erreurs.couleur }}</span>
            </div>
          </div>
          <div class="champ">
            <label for="note">Note <span class="facultatif">(facultatif)</span></label>
            <textarea id="note" v-model="fiche.note" rows="3" maxlength="2000" :aria-invalid="!!erreurs.note" />
            <span v-if="erreurs.note" class="message-champ">{{ erreurs.note }}</span>
          </div>
          <div class="actions">
            <button type="submit" class="bouton bouton-principal" :disabled="envoi">{{ envoi ? 'Enregistrement…' : 'Enregistrer' }}</button>
            <button type="button" class="bouton bouton-secondaire" @click="changerDeMode('lecture')">Annuler</button>
          </div>
        </form>
      </div>
    </div>

    <dialog ref="dialogue" class="dialogue" aria-labelledby="titre-suppression" aria-describedby="texte-suppression">
      <h2 id="titre-suppression">Supprimer cet article ?</h2>
      <p id="texte-suppression">L'article et sa photo seront supprimés définitivement.</p>
      <div class="actions">
        <button type="button" class="bouton bouton-secondaire" @click="dialogue?.close()">Annuler</button>
        <button type="button" class="bouton bouton-danger-plein" :disabled="suppression" data-cy="confirmer-suppression" @click="supprimer">
          {{ suppression ? 'Suppression…' : 'Supprimer' }}
        </button>
      </div>
    </dialog>
  </article>
</template>

<style scoped>
.retour {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: var(--zone-tactile);
  margin-left: -6px;
  color: var(--texte-doux);
  font-weight: 700;
  text-decoration: none;
}

.colonnes {
  display: grid;
  gap: 18px;
  margin-top: 8px;
}

.colonne-photo {
  position: relative;
}

.photo {
  display: block;
  width: 100%;
  max-height: 60vh;
  aspect-ratio: 4 / 5;
  border-radius: 22px;
  background: var(--bordeaux-clair);
  object-fit: cover;
}

.badge-personnel {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgb(31 21 23 / 0.78);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
}

.colonne-infos {
  display: grid;
  align-content: start;
  gap: 16px;
}

h1 {
  margin: 0;
  color: var(--bordeaux);
}

h2 {
  margin: 0 0 4px;
  font-size: 13px;
}

.details {
  margin: 4px 0 0;
  color: var(--texte-doux);
}

.emplacement {
  padding: 14px 16px;
  border-color: var(--ligne);
}

.emplacement h2 {
  color: var(--texte-doux);
  font-weight: 700;
}

.emplacement p {
  margin: 0;
  font-weight: 700;
}

.emplacement a {
  display: inline-block;
  min-height: 24px;
}

.emplacement span {
  margin: 0 6px;
  color: var(--texte-doux);
}

.note p,
.auteur {
  margin: 0;
}

.note p {
  white-space: pre-line;
}

.auteur {
  color: var(--texte-doux);
  font-size: 14px;
}

.confirmation {
  margin: 0;
  padding: 0.75rem 1rem;
  border-radius: var(--rayon);
  background: #e8f3ec;
  color: #1e5631;
}

.alerte {
  margin: 0;
}

.personnel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border: 1px solid var(--bordure);
  border-radius: var(--rayon);
  background: var(--surface);
  cursor: pointer;
}

.personnel > span {
  display: grid;
  gap: 2px;
}

.personnel input {
  flex-shrink: 0;
  width: 22px;
  height: 22px;
  margin: 0;
  accent-color: var(--bordeaux);
}

.actions {
  display: grid;
  gap: 10px;
}

.bouton-danger,
.bouton-danger-plein {
  min-height: var(--zone-tactile);
  border-radius: var(--rayon);
  font-size: 0.875rem;
}

.bouton-danger {
  border-color: var(--erreur);
  background: var(--surface);
  color: var(--erreur);
}

.bouton-danger-plein {
  background: var(--erreur);
  color: #fff;
}

.duo {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.champ select,
.champ textarea {
  min-width: 0;
  border: 1px solid var(--bordure);
  border-radius: var(--rayon);
  background: var(--surface);
  color: var(--texte);
  font: inherit;
  font-size: 15px;
}

.champ select {
  height: 48px;
  padding: 0 12px;
}

.champ textarea {
  padding: 10px 12px;
  resize: vertical;
}

.champ select:focus,
.champ textarea:focus {
  outline: 2px solid var(--bordeaux);
  outline-offset: 1px;
}

.facultatif {
  color: var(--texte-aide);
  font-weight: 400;
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
  font-size: 1.2rem;
}

.dialogue .actions {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 8px;
}

@media (min-width: 1024px) {
  .colonnes {
    grid-template-columns: 440px minmax(0, 560px);
    gap: 40px;
    margin-top: 16px;
  }

  .photo {
    max-height: none;
  }

  h1 {
    font-size: 34px;
  }

  .actions {
    display: flex;
    flex-wrap: wrap;
  }

  .actions .bouton {
    padding: 0 22px;
  }

  .dialogue .actions {
    justify-content: flex-end;
  }
}
</style>
