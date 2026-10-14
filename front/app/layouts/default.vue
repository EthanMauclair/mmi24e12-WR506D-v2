<script setup lang="ts">
/**
 * Mise en page des écrans connectés (docs/09-maquettes.md) :
 * - mobile : barre du bas, bouton photo au centre, accessible partout (D-010) ;
 * - desktop : menu latéral, « Ajouter un article » en tête.
 * Les entrées sans `vers` arrivent avec les prochaines branches : affichées, mais inactives.
 */
interface Entree {
  libelle: string
  icone: 'accueil' | 'recherche' | 'logement' | 'profil'
  vers: string | null
}

const entrees: Entree[] = [
  { libelle: 'Accueil', icone: 'accueil', vers: '/' },
  { libelle: 'Rechercher', icone: 'recherche', vers: null },
  { libelle: 'Logements', icone: 'logement', vers: null },
  { libelle: 'Profil', icone: 'profil', vers: null },
]
const ajout: string | null = null

const { moi, deconnecter } = useSession()

async function seDeconnecter() {
  await deconnecter()
  await navigateTo('/connexion')
}
</script>

<template>
  <div class="application">
    <aside class="menu-lateral" aria-label="Menu principal">
      <NuxtLink to="/" class="logo">Penderie</NuxtLink>

      <NuxtLink v-if="ajout" :to="ajout" class="bouton bouton-principal">
        <Icone nom="photo" /> Ajouter un article
      </NuxtLink>
      <span v-else class="bouton bouton-principal inactif" aria-disabled="true" title="Bientôt disponible">
        <Icone nom="photo" /> Ajouter un article
      </span>

      <nav>
        <template v-for="entree in entrees" :key="entree.libelle">
          <NuxtLink v-if="entree.vers" :to="entree.vers" class="lien-menu">
            <Icone :nom="entree.icone" /> {{ entree.libelle }}
          </NuxtLink>
          <span v-else class="lien-menu inactif" aria-disabled="true" title="Bientôt disponible">
            <Icone :nom="entree.icone" /> {{ entree.libelle }}
          </span>
        </template>
      </nav>

      <div class="compte">
        <span v-if="moi" class="pseudo">@{{ moi.pseudo }}</span>
        <button type="button" class="lien-menu" @click="seDeconnecter">
          <Icone nom="deconnexion" /> Se déconnecter
        </button>
      </div>
    </aside>

    <header class="entete-mobile">
      <NuxtLink to="/" class="logo">Penderie</NuxtLink>
      <button type="button" class="bouton-icone" aria-label="Se déconnecter" @click="seDeconnecter">
        <Icone nom="deconnexion" />
      </button>
    </header>

    <main class="contenu">
      <slot />
    </main>

    <nav class="barre-bas" aria-label="Navigation">
      <template v-for="(entree, index) in entrees" :key="entree.libelle">
        <NuxtLink v-if="entree.vers" :to="entree.vers" class="onglet">
          <Icone :nom="entree.icone" />
          <span>{{ entree.libelle }}</span>
        </NuxtLink>
        <span v-else class="onglet inactif" aria-disabled="true">
          <Icone :nom="entree.icone" />
          <span>{{ entree.libelle }}</span>
        </span>

        <!-- Bouton photo au centre, entre la 2e et la 3e entrée -->
        <template v-if="index === 1">
          <NuxtLink v-if="ajout" :to="ajout" class="bouton-photo" aria-label="Ajouter un article">
            <Icone nom="photo" />
          </NuxtLink>
          <span v-else class="bouton-photo inactif" aria-disabled="true" aria-label="Ajouter un article (bientôt)">
            <Icone nom="photo" />
          </span>
        </template>
      </template>
    </nav>
  </div>
</template>

<style scoped>
.logo {
  font-family: var(--police-titre);
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--bordeaux);
  text-decoration: none;
}

.inactif {
  opacity: 0.45;
  cursor: not-allowed;
}

/* ---------- Mobile ---------- */

.menu-lateral {
  display: none;
}

.entete-mobile {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 1rem;
  background: var(--fond);
  border-bottom: 1px solid var(--bordure);
}

.bouton-icone {
  display: grid;
  place-items: center;
  width: var(--zone-tactile);
  height: var(--zone-tactile);
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: var(--texte);
  cursor: pointer;
}

.contenu {
  padding: 1.25rem 1rem calc(var(--hauteur-barre) + 1.5rem);
  max-width: 960px;
}

.barre-bas {
  position: fixed;
  inset: auto 0 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-around;
  height: var(--hauteur-barre);
  padding-bottom: env(safe-area-inset-bottom);
  background: var(--surface);
  border-top: 1px solid var(--bordure);
}

.onglet {
  display: grid;
  justify-items: center;
  gap: 2px;
  min-width: var(--zone-tactile);
  min-height: var(--zone-tactile);
  color: var(--texte-doux);
  font-size: 0.7rem;
  font-weight: 600;
  text-decoration: none;
}

.onglet.router-link-exact-active {
  color: var(--bordeaux);
}

.bouton-photo {
  display: grid;
  place-items: center;
  width: 58px;
  height: 58px;
  margin-top: -28px;
  border-radius: 50%;
  background: var(--bordeaux);
  color: #fff;
  box-shadow: 0 6px 16px rgb(110 26 43 / 0.35);
}

.bouton-photo.inactif {
  opacity: 0.7;
}

/* ---------- Desktop ---------- */

@media (min-width: 1024px) {
  .entete-mobile,
  .barre-bas {
    display: none;
  }

  .menu-lateral {
    position: fixed;
    inset: 0 auto 0 0;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    width: var(--largeur-menu);
    padding: 1.5rem 1rem;
    background: var(--surface);
    border-right: 1px solid var(--bordure);
  }

  .menu-lateral nav {
    display: grid;
    gap: 0.25rem;
  }

  .lien-menu {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-height: var(--zone-tactile);
    padding: 0 0.75rem;
    border: 0;
    border-radius: var(--rayon);
    background: transparent;
    color: var(--texte);
    font: 500 1rem var(--police-texte);
    text-decoration: none;
    cursor: pointer;
  }

  .lien-menu.router-link-exact-active {
    background: var(--bordeaux-clair);
    color: var(--bordeaux);
    font-weight: 700;
  }

  .compte {
    margin-top: auto;
    display: grid;
    gap: 0.25rem;
  }

  .pseudo {
    padding: 0 0.75rem;
    color: var(--texte-doux);
    font-size: 0.9rem;
  }

  .contenu {
    margin-left: var(--largeur-menu);
    padding: 2.5rem 3rem;
  }
}
</style>
