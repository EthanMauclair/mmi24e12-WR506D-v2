<script setup lang="ts">
/**
 * Mise en page de la connexion et de l'inscription (écrans 1 et 2).
 * Desktop : écran coupé en deux, panneau bordeaux à gauche (promesse et étapes), formulaire à droite.
 * Mobile : le panneau se réduit à un bandeau au-dessus du formulaire.
 */
</script>

<template>
  <div class="accueil">
    <section class="panneau">
      <p class="logo">Penderie</p>
      <p class="promesse">Tout ce que vous possédez, rangé et retrouvé en quelques secondes.</p>
      <ol class="etapes">
        <li>Créez votre compte</li>
        <li>Décrivez votre logement, ses pièces et ses rangements</li>
        <li>Photographiez votre premier article</li>
      </ol>
    </section>

    <main class="formulaire-zone">
      <div class="formulaire-contenu">
        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped>
.accueil {
  min-height: 100vh;
  min-height: 100dvh;
}

.panneau {
  padding: 2rem 1.5rem 1.5rem;
  background: var(--bordeaux);
  color: #fff;
}

.logo {
  margin: 0 0 0.5rem;
  font-family: var(--police-titre);
  font-size: 2.25rem;
  font-weight: 600;
}

.promesse {
  margin: 0;
  font-size: 1rem;
  opacity: 0.9;
}

.etapes {
  display: none;
}

.formulaire-zone {
  padding: 2rem 1.25rem 3rem;
}

.formulaire-contenu {
  width: 100%;
  max-width: 420px;
  margin: 0 auto;
}

@media (min-width: 1024px) {
  .accueil {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  .panneau {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 4rem;
  }

  .logo {
    font-size: 3.5rem;
  }

  .promesse {
    max-width: 26ch;
    font-family: var(--police-titre);
    font-size: 1.9rem;
    line-height: 1.25;
  }

  .etapes {
    display: grid;
    gap: 0.75rem;
    margin: 3rem 0 0;
    padding-left: 1.25rem;
    font-size: 1rem;
    opacity: 0.9;
  }

  .formulaire-zone {
    display: grid;
    place-items: center;
    padding: 4rem;
  }
}
</style>
