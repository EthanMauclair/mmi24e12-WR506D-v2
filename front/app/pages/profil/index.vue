<script setup lang="ts">
/**
 * Écran 4 – Mon profil (F-04, P4, P5) : compteurs, visibilité, tenues et articles.
 * Les informations du compte (e-mail, date de naissance, mot de passe) sont dans les paramètres.
 */
useHead({ title: 'Mon profil – Penderie' })

interface Profil {
  pseudo: string
  estPrive: boolean
  nombreAbonnes: number
  // Absent quand on ne peut pas voir les articles (A-03) ; toujours présent sur son propre profil.
  nombreArticles?: number
}

interface ArticlePublic {
  id: number
  photoUrl: string
  categorie: { id: number, nom: string }
  marque?: string
  couleur?: string
}

const api = useApi()
const config = useRuntimeConfig()
const { moi } = useSession()
const pseudo = moi.value!.pseudo

const [{ data: profil }, { data: articles }] = await Promise.all([
  useAsyncData(`profil-${pseudo}`, () => api<Profil>(`/api/utilisateurs/${pseudo}`)),
  useAsyncData(`articles-${pseudo}`, () => api<ArticlePublic[]>(`/api/utilisateurs/${pseudo}/articles`)),
])

// La photo est servie par l'API (D-015) : le navigateur la demande avec le cookie de session.
function photo(article: ArticlePublic): string {
  return `${config.public.apiBase}${article.photoUrl}`
}

function libelle(article: ArticlePublic): string {
  return [article.categorie.nom, article.marque, article.couleur].filter(Boolean).join(' · ')
}

/** 18 000 → « 18 k », comme sur la maquette. */
function compact(nombre: number): string {
  return new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(nombre)
}

type Onglet = 'tenues' | 'articles'
const onglet = ref<Onglet>('articles')
</script>

<template>
  <div v-if="profil" class="profil">
    <section class="entete">
      <div class="avatar" aria-hidden="true">{{ profil.pseudo.charAt(0).toUpperCase() }}</div>

      <div class="identite">
        <div class="pseudo-ligne">
          <h1>@{{ profil.pseudo }}</h1>
          <span class="badge">{{ profil.estPrive ? 'Privé' : 'Public' }}</span>
        </div>

        <dl class="compteurs">
          <div>
            <dt>abonnés</dt>
            <dd>{{ compact(profil.nombreAbonnes) }}</dd>
          </div>
          <div>
            <dt>articles</dt>
            <dd>{{ compact(profil.nombreArticles ?? 0) }}</dd>
          </div>
          <div>
            <!-- Tenues : lot suivant (F-23) ; aucune ne peut encore exister. -->
            <dt>tenues</dt>
            <dd>0</dd>
          </div>
        </dl>
      </div>

      <div class="actions">
        <NuxtLink to="/profil/parametres" class="bouton bouton-secondaire">Modifier le profil</NuxtLink>
        <span class="bouton bouton-principal action-inactive" aria-disabled="true" title="Bientôt disponible">Composer une tenue</span>
      </div>
    </section>

    <div class="onglets" role="tablist" aria-label="Contenu du profil">
      <button
        id="onglet-tenues"
        type="button"
        role="tab"
        :aria-selected="onglet === 'tenues'"
        aria-controls="panneau-tenues"
        @click="onglet = 'tenues'"
      >
        Tenues
      </button>
      <button
        id="onglet-articles"
        type="button"
        role="tab"
        :aria-selected="onglet === 'articles'"
        aria-controls="panneau-articles"
        @click="onglet = 'articles'"
      >
        Articles
      </button>
    </div>

    <section v-show="onglet === 'tenues'" id="panneau-tenues" role="tabpanel" aria-labelledby="onglet-tenues" class="vide">
      <p class="vide-titre">Aucune tenue pour l'instant</p>
      <p>La composition de tenues arrive bientôt.</p>
    </section>

    <section v-show="onglet === 'articles'" id="panneau-articles" role="tabpanel" aria-labelledby="onglet-articles">
      <ul v-if="articles?.length" class="grille" data-cy="articles">
        <li v-for="article in articles" :key="article.id">
          <NuxtLink :to="`/articles/${article.id}`" class="lien-article">
            <img :src="photo(article)" :alt="libelle(article)" loading="lazy">
          </NuxtLink>
        </li>
      </ul>
      <div v-else class="vide">
        <p class="vide-titre">Aucun article pour l'instant</p>
        <p>Vos articles apparaîtront ici, sauf ceux que vous marquez personnels.</p>
      </div>
    </section>
  </div>
</template>

<style scoped>
.entete {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 14px 16px;
  align-items: center;
}

.avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 76px;
  height: 76px;
  border: 2px solid var(--bordeaux);
  border-radius: 50%;
  background: var(--bordeaux-clair);
  color: var(--bordeaux);
  font-size: 32px;
  font-weight: 700;
}

.identite {
  display: contents;
}

.pseudo-ligne {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  grid-column: 1 / -1;
  grid-row: 2;
}

h1 {
  margin: 0;
  font-size: 18px;
  overflow-wrap: anywhere;
}

.badge {
  padding: 3px 9px;
  border-radius: 999px;
  background: var(--bordeaux-clair);
  color: var(--bordeaux);
  font-size: 12px;
  font-weight: 700;
}

.compteurs {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 4px;
  margin: 0;
  text-align: center;
  grid-column: 2;
  grid-row: 1;
}

.compteurs div {
  display: flex;
  flex-direction: column-reverse;
}

.compteurs dt {
  color: var(--texte-doux);
  font-size: 13px;
}

.compteurs dd {
  margin: 0;
  font-size: 19px;
  font-weight: 700;
}

.actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  grid-column: 1 / -1;
}

.actions .bouton {
  min-height: var(--zone-tactile);
  padding: 0 12px;
  border-radius: var(--rayon);
  font-size: 14px;
  text-align: center;
}

.action-inactive {
  opacity: 0.55;
  cursor: not-allowed;
}

.onglets {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin: 18px -20px 0;
  border-bottom: 1px solid var(--ligne);
}

.onglets button {
  height: var(--zone-tactile);
  border: 0;
  border-bottom: 2px solid transparent;
  background: transparent;
  color: var(--texte-doux);
  font: 700 15px var(--police-texte);
  cursor: pointer;
}

.onglets button[aria-selected='true'] {
  border-bottom-color: var(--bordeaux);
  color: var(--bordeaux);
}

.grille {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 3px;
  margin: 3px -17px 0;
  padding: 0;
  list-style: none;
}

.lien-article {
  display: block;
}

.grille img {
  display: block;
  width: 100%;
  aspect-ratio: 3 / 4;
  object-fit: cover;
  background: var(--bordeaux-clair);
}

.vide {
  padding: 40px 16px;
  color: var(--texte-doux);
  text-align: center;
}

.vide p {
  margin: 0 0 6px;
}

.vide-titre {
  color: var(--texte);
  font-weight: 700;
}

@media (min-width: 1024px) {
  .entete {
    grid-template-columns: auto 1fr auto;
    gap: 32px;
  }

  .avatar {
    width: 120px;
    height: 120px;
    border-width: 3px;
    font-size: 48px;
  }

  .identite {
    display: grid;
    gap: 12px;
  }

  .pseudo-ligne,
  .compteurs {
    grid-column: auto;
    grid-row: auto;
  }

  h1 {
    font-size: 26px;
  }

  .compteurs {
    display: flex;
    gap: 28px;
    text-align: left;
  }

  .compteurs div {
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 6px;
    align-items: baseline;
  }

  .compteurs dt,
  .compteurs dd {
    font-size: 15px;
  }

  .actions {
    display: flex;
    grid-column: auto;
  }

  .actions .bouton {
    padding: 0 18px;
  }

  .onglets {
    display: flex;
    gap: 28px;
    margin: 28px 0 0;
  }

  .onglets button {
    padding: 0;
  }

  .grille {
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
    margin: 16px 0 0;
  }

  .grille img {
    aspect-ratio: 4 / 5;
    border-radius: var(--rayon);
  }
}
</style>
