<script setup lang="ts">
/**
 * Accueil de la personne connectée. Pour l'instant, il prouve la chaîne complète :
 * cookie BEARER → rendu serveur → GET /api/moi → affichage.
 * Il accueillera ensuite les logements (prochaine étape).
 */
useHead({ title: 'Accueil – Penderie' })

const { moi } = useSession()
</script>

<template>
  <div v-if="moi">
    <h1>Bonjour {{ moi.pseudo }}</h1>
    <p class="aide">Votre penderie est prête à être remplie.</p>

    <section class="carte">
      <h2>Mon compte</h2>
      <dl>
        <dt>Pseudo</dt>
        <dd>@{{ moi.pseudo }}</dd>
        <dt>E-mail</dt>
        <dd>{{ moi.email }}</dd>
        <dt>Visibilité</dt>
        <dd>
          <span class="badge">{{ moi.estPrive ? 'Privé' : 'Public' }}</span>
        </dd>
      </dl>
    </section>
  </div>
</template>

<style scoped>
.aide {
  margin-bottom: 1.5rem;
}

dl {
  display: grid;
  grid-template-columns: max-content 1fr;
  gap: 0.5rem 1.5rem;
  margin: 1rem 0 0;
}

dt {
  color: var(--texte-doux);
}

dd {
  margin: 0;
  overflow-wrap: anywhere;
}

.badge {
  display: inline-block;
  padding: 0.1rem 0.6rem;
  border-radius: 999px;
  background: var(--bordeaux-clair);
  color: var(--bordeaux);
  font-size: 0.85rem;
  font-weight: 700;
}
</style>
