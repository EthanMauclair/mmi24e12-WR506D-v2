<script setup lang="ts">
/** Mes logements (F-05) : chaque logement avec ses pièces et leurs rangements. */
useHead({ title: 'Mes logements – Penderie' })

interface Logement {
  id: number
  nom: string
  monRole: 'PROPRIETAIRE' | 'COPROPRIETAIRE'
  pieces?: { id: number, nom: string, stockages: { id: number, nom: string }[] }[]
}

const api = useApi()

// La liste ne contient pas les pièces : on lit le détail de chaque logement (GET /logements/{id}).
const { data: logements } = await useAsyncData('mes-logements', async () => {
  const liste = await api<Logement[]>('/api/logements')
  return Promise.all(liste.map(l => api<Logement>(`/api/logements/${l.id}`)))
})
</script>

<template>
  <div>
    <div class="titre-ligne">
      <h1>Mes logements</h1>
      <NuxtLink v-if="logements?.length" to="/logements/nouveau" class="bouton bouton-secondaire">+ Nouveau logement</NuxtLink>
    </div>

    <div v-if="!logements?.length" class="vide carte">
      <p class="vide-titre">Aucun logement pour l'instant</p>
      <p>Décrivez votre logement, ses pièces et leurs rangements pour y ranger vos articles.</p>
      <NuxtLink to="/logements/nouveau" class="bouton bouton-principal">Créer mon logement</NuxtLink>
    </div>

    <section v-for="logement in logements" :key="logement.id" class="logement carte">
      <div class="logement-entete">
        <h2>{{ logement.nom }}</h2>
        <span class="badge">{{ logement.monRole === 'PROPRIETAIRE' ? 'Propriétaire' : 'Co-propriétaire' }}</span>
      </div>

      <p v-if="!logement.pieces?.length" class="aide">Aucune pièce pour l'instant.</p>
      <ul v-else class="pieces">
        <li v-for="piece in logement.pieces" :key="piece.id" class="piece">
          <h3>{{ piece.nom }}</h3>
          <ul v-if="piece.stockages.length" class="rangements">
            <li v-for="stockage in piece.stockages" :key="stockage.id" class="puce">{{ stockage.nom }}</li>
          </ul>
          <p v-else class="aide">Aucun rangement</p>
        </li>
      </ul>
    </section>
  </div>
</template>

<style scoped>
.titre-ligne {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

h1 {
  margin: 0;
  color: var(--bordeaux);
}

.titre-ligne .bouton {
  min-height: var(--zone-tactile);
}

.logement {
  display: grid;
  gap: 14px;
  margin-bottom: 16px;
}

.logement-entete {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

h2 {
  margin: 0;
  font-size: 19px;
}

.badge {
  padding: 3px 9px;
  border-radius: 999px;
  background: var(--bordeaux-clair);
  color: var(--bordeaux);
  font-size: 12px;
  font-weight: 700;
}

.pieces {
  display: grid;
  gap: 12px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.piece {
  display: grid;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid var(--ligne);
}

h3 {
  margin: 0;
  font-size: 15px;
}

.rangements {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.puce {
  padding: 6px 11px;
  border-radius: 999px;
  background: var(--bordeaux-clair);
  color: var(--bordeaux);
  font-size: 13px;
  font-weight: 700;
}

.aide {
  margin: 0;
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

@media (min-width: 1024px) {
  .pieces {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .piece {
    padding: 14px;
    border: 1px solid var(--ligne);
    border-radius: var(--rayon);
  }
}
</style>
