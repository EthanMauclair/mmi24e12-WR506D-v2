<script setup lang="ts">
/**
 * Cadre des écrans Connexion et Inscription (maquettes 1 et 2).
 * Desktop : écran coupé en deux, panneau bordeaux à gauche (slot « panneau »), formulaire à droite.
 * Mobile : pas de panneau, le formulaire occupe tout l'écran.
 */
</script>

<template>
  <div class="ecran-accueil">
    <section class="panneau">
      <slot name="panneau" />
    </section>
    <main class="zone-formulaire">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.ecran-accueil {
  min-height: 100vh;
  min-height: 100dvh;
}

.panneau {
  display: none;
}

.zone-formulaire {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  min-height: 100dvh;
  padding: 24px 28px 32px;
}

@media (min-width: 1024px) {
  .ecran-accueil {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .panneau {
    position: sticky;
    top: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 72px 80px;
    background: var(--bordeaux);
    color: #fff;
  }

  .zone-formulaire {
    align-items: center;
    justify-content: center;
    padding: 56px 72px;
  }
}
</style>
