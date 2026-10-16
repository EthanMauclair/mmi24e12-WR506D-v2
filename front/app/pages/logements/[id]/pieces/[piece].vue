<script setup lang="ts">
/**
 * Une pièce et ses articles (F-26, P3) : tous ses rangements, ou un seul (?rangement=).
 * Vue membre : articles personnels compris (A-01), avec leur rangement.
 */
interface Logement {
  id: number
  nom: string
  pieces?: { id: number, nom: string, stockages: { id: number, nom: string }[] }[]
}

interface ArticleMembre {
  id: number
  photoUrl: string
  categorie: { id: number, nom: string }
  marque?: string
  couleur?: string
  estPersonnel?: boolean
  emplacement?: { stockage: { id: number, nom: string } }
}

const PAR_PAGE = 30
const api = useApi()
const config = useRuntimeConfig()
const route = useRoute()
const logementId = Number(route.params.id)
const pieceId = Number(route.params.piece)

const { data: logement, error, refresh: relireLogement } = await useAsyncData(`logement-${logementId}`, () => api<Logement>(`/api/logements/${logementId}`))
const piece = computed(() => logement.value?.pieces?.find(p => p.id === pieceId))
if (error.value || !piece.value) {
  throw createError({ statusCode: 404, statusMessage: 'Pièce introuvable', fatal: true })
}
useHead({ title: `${piece.value.nom} – Penderie` })

// Filtre par rangement, repris de l'adresse pour qu'un clic sur un rangement dans « Mes logements » y mène directement.
const filtre = ref<number | null>(Number(route.query.rangement) || null)
watch(filtre, valeur => navigateTo({ query: valeur ? { rangement: valeur } : {} }, { replace: true }))

const { data: premiere } = await useAsyncData(
  `articles-piece-${pieceId}`,
  () => api<ArticleMembre[]>('/api/articles', { query: { piece: pieceId, stockage: filtre.value ?? undefined } }),
  { watch: [filtre] },
)

// Pages suivantes (« Voir plus »), chargées dans le navigateur.
const suite = ref<ArticleMembre[]>([])
const page = ref(1)
const chargement = ref(false)
watch(filtre, () => {
  suite.value = []
  page.value = 1
})
const articles = computed(() => [...(premiere.value ?? []), ...suite.value])
const encore = computed(() => articles.value.length === PAR_PAGE * page.value)

async function voirPlus() {
  chargement.value = true
  try {
    const suivants = await api<ArticleMembre[]>('/api/articles', {
      query: { piece: pieceId, stockage: filtre.value ?? undefined, page: page.value + 1 },
    })
    suite.value.push(...suivants)
    page.value++
  }
  finally {
    chargement.value = false
  }
}

// Ajout d'un rangement à la pièce, sans quitter la page (F-06).
const nouveauRangement = ref<string | null>(null)
const erreurRangement = ref<string | null>(null)
const vFocus = { mounted: (el: HTMLElement) => el.focus() }

async function ajouterRangement() {
  const nom = (nouveauRangement.value ?? '').trim()
  if (!nom) {
    nouveauRangement.value = null
    return
  }
  erreurRangement.value = null
  try {
    const cree = await api<{ id: number }>(`/api/pieces/${pieceId}/stockages`, { method: 'POST', body: { nom } })
    await relireLogement()
    nouveauRangement.value = null
    filtre.value = cree.id
  }
  catch (e) {
    erreurRangement.value = lireErreur(e).general ?? 'Nom vide ou trop long (80 caractères maximum).'
  }
}

function photo(article: ArticleMembre): string {
  return `${config.public.apiBase}${article.photoUrl}`
}

function details(article: ArticleMembre): string {
  return [article.marque, article.couleur].filter(Boolean).join(' · ')
}
</script>

<template>
  <div v-if="logement && piece">
    <NuxtLink to="/logements" class="retour">
      <Icone nom="retour" :taille="20" :epaisseur="1.8" /> Mes logements
    </NuxtLink>
    <p class="fil">{{ logement.nom }}</p>
    <h1>{{ piece.nom }}</h1>

    <div class="filtres" role="group" aria-label="Filtrer par rangement">
      <button v-if="piece.stockages.length" type="button" class="filtre" :aria-pressed="filtre === null" @click="filtre = null">Tous les rangements</button>
      <button
        v-for="stockage in piece.stockages"
        :key="stockage.id"
        type="button"
        class="filtre"
        :aria-pressed="filtre === stockage.id"
        @click="filtre = stockage.id"
      >
        {{ stockage.nom }}
      </button>
      <form v-if="nouveauRangement !== null" class="nouveau-rangement" @submit.prevent="ajouterRangement">
        <label for="nouveau-rangement" class="visuellement-cache">Nom du nouveau rangement</label>
        <input id="nouveau-rangement" v-model="nouveauRangement" v-focus placeholder="ex. Commode" maxlength="80" @blur="ajouterRangement">
      </form>
      <button v-else type="button" class="filtre ajouter" @click="nouveauRangement = ''">+ Rangement</button>
    </div>
    <p v-if="erreurRangement" class="alerte" role="alert">{{ erreurRangement }}</p>

    <ul v-if="articles.length" class="grille" data-cy="articles">
      <li v-for="article in articles" :key="article.id">
        <NuxtLink :to="`/articles/${article.id}`" class="article">
          <div class="vignette">
            <img :src="photo(article)" alt="" loading="lazy">
            <span v-if="article.estPersonnel" class="personnel">Personnel</span>
          </div>
          <span class="categorie">{{ article.categorie.nom }}</span>
          <span v-if="details(article)" class="details">{{ details(article) }}</span>
          <span v-if="filtre === null && article.emplacement" class="rangement">{{ article.emplacement.stockage.nom }}</span>
        </NuxtLink>
      </li>
    </ul>

    <div v-else class="vide carte">
      <p class="vide-titre">{{ piece.stockages.length ? 'Aucun article ici pour l\'instant' : 'Aucun rangement dans cette pièce' }}</p>
      <p v-if="piece.stockages.length">Prenez un article en photo et choisissez ce rangement.</p>
      <p v-else>Ajoutez-en un avec « + Rangement », puis rangez-y vos articles.</p>
      <NuxtLink v-if="piece.stockages.length" to="/ajout" class="bouton bouton-principal">Ajouter un article</NuxtLink>
    </div>

    <button v-if="encore" type="button" class="bouton bouton-secondaire voir-plus" :disabled="chargement" @click="voirPlus">
      {{ chargement ? 'Chargement…' : 'Voir plus' }}
    </button>
  </div>
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

.fil {
  margin: 4px 0 0;
  color: var(--texte-doux);
  font-size: 14px;
}

h1 {
  margin: 0 0 16px;
  color: var(--bordeaux);
}

.filtres {
  display: flex;
  gap: 8px;
  margin: 0 -20px 16px;
  padding: 0 20px 4px;
  overflow-x: auto;
}

.filtre {
  flex-shrink: 0;
  min-height: 40px;
  padding: 0 14px;
  border: 1px solid var(--bordure);
  border-radius: 999px;
  background: var(--surface);
  color: #4a3d40;
  font: 700 14px var(--police-texte);
  cursor: pointer;
}

.filtre.ajouter {
  border-style: dashed;
  border-color: #b8a9ac;
  background: transparent;
}

.nouveau-rangement input {
  width: 180px;
  height: 40px;
  padding: 0 14px;
  border: 1px solid var(--bordeaux);
  border-radius: 999px;
  background: var(--surface);
  color: var(--texte);
  font: inherit;
  font-size: 14px;
}

.filtre[aria-pressed='true'] {
  border-color: var(--bordeaux);
  background: var(--bordeaux);
  color: #fff;
}

.grille {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px 12px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.vignette {
  position: relative;
}

.vignette img {
  display: block;
  width: 100%;
  aspect-ratio: 4 / 5;
  border-radius: var(--rayon);
  background: var(--bordeaux-clair);
  object-fit: cover;
}

.personnel {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 3px 8px;
  border-radius: 999px;
  background: rgb(31 21 23 / 0.78);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
}

.article {
  display: grid;
  color: var(--texte);
  text-decoration: none;
}

.article:hover .categorie {
  color: var(--bordeaux);
}

.categorie {
  margin-top: 8px;
  font-weight: 700;
}

.details,
.rangement {
  color: var(--texte-doux);
  font-size: 13px;
}

.rangement {
  color: var(--bordeaux);
  font-weight: 700;
}

.vide {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 32px 20px;
  color: var(--texte-doux);
  text-align: center;
}

.vide p {
  margin: 0;
}

.vide-titre {
  color: var(--texte);
  font-weight: 700;
}

.vide .bouton {
  margin-top: 8px;
}

.voir-plus {
  display: flex;
  margin: 24px auto 0;
}

@media (min-width: 1024px) {
  .filtres {
    flex-wrap: wrap;
    margin: 0 0 20px;
    padding: 0;
  }

  .grille {
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 20px 16px;
  }
}
</style>
