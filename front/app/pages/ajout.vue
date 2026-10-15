<script setup lang="ts">
/**
 * Écrans 5 et 6 – Ajouter un article (F-07, F-08, F-10, P2), en deux étapes sur la même page :
 * 1. la photo (caméra, galerie, glisser-déposer ou webcam), envoyée à POST /api/reconnaissances ;
 * 2. la fiche (catégorie, marque, couleur, rangement, note, personnel), envoyée à POST /api/articles.
 * En V1, la photo ne remplit rien (D-023) : la fiche est toujours saisie à la main.
 */
useHead({ title: 'Ajouter un article – Penderie' })

interface Logement {
  id: number
  nom: string
  pieces?: { id: number, nom: string, stockages: { id: number, nom: string }[] }[]
}

const api = useApi()
const TAILLE_MAX = 10 * 1024 * 1024
const FORMATS = ['image/jpeg', 'image/png', 'image/webp']

// ---------- Étape 1 : la photo ----------

const etape = ref<'photo' | 'fiche'>('photo')
const envoiPhoto = ref(false)
const erreurPhoto = ref<string | null>(null)
const photo = ref<string | null>(null)
const apercu = ref<string | null>(null)

const video = ref<HTMLVideoElement | null>(null)
const champCamera = ref<HTMLInputElement | null>(null)
const champGalerie = ref<HTMLInputElement | null>(null)
const camera = ref<'arret' | 'demarrage' | 'active' | 'indisponible'>('arret')
const survol = ref(false)
let flux: MediaStream | null = null

async function demarrerCamera() {
  if (!navigator.mediaDevices?.getUserMedia) {
    camera.value = 'indisponible'
    return
  }
  camera.value = 'demarrage'
  try {
    flux = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 } }, audio: false })
    video.value!.srcObject = flux
    await video.value!.play()
    camera.value = 'active'
  }
  catch {
    // Refus d'accès, pas de caméra, ou page servie sans HTTPS depuis un téléphone.
    arreterCamera()
    camera.value = 'indisponible'
  }
}

function arreterCamera() {
  flux?.getTracks().forEach(piste => piste.stop())
  flux = null
  camera.value = 'arret'
}

/** Déclencheur : photo de la caméra intégrée, ou appareil photo du téléphone si elle n'est pas disponible. */
function declencher() {
  if (camera.value !== 'active' || !video.value) {
    champCamera.value?.click()
    return
  }
  const image = document.createElement('canvas')
  image.width = video.value.videoWidth
  image.height = video.value.videoHeight
  image.getContext('2d')!.drawImage(video.value, 0, 0)
  image.toBlob((blob) => {
    if (blob) {
      envoyer(new File([blob], 'photo.jpg', { type: 'image/jpeg' }))
    }
  }, 'image/jpeg', 0.9)
}

function fichierChoisi(evenement: Event) {
  const champ = evenement.target as HTMLInputElement
  const fichier = champ.files?.[0]
  champ.value = ''
  if (fichier) {
    envoyer(fichier)
  }
}

function deposer(evenement: DragEvent) {
  survol.value = false
  const fichier = evenement.dataTransfer?.files[0]
  if (fichier) {
    envoyer(fichier)
  }
}

async function envoyer(fichier: File) {
  erreurPhoto.value = null
  if (!FORMATS.includes(fichier.type)) {
    erreurPhoto.value = 'Format non pris en charge : envoyez une photo JPG, PNG ou WebP.'
    return
  }
  if (fichier.size > TAILLE_MAX) {
    erreurPhoto.value = 'La photo dépasse 10 Mo.'
    return
  }

  envoiPhoto.value = true
  try {
    const corps = new FormData()
    corps.append('photo', fichier)
    // L'API réduit la photo, la redresse et retire ses métadonnées (position GPS comprise).
    const reponse = await api<{ photo: string }>('/api/reconnaissances', { method: 'POST', body: corps })
    photo.value = reponse.photo
    if (apercu.value) {
      URL.revokeObjectURL(apercu.value)
    }
    apercu.value = URL.createObjectURL(fichier)
    arreterCamera()
    etape.value = 'fiche'
    window.scrollTo(0, 0)
  }
  catch (e) {
    erreurPhoto.value = lireErreur(e).general ?? 'La photo n\'a pas pu être envoyée.'
  }
  finally {
    envoiPhoto.value = false
  }
}

function changerDePhoto() {
  etape.value = 'photo'
  demarrerSurTelephone()
}

/** Sur téléphone, la caméra s'ouvre d'emblée ; sur ordinateur, on attend « Utiliser la webcam ». */
function demarrerSurTelephone() {
  if (window.matchMedia('(max-width: 1023px)').matches) {
    nextTick(demarrerCamera)
  }
}

onMounted(demarrerSurTelephone)
onBeforeUnmount(() => {
  arreterCamera()
  if (apercu.value) {
    URL.revokeObjectURL(apercu.value)
  }
})

// ---------- Étape 2 : la fiche ----------

const [{ data: categories }, { data: logements }] = await Promise.all([
  useAsyncData('categories', () => api<{ id: number, nom: string }[]>('/api/categories')),
  useAsyncData('logements-rangements', async () => {
    const liste = await api<Logement[]>('/api/logements')
    return Promise.all(liste.map(l => api<Logement>(`/api/logements/${l.id}`)))
  }),
])

/** Les rangements, regroupés par « Logement › Pièce » (D-010 : on choisit où ranger après la photo). */
const groupesRangements = computed(() => (logements.value ?? []).flatMap(logement =>
  (logement.pieces ?? [])
    .filter(piece => piece.stockages.length > 0)
    .map(piece => ({ libelle: `${logement.nom} › ${piece.nom}`, stockages: piece.stockages })),
))

const fiche = reactive({
  categorie: null as number | null,
  marque: '',
  couleur: '',
  stockage: null as number | null,
  note: '',
  estPersonnel: false,
})
const envoiFiche = ref(false)
const erreurFiche = ref<string | null>(null)
const erreurs = ref<Record<string, string>>({})

async function enregistrer() {
  erreurFiche.value = null
  const manquants: Record<string, string> = {}
  if (!fiche.categorie) {
    manquants.categorie = 'Choisissez une catégorie.'
  }
  if (!fiche.stockage) {
    manquants.stockage = 'Choisissez où ranger l\'article.'
  }
  erreurs.value = manquants
  if (Object.keys(manquants).length > 0) {
    return
  }

  envoiFiche.value = true
  try {
    await api('/api/articles', {
      method: 'POST',
      body: {
        photo: photo.value,
        categorie: fiche.categorie,
        stockage: fiche.stockage,
        // Un champ facultatif laissé vide n'est pas envoyé.
        marque: fiche.marque.trim() || undefined,
        couleur: fiche.couleur.trim() || undefined,
        note: fiche.note.trim() || undefined,
        estPersonnel: fiche.estPersonnel,
      },
    })
    await navigateTo('/profil')
  }
  catch (e) {
    const { general, champs } = lireErreur(e)
    erreurFiche.value = general
    erreurs.value = champs
  }
  finally {
    envoiFiche.value = false
  }
}
</script>

<template>
  <div class="ajout">
    <!-- ==================== Étape 1 : la photo ==================== -->
    <section v-show="etape === 'photo'" class="etape-photo" aria-labelledby="titre-photo">
      <header class="entete-photo">
        <NuxtLink to="/profil" class="bouton-rond" aria-label="Fermer">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
        </NuxtLink>
        <h1 id="titre-photo">Ajouter un article</h1>
        <span class="bouton-rond" aria-hidden="true" />
      </header>
      <p class="sous-titre">Envoyez une photo de l'article, puis complétez sa fiche.</p>

      <div class="zones">
        <div
          class="zone"
          :class="{ survol, 'camera-active': camera === 'active' }"
          @dragover.prevent="survol = true"
          @dragleave="survol = false"
          @drop.prevent="deposer"
        >
          <video ref="video" v-show="camera === 'active'" class="video" muted playsinline aria-label="Aperçu de l'appareil photo" />
          <div v-show="camera === 'active'" class="cadre" aria-hidden="true">
            <span /><span /><span /><span />
          </div>

          <div v-if="camera !== 'active'" class="depot">
            <div class="depot-icone" aria-hidden="true">
              <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4" /><path d="m7 9 5-5 5 5" /><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" /></svg>
            </div>
            <p class="depot-titre">
              <span class="seulement-desktop">Glissez une photo ici</span>
              <span class="seulement-mobile">{{ camera === 'demarrage' ? 'Ouverture de l\'appareil photo…' : 'Prenez l\'article en photo' }}</span>
            </p>
            <p class="depot-aide">JPG, PNG ou WebP, 10 Mo maximum</p>
            <div class="depot-actions seulement-desktop">
              <button type="button" class="bouton bouton-principal" :disabled="envoiPhoto" @click="champGalerie?.click()">Choisir un fichier</button>
              <button type="button" class="bouton bouton-contour" :disabled="envoiPhoto || camera === 'demarrage'" @click="demarrerCamera">Utiliser la webcam</button>
            </div>
            <p v-if="camera === 'indisponible'" class="depot-aide">Caméra indisponible : utilisez le bouton de prise de vue ou un fichier.</p>
          </div>

          <p class="conseil">Posez l'article à plat, sur un fond uni, et cadrez-le en entier.</p>
        </div>

        <aside class="conseils">
          <h2>Pour une bonne photo</h2>
          <ol>
            <li>Posez l'article à plat, sur un fond uni.</li>
            <li>Cadrez-le en entier, en pleine lumière.</li>
            <li>Une photo par article.</li>
          </ol>
          <p class="note-gps">La position GPS de la photo est supprimée avant l'enregistrement.</p>
        </aside>
      </div>

      <p v-if="erreurPhoto" class="alerte" role="alert">{{ erreurPhoto }}</p>
      <p v-if="envoiPhoto" class="envoi" role="status">Envoi de la photo…</p>

      <div class="commandes">
        <button type="button" class="bouton-galerie" aria-label="Choisir une photo dans la galerie" :disabled="envoiPhoto" @click="champGalerie?.click()">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="m21 17-5-5-9 8" /></svg>
        </button>
        <button type="button" class="declencheur" aria-label="Prendre la photo" :disabled="envoiPhoto" @click="declencher">
          <span />
        </button>
        <button v-if="camera === 'active'" type="button" class="arreter seulement-desktop" @click="arreterCamera">Arrêter la webcam</button>
        <span v-else class="emplacement" aria-hidden="true" />
      </div>

      <input ref="champGalerie" type="file" accept="image/jpeg,image/png,image/webp" class="visuellement-cache" tabindex="-1" aria-hidden="true" @change="fichierChoisi">
      <input ref="champCamera" type="file" accept="image/*" capture="environment" class="visuellement-cache" tabindex="-1" aria-hidden="true" @change="fichierChoisi">
    </section>

    <!-- ==================== Étape 2 : la fiche ==================== -->
    <section v-if="etape === 'fiche'" class="etape-fiche" aria-labelledby="titre-fiche">
      <header class="entete-fiche">
        <button type="button" class="bouton-retour" aria-label="Changer de photo" @click="changerDePhoto">
          <Icone nom="retour" :taille="22" :epaisseur="1.8" />
        </button>
        <h1 id="titre-fiche">Nouvel article</h1>
      </header>

      <div class="fiche">
        <div class="colonne-photo">
          <img v-if="apercu" :src="apercu" alt="Photo de l'article" class="photo">
          <button type="button" class="lien-photo seulement-desktop" @click="changerDePhoto">Changer de photo</button>
        </div>

        <form class="formulaire colonne-formulaire" novalidate @submit.prevent="enregistrer">
          <div class="bandeau">
            <strong>Fiche de l'article.</strong> Indiquez la catégorie, la marque et la couleur.
          </div>

          <p v-if="erreurFiche" class="alerte" role="alert">{{ erreurFiche }}</p>

          <div class="trio">
            <div class="champ">
              <label for="categorie">Catégorie</label>
              <select id="categorie" v-model="fiche.categorie" :aria-invalid="!!erreurs.categorie" aria-describedby="categorie-erreur">
                <option :value="null" disabled>Choisir…</option>
                <option v-for="categorie in categories" :key="categorie.id" :value="categorie.id">{{ categorie.nom }}</option>
              </select>
              <span v-if="erreurs.categorie" id="categorie-erreur" class="message-champ">{{ erreurs.categorie }}</span>
            </div>
            <div class="champ">
              <label for="marque">Marque</label>
              <input id="marque" v-model="fiche.marque" maxlength="80" :aria-invalid="!!erreurs.marque">
            </div>
            <div class="champ">
              <label for="couleur">Couleur</label>
              <input id="couleur" v-model="fiche.couleur" maxlength="40" :aria-invalid="!!erreurs.couleur">
            </div>
          </div>

          <div class="champ">
            <label for="rangement">Où le ranger ?</label>
            <p v-if="groupesRangements.length === 0" class="alerte">
              Aucun rangement pour l'instant. <NuxtLink to="/logements/nouveau">Créez votre logement</NuxtLink>, ses pièces et leurs rangements.
            </p>
            <select v-else id="rangement" v-model="fiche.stockage" :aria-invalid="!!erreurs.stockage" aria-describedby="rangement-erreur">
              <option :value="null" disabled>Choisir un rangement…</option>
              <optgroup v-for="groupe in groupesRangements" :key="groupe.libelle" :label="groupe.libelle">
                <option v-for="stockage in groupe.stockages" :key="stockage.id" :value="stockage.id">{{ groupe.libelle }} › {{ stockage.nom }}</option>
              </optgroup>
            </select>
            <span v-if="erreurs.stockage" id="rangement-erreur" class="message-champ">{{ erreurs.stockage }}</span>
          </div>

          <div class="champ">
            <label for="note">Note <span class="facultatif">(facultatif)</span></label>
            <textarea id="note" v-model="fiche.note" rows="2" maxlength="2000" placeholder="ex. Acheté à Milan, à faire retoucher" />
          </div>

          <label class="personnel">
            <span>
              <strong>Article personnel</strong>
              <span class="aide">Visible seulement par vous et les co-propriétaires du logement</span>
            </span>
            <input v-model="fiche.estPersonnel" type="checkbox">
          </label>

          <div class="actions">
            <NuxtLink to="/profil" class="bouton bouton-secondaire seulement-desktop">Annuler</NuxtLink>
            <button type="submit" class="bouton bouton-principal" :disabled="envoiFiche">
              {{ envoiFiche ? 'Enregistrement…' : 'Enregistrer l\'article' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </div>
</template>

<style scoped>
.seulement-desktop {
  display: none !important;
}

/* ==================== Étape 1, mobile : plein écran sombre ==================== */

.etape-photo {
  position: fixed;
  inset: 0;
  z-index: 20;
  display: flex;
  flex-direction: column;
  background: #151012;
  color: #fff;
}

.entete-photo {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 16px 8px;
}

.entete-photo h1 {
  margin: 0;
  color: #fff;
  font-size: 16px;
}

.bouton-rond {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--zone-tactile);
  height: var(--zone-tactile);
  color: #fff;
}

.sous-titre,
.conseils {
  display: none;
}

.zones {
  display: flex;
  flex-grow: 1;
  min-height: 0;
  margin: 8px 16px 0;
}

.zone {
  position: relative;
  display: flex;
  flex-grow: 1;
  align-items: center;
  justify-content: center;
  border-radius: 22px;
  background: repeating-linear-gradient(135deg, #2a2022 0 14px, #30262a 14px 28px);
  overflow: hidden;
}

.video {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cadre {
  position: relative;
  width: 250px;
  height: 320px;
  max-height: 60%;
}

.cadre span {
  position: absolute;
  width: 36px;
  height: 36px;
  border: 0 solid #fff;
}

.cadre span:nth-child(1) { top: 0; left: 0; border-top-width: 3px; border-left-width: 3px; border-top-left-radius: 14px; }
.cadre span:nth-child(2) { top: 0; right: 0; border-top-width: 3px; border-right-width: 3px; border-top-right-radius: 14px; }
.cadre span:nth-child(3) { bottom: 0; left: 0; border-bottom-width: 3px; border-left-width: 3px; border-bottom-left-radius: 14px; }
.cadre span:nth-child(4) { bottom: 0; right: 0; border-bottom-width: 3px; border-right-width: 3px; border-bottom-right-radius: 14px; }

.depot {
  display: grid;
  justify-items: center;
  gap: 10px;
  padding: 24px;
  text-align: center;
}

.depot-icone {
  display: none;
}

.depot-titre {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.depot-aide {
  margin: 0;
  color: #d9cfd1;
  font-size: 14px;
}

.conseil {
  position: absolute;
  right: 20px;
  bottom: 18px;
  left: 20px;
  margin: 0;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgb(21 16 18 / 0.78);
  font-size: 14px;
  text-align: center;
}

.etape-photo .alerte,
.envoi {
  margin: 12px 16px 0;
}

.envoi {
  color: #fff;
  text-align: center;
}

.commandes {
  display: grid;
  flex-shrink: 0;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  align-items: center;
  justify-items: center;
  height: 150px;
  padding: 0 24px max(0px, env(safe-area-inset-bottom));
}

.bouton-galerie {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  border: 1px solid rgb(255 255 255 / 0.35);
  border-radius: 14px;
  background: #2a2022;
  color: #fff;
  cursor: pointer;
}

.declencheur {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 84px;
  height: 84px;
  padding: 0;
  border: 4px solid #fff;
  border-radius: 50%;
  background: transparent;
  cursor: pointer;
}

.declencheur span {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #8c2f3f;
}

.declencheur:disabled,
.bouton-galerie:disabled {
  opacity: 0.5;
  cursor: wait;
}

/* ==================== Étape 2 : la fiche ==================== */

.entete-fiche {
  display: flex;
  align-items: center;
  gap: 4px;
}

.entete-fiche h1 {
  margin: 0;
  color: var(--bordeaux);
  font-size: 22px;
}

.bouton-retour {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--zone-tactile);
  height: var(--zone-tactile);
  margin-left: -10px;
  border: 0;
  background: transparent;
  color: var(--texte);
  cursor: pointer;
}

.fiche {
  display: grid;
  grid-template-columns: 92px 1fr;
  gap: 14px;
  margin-top: 10px;
}

.photo {
  display: block;
  width: 92px;
  height: 116px;
  border-radius: var(--rayon-bouton);
  background: var(--bordeaux-clair);
  object-fit: cover;
}

.colonne-formulaire {
  display: contents;
}

.bandeau {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
  padding: 12px;
  border-radius: var(--rayon-bouton);
  background: var(--bordeaux-clair);
  color: #4a3d40;
  font-size: 13px;
  line-height: 1.4;
}

.bandeau strong {
  color: var(--bordeaux);
}

.colonne-formulaire > :not(.bandeau) {
  grid-column: 1 / -1;
}

.trio {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px 10px;
}

.trio .champ:first-child {
  grid-column: 1 / -1;
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
  height: 46px;
  padding: 0 12px;
}

.champ input {
  height: 46px;
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
  margin-top: 8px;
}

/* ==================== Desktop ==================== */

@media (min-width: 1024px) {
  .seulement-desktop {
    display: inline-flex !important;
  }

  .seulement-mobile,
  .commandes,
  .conseil {
    display: none;
  }

  .etape-photo {
    position: static;
    display: grid;
    gap: 24px;
    background: transparent;
    color: var(--texte);
  }

  .entete-photo {
    padding: 0;
  }

  .entete-photo .bouton-rond {
    display: none;
  }

  .entete-photo h1,
  .entete-fiche h1 {
    color: var(--bordeaux);
    font-size: 34px;
  }

  .sous-titre {
    display: block;
    margin: -18px 0 0;
    color: var(--texte-doux);
    font-size: 15px;
  }

  .zones {
    gap: 32px;
    min-height: 560px;
    margin: 0;
  }

  .zone {
    border: 2px dashed #b8a9ac;
    background: var(--surface);
  }

  .zone.survol {
    border-color: var(--bordeaux);
    background: var(--bordeaux-clair);
  }

  .zone.camera-active {
    border-style: solid;
    background: #151012;
  }

  .depot {
    gap: 18px;
    padding: 40px;
  }

  .depot-icone {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border-radius: 20px;
    background: var(--bordeaux-clair);
    color: var(--bordeaux);
  }

  .depot-titre {
    font-size: 20px;
  }

  .depot-aide {
    color: var(--texte-doux);
  }

  .depot-actions {
    gap: 12px;
    margin-top: 6px;
  }

  .depot-actions .bouton {
    min-height: 48px;
    border-radius: var(--rayon);
    font-size: 15px;
  }

  .bouton-contour {
    border: 1px solid var(--bordeaux);
    background: var(--surface);
    color: var(--bordeaux);
  }

  .conseils {
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    gap: 16px;
    width: 320px;
    padding: 28px;
    border: 1px solid var(--ligne);
    border-radius: 22px;
    background: var(--surface);
  }

  .conseils h2 {
    margin: 0;
    font-size: 17px;
  }

  .conseils ol {
    display: grid;
    gap: 14px;
    margin: 0;
    padding-left: 1.2rem;
    color: #4a3d40;
    font-size: 14px;
  }

  .conseils ol li::marker {
    color: var(--bordeaux);
    font-weight: 700;
  }

  .note-gps {
    margin: auto 0 0;
    padding: 12px;
    border-radius: var(--rayon);
    background: var(--fond);
    color: var(--texte-doux);
    font-size: 13px;
  }

  .etape-photo .alerte,
  .envoi {
    margin: 0;
  }

  .envoi {
    color: var(--texte-doux);
  }

  /* Webcam active : déclencheur et arrêt sous la zone. */
  .etape-photo:has(.camera-active) .commandes {
    display: flex;
    justify-content: center;
    gap: 24px;
    height: auto;
    padding: 0;
  }

  .etape-photo:has(.camera-active) .bouton-galerie {
    display: none;
  }

  .etape-photo:has(.camera-active) .declencheur {
    border-color: var(--bordeaux);
  }

  .arreter {
    min-height: var(--zone-tactile);
    padding: 0 18px;
    border: 1px solid var(--bordure);
    border-radius: var(--rayon);
    background: var(--surface);
    color: var(--texte);
    font: 700 14px var(--police-texte);
    cursor: pointer;
  }

  .fiche {
    grid-template-columns: 440px 1fr;
    gap: 40px;
    margin-top: 24px;
  }

  .colonne-photo {
    display: grid;
    align-content: start;
    gap: 14px;
  }

  .photo {
    width: 100%;
    height: auto;
    aspect-ratio: 4 / 5;
    border-radius: 22px;
  }

  .lien-photo {
    justify-self: start;
    border: 0;
    background: transparent;
    color: var(--bordeaux);
    font: 700 14px var(--police-texte);
    text-decoration: underline;
    cursor: pointer;
  }

  .colonne-formulaire {
    display: grid;
    align-content: start;
    gap: 16px;
  }

  .bandeau {
    display: block;
    padding: 14px 16px;
    font-size: 14px;
  }

  .trio {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
  }

  .trio .champ:first-child {
    grid-column: auto;
  }

  .champ select,
  .champ input {
    height: 48px;
  }

  .personnel {
    padding: 14px 16px;
  }

  .actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }

  .actions .bouton {
    padding: 0 28px;
  }
}
</style>
