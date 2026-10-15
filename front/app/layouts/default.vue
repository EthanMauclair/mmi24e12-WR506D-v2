<script setup lang="ts">
import type { NomIcone } from '~/components/Icone.vue'

/**
 * Mise en page des écrans connectés (maquette 4, Profil) :
 * - mobile : barre du bas à cinq icônes, bouton photo au centre, accessible partout (D-010) ;
 * - desktop : menu latéral, « Ajouter un article » en tête.
 * Les entrées sans `vers` arrivent avec les prochaines branches : affichées, mais inactives.
 */
interface Entree {
  libelle: string
  icone: NomIcone
  vers: string | null
}

const entrees: Entree[] = [
  { libelle: 'Mes logements', icone: 'logement', vers: null },
  { libelle: 'Rechercher', icone: 'recherche', vers: null },
  { libelle: 'Mes tenues', icone: 'tenue', vers: null },
  { libelle: 'Mon profil', icone: 'profil', vers: '/profil' },
]
const ajout: string | null = null

const { moi } = useSession()
</script>

<template>
  <div class="application">
    <nav class="menu-lateral" aria-label="Navigation principale">
      <NuxtLink to="/" class="logo">Penderie</NuxtLink>

      <NuxtLink v-if="ajout" :to="ajout" class="ajouter">
        <Icone nom="photo" :taille="20" :epaisseur="1.8" /> Ajouter un article
      </NuxtLink>
      <span v-else class="ajouter inactif" aria-disabled="true" title="Bientôt disponible">
        <Icone nom="photo" :taille="20" :epaisseur="1.8" /> Ajouter un article
      </span>

      <template v-for="entree in entrees" :key="entree.libelle">
        <NuxtLink v-if="entree.vers" :to="entree.vers" class="lien-menu">{{ entree.libelle }}</NuxtLink>
        <span v-else class="lien-menu inactif" aria-disabled="true" title="Bientôt disponible">{{ entree.libelle }}</span>
      </template>

      <div class="compte">
        <span v-if="moi" class="pseudo">@{{ moi.pseudo }}</span>
        <NuxtLink to="/profil/parametres" class="lien-menu lien-parametres">
          <Icone nom="reglages" :taille="20" /> Paramètres
        </NuxtLink>
      </div>
    </nav>

    <header class="entete-mobile">
      <NuxtLink to="/" class="logo">Penderie</NuxtLink>
      <NuxtLink to="/profil/parametres" class="bouton-icone" aria-label="Paramètres du compte">
        <Icone nom="reglages" :taille="22" />
      </NuxtLink>
    </header>

    <main class="contenu">
      <slot />
    </main>

    <nav class="barre-bas" aria-label="Navigation principale">
      <template v-for="(entree, index) in entrees" :key="entree.libelle">
        <NuxtLink v-if="entree.vers" :to="entree.vers" class="onglet" :aria-label="entree.libelle">
          <Icone :nom="entree.icone" />
        </NuxtLink>
        <span v-else class="onglet inactif" aria-disabled="true" :aria-label="`${entree.libelle} (bientôt)`">
          <Icone :nom="entree.icone" />
        </span>

        <!-- Bouton photo au centre, entre la 2e et la 3e entrée -->
        <template v-if="index === 1">
          <NuxtLink v-if="ajout" :to="ajout" class="bouton-photo" aria-label="Ajouter un article en photo">
            <Icone nom="photo" :taille="26" :epaisseur="1.8" />
          </NuxtLink>
          <span v-else class="bouton-photo inactif" aria-disabled="true" aria-label="Ajouter un article en photo (bientôt)">
            <Icone nom="photo" :taille="26" :epaisseur="1.8" />
          </span>
        </template>
      </template>
    </nav>
  </div>
</template>

<style scoped>
.logo {
  font-family: var(--police-titre);
  font-size: 22px;
  font-weight: 700;
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
  padding: 18px 20px 8px;
  background: var(--fond);
}

.bouton-icone {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--zone-tactile);
  height: var(--zone-tactile);
  border: 0;
  background: transparent;
  color: var(--texte);
  cursor: pointer;
}

.contenu {
  padding: 8px 20px calc(var(--hauteur-barre) + 24px);
}

.barre-bas {
  position: fixed;
  inset: auto 0 0;
  z-index: 10;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  align-items: center;
  height: var(--hauteur-barre);
  padding-bottom: max(8px, env(safe-area-inset-bottom));
  background: var(--surface);
  border-top: 1px solid var(--ligne);
}

.onglet {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 48px;
  color: var(--texte-doux);
}

.onglet.router-link-active {
  color: var(--bordeaux);
}

.bouton-photo {
  display: flex;
  align-items: center;
  justify-content: center;
  justify-self: center;
  width: 56px;
  height: 56px;
  border-radius: 18px;
  background: var(--bordeaux);
  color: #fff;
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
    gap: 6px;
    width: var(--largeur-menu);
    padding: 28px 18px;
    background: var(--surface);
    border-right: 1px solid var(--ligne);
  }

  .menu-lateral .logo {
    padding: 0 12px 20px;
    font-size: 24px;
  }

  .ajouter {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    height: 48px;
    margin-bottom: 14px;
    border-radius: var(--rayon);
    background: var(--bordeaux);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
  }

  .lien-menu {
    display: flex;
    align-items: center;
    height: var(--zone-tactile);
    padding: 0 12px;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #4a3d40;
    font: 400 15px var(--police-texte);
    text-align: left;
    text-decoration: none;
    cursor: pointer;
  }

  .lien-menu.router-link-active {
    background: var(--bordeaux-clair);
    color: var(--bordeaux);
    font-weight: 700;
  }

  .lien-parametres {
    gap: 10px;
  }

  .compte {
    display: grid;
    gap: 4px;
    margin-top: auto;
  }

  .pseudo {
    padding: 0 12px;
    color: var(--texte-doux);
    font-size: 14px;
  }

  .contenu {
    margin-left: var(--largeur-menu);
    padding: 40px 56px;
  }
}
</style>
