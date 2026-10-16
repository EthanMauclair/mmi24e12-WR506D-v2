<script setup lang="ts">
import type { ArticleMembre } from '~/composables/useLogements'

/**
 * Rechercher un article et voir où il est rangé (F-26, P3).
 * - Le texte porte sur la catégorie, la marque, la couleur et la note ; les filtres le restreignent.
 * - Tout est repris dans l'adresse : la recherche survit au retour depuis la fiche d'un article.
 * - Résultats par pages de 30 (docs/08-contrat-api.md, § 1). L'API ne donne pas de total :
 *   une page pleine laisse supposer une page suivante.
 */
useHead({ title: 'Rechercher – Penderie' })

const PAR_PAGE = 30
const CRITERES = ['q', 'logement', 'piece', 'stockage', 'categorie', 'couleur', 'marque'] as const
type Critere = typeof CRITERES[number]

const api = useApi()
const config = useRuntimeConfig()
const route = useRoute()

const [{ data: logements }, { data: categories }] = await Promise.all([useLogements(), useCategories()])

// ---------- Critères, synchronisés avec l'adresse ----------

function depuisAdresse(): Record<Critere, string> {
  return Object.fromEntries(CRITERES.map((cle) => {
    const valeur = route.query[cle]
    return [cle, typeof valeur === 'string' ? valeur : '']
  })) as Record<Critere, string>
}

const criteres = reactive(depuisAdresse())
const page = computed(() => Math.max(1, Number(route.query.page) || 1))

// Retour arrière, ou « Rechercher » du menu alors qu'on est déjà sur la page.
watch(() => route.query, () => Object.assign(criteres, depuisAdresse()))

/**
 * Les critères renseignés, tels qu'ils partent dans l'adresse et vers l'API (qui retire les espaces).
 * Ils ne sont pas rognés ici : l'adresse est relue dans les champs, on perdrait l'espace en cours de frappe.
 */
function renseignes(): Partial<Record<Critere, string>> {
  return Object.fromEntries(CRITERES.map(cle => [cle, criteres[cle]]).filter(([, valeur]) => valeur!.trim() !== ''))
}

const requete = computed(() => {
  const query: Record<string, string> = {}
  for (const cle of CRITERES) {
    const valeur = route.query[cle]
    if (typeof valeur === 'string' && valeur !== '') {
      query[cle] = valeur
    }
  }
  return query
})
const nombreFiltres = computed(() => Object.keys(requete.value).filter(cle => cle !== 'q').length)
const filtresOuverts = ref(nombreFiltres.value > 0)

/** Nouvelle recherche : on revient à la première page. */
function appliquer() {
  clearTimeout(attente)
  navigateTo({ query: renseignes() }, { replace: true })
}

// Les champs de texte attendent une courte pause dans la frappe avant de lancer la recherche.
let attente: ReturnType<typeof setTimeout> | undefined
function apresFrappe() {
  clearTimeout(attente)
  attente = setTimeout(appliquer, 350)
}
onBeforeUnmount(() => clearTimeout(attente))

function choisirLogement() {
  criteres.piece = ''
  criteres.stockage = ''
  appliquer()
}

function choisirPiece() {
  criteres.stockage = ''
  appliquer()
}

function effacerFiltres() {
  for (const cle of CRITERES) {
    if (cle !== 'q') {
      criteres[cle] = ''
    }
  }
  appliquer()
}

// ---------- Listes des filtres ----------

/** Les pièces, regroupées par logement ; seulement celles du logement choisi, s'il y en a un. */
const groupesPieces = computed(() => (logements.value ?? [])
  .filter(logement => !criteres.logement || String(logement.id) === criteres.logement)
  .map(logement => ({ id: logement.id, nom: logement.nom, pieces: logement.pieces ?? [] })))

const rangements = computed(() => (logements.value ?? [])
  .flatMap(logement => logement.pieces ?? [])
  .find(piece => String(piece.id) === criteres.piece)?.stockages ?? [])

// ---------- Résultats ----------

const { data: articles, status, error } = await useAsyncData(
  'recherche',
  () => api<ArticleMembre[]>('/api/articles', { query: { ...requete.value, page: page.value } }),
  { watch: [requete, page] },
)
const suivante = computed(() => (articles.value?.length ?? 0) === PAR_PAGE)

// Seule l'adresse change d'une page à l'autre : Nuxt ne remonte pas en haut de lui-même.
watch(page, () => window.scrollTo({ top: 0 }))

const resume = computed(() => {
  const nombre = articles.value?.length ?? 0
  if (page.value > 1 || suivante.value) {
    const debut = (page.value - 1) * PAR_PAGE + 1
    return nombre ? `Page ${page.value} : articles ${debut} à ${debut + nombre - 1}` : `Page ${page.value} : aucun article`
  }
  return nombre === 0 ? 'Aucun article trouvé' : nombre === 1 ? '1 article trouvé' : `${nombre} articles trouvés`
})

function versPage(numero: number) {
  return { query: { ...requete.value, ...(numero > 1 ? { page: String(numero) } : {}) } }
}

function photo(article: ArticleMembre): string {
  return `${config.public.apiBase}${article.photoUrl}`
}

function details(article: ArticleMembre): string {
  return [article.marque, article.couleur].filter(Boolean).join(' · ')
}

function emplacement(article: ArticleMembre): string {
  const e = article.emplacement
  return e ? `${e.logement.nom} › ${e.piece.nom} › ${e.stockage.nom}` : ''
}
</script>

<template>
  <div class="recherche">
    <h1>Rechercher</h1>

    <form role="search" class="formulaire-recherche" aria-label="Rechercher un article" @submit.prevent="appliquer">
      <div class="champ-texte">
        <label for="texte" class="visuellement-cache">Rechercher un article</label>
        <Icone nom="recherche" :taille="20" class="loupe" />
        <input
          id="texte"
          v-model="criteres.q"
          type="search"
          enterkeyhint="search"
          placeholder="Veste, Levi's, noir, Milan…"
          maxlength="100"
          data-cy="recherche-texte"
          @input="apresFrappe"
        >
      </div>

      <button
        type="button"
        class="bouton-filtres"
        :aria-expanded="filtresOuverts"
        aria-controls="filtres"
        @click="filtresOuverts = !filtresOuverts"
      >
        Filtres<span v-if="nombreFiltres" class="compteur"> ({{ nombreFiltres }})</span>
      </button>

      <div v-show="filtresOuverts" id="filtres" class="filtres" data-cy="filtres">
        <div class="champ">
          <label for="filtre-logement">Logement</label>
          <select id="filtre-logement" v-model="criteres.logement" @change="choisirLogement">
            <option value="">Tous les logements</option>
            <option v-for="logement in logements" :key="logement.id" :value="String(logement.id)">{{ logement.nom }}</option>
          </select>
        </div>
        <div class="champ">
          <label for="filtre-piece">Pièce</label>
          <select id="filtre-piece" v-model="criteres.piece" @change="choisirPiece">
            <option value="">Toutes les pièces</option>
            <optgroup v-for="groupe in groupesPieces" :key="groupe.id" :label="groupe.nom">
              <option v-for="piece in groupe.pieces" :key="piece.id" :value="String(piece.id)">{{ piece.nom }}</option>
            </optgroup>
          </select>
        </div>
        <div class="champ">
          <label for="filtre-rangement">Rangement</label>
          <select id="filtre-rangement" v-model="criteres.stockage" :disabled="!criteres.piece" aria-describedby="aide-rangement" @change="appliquer">
            <option value="">{{ criteres.piece ? 'Tous les rangements' : 'Choisissez une pièce' }}</option>
            <option v-for="stockage in rangements" :key="stockage.id" :value="String(stockage.id)">{{ stockage.nom }}</option>
          </select>
          <span v-if="!criteres.piece" id="aide-rangement" class="aide">Choisissez d'abord une pièce.</span>
        </div>
        <div class="champ">
          <label for="filtre-categorie">Catégorie</label>
          <select id="filtre-categorie" v-model="criteres.categorie" @change="appliquer">
            <option value="">Toutes les catégories</option>
            <option v-for="categorie in categories" :key="categorie.id" :value="String(categorie.id)">{{ categorie.nom }}</option>
          </select>
        </div>
        <div class="champ">
          <label for="filtre-couleur">Couleur</label>
          <input id="filtre-couleur" v-model="criteres.couleur" maxlength="40" placeholder="ex. noir" data-cy="filtre-couleur" @input="apresFrappe">
        </div>
        <div class="champ">
          <label for="filtre-marque">Marque</label>
          <input id="filtre-marque" v-model="criteres.marque" maxlength="80" placeholder="ex. Levi's" @input="apresFrappe">
        </div>
        <button v-if="nombreFiltres" type="button" class="effacer" @click="effacerFiltres">Effacer les filtres</button>
      </div>
    </form>

    <p v-if="error" class="alerte" role="alert">{{ lireErreur(error).general }}</p>

    <template v-else>
      <p class="resume" role="status" data-cy="resume">{{ status === 'pending' ? 'Recherche…' : resume }}</p>

      <ul v-if="articles?.length" class="grille" :aria-busy="status === 'pending'" data-cy="resultats">
        <li v-for="article in articles" :key="article.id">
          <NuxtLink :to="`/articles/${article.id}`" class="article" data-cy="article">
            <div class="vignette">
              <img :src="photo(article)" alt="" loading="lazy">
              <span v-if="article.estPersonnel" class="personnel">Personnel</span>
            </div>
            <span class="categorie">{{ article.categorie.nom }}</span>
            <span v-if="details(article)" class="details">{{ details(article) }}</span>
            <span class="emplacement">{{ emplacement(article) }}</span>
          </NuxtLink>
        </li>
      </ul>

      <div v-else-if="status !== 'pending'" class="vide carte">
        <template v-if="Object.keys(requete).length">
          <p class="vide-titre">Aucun article ne correspond</p>
          <p>Essayez un autre mot, ou retirez des filtres.</p>
          <button v-if="nombreFiltres" type="button" class="bouton bouton-secondaire" @click="effacerFiltres">Effacer les filtres</button>
        </template>
        <template v-else>
          <p class="vide-titre">Aucun article pour l'instant</p>
          <p>Prenez un article en photo et choisissez où le ranger.</p>
          <NuxtLink to="/ajout" class="bouton bouton-principal">Ajouter un article</NuxtLink>
        </template>
      </div>

      <nav v-if="page > 1 || suivante" class="pagination" aria-label="Pages de résultats">
        <NuxtLink v-if="page > 1" :to="versPage(page - 1)" class="bouton bouton-secondaire" rel="prev">Page précédente</NuxtLink>
        <span v-else class="bouton bouton-secondaire inactif" aria-disabled="true">Page précédente</span>
        <span class="numero" aria-current="page">Page {{ page }}</span>
        <NuxtLink v-if="suivante" :to="versPage(page + 1)" class="bouton bouton-secondaire" rel="next" data-cy="page-suivante">Page suivante</NuxtLink>
        <span v-else class="bouton bouton-secondaire inactif" aria-disabled="true">Page suivante</span>
      </nav>
    </template>
  </div>
</template>

<style scoped>
h1 {
  margin: 0 0 16px;
  color: var(--bordeaux);
}

.formulaire-recherche {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 10px;
}

.champ-texte {
  position: relative;
}

.loupe {
  position: absolute;
  top: 15px;
  left: 14px;
  color: var(--texte-doux);
  pointer-events: none;
}

.champ-texte input {
  width: 100%;
  height: 50px;
  padding: 0 14px 0 44px;
  border: 1px solid var(--bordure);
  border-radius: var(--rayon);
  background: var(--surface);
  color: var(--texte);
  font: inherit;
  font-size: 16px;
}

.champ-texte input:focus,
.champ select:focus {
  outline: 2px solid var(--bordeaux);
  outline-offset: 1px;
}

.bouton-filtres {
  min-height: 50px;
  padding: 0 16px;
  border: 1px solid var(--bordure);
  border-radius: var(--rayon);
  background: var(--surface);
  color: var(--texte);
  font: 700 14px var(--police-texte);
  cursor: pointer;
}

.bouton-filtres[aria-expanded='true'] {
  border-color: var(--bordeaux);
  color: var(--bordeaux);
}

.filtres {
  display: grid;
  grid-column: 1 / -1;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px 10px;
  padding: 14px;
  border: 1px solid var(--ligne);
  border-radius: var(--rayon);
  background: var(--surface);
}

.champ select {
  height: 46px;
  min-width: 0;
  padding: 0 10px;
  border: 1px solid var(--bordure);
  border-radius: var(--rayon);
  background: var(--surface);
  color: var(--texte);
  font: inherit;
  font-size: 15px;
}

.champ select:disabled {
  background: var(--fond);
  color: var(--texte-doux);
}

.champ input {
  height: 46px;
}

.effacer {
  grid-column: 1 / -1;
  justify-self: start;
  min-height: var(--zone-tactile);
  padding: 0 4px;
  border: 0;
  background: transparent;
  color: var(--bordeaux);
  font: 700 14px var(--police-texte);
  cursor: pointer;
}

.resume {
  margin: 16px 0 12px;
  color: var(--texte-doux);
  font-size: 14px;
}

.grille {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px 12px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.grille[aria-busy='true'] {
  opacity: 0.6;
}

.article {
  display: grid;
  color: var(--texte);
  text-decoration: none;
}

.article:hover .categorie {
  color: var(--bordeaux);
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

.categorie {
  margin-top: 8px;
  font-weight: 700;
}

.details,
.emplacement {
  color: var(--texte-doux);
  font-size: 13px;
}

.emplacement {
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

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 24px;
}

.pagination .bouton {
  padding: 0 14px;
}

.inactif {
  opacity: 0.45;
  cursor: not-allowed;
}

.numero {
  color: var(--texte-doux);
  font-size: 14px;
  font-weight: 700;
}

@media (min-width: 1024px) {
  h1 {
    font-size: 34px;
  }

  .bouton-filtres {
    display: none;
  }

  /* Sur ordinateur, les filtres restent visibles (v-show les masque seulement sur téléphone). */
  .filtres {
    display: grid !important;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 14px;
    padding: 0;
    border: 0;
    background: transparent;
  }

  .formulaire-recherche {
    grid-template-columns: 1fr;
  }

  .grille {
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 20px 16px;
  }
}
</style>
