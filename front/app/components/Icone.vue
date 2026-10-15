<script setup lang="ts">
/** Icônes au trait, reprises des maquettes (aucune bibliothèque chargée pour quelques pictogrammes). */
export type NomIcone = 'cintre' | 'logement' | 'recherche' | 'photo' | 'tenue' | 'profil' | 'retour' | 'deconnexion'
  | 'oeil' | 'oeil-barre' | 'reglages'

withDefaults(defineProps<{ nom: NomIcone, taille?: number, epaisseur?: number }>(), {
  taille: 24,
  epaisseur: 1.7,
})

const traces: Record<NomIcone, string[]> = {
  cintre: ['M12 6a2 2 0 1 1 2 2c-1 0-2 .7-2 1.6V10', 'M12 10 3.5 16.2A1 1 0 0 0 4.1 18h15.8a1 1 0 0 0 .6-1.8L12 10Z'],
  logement: ['M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z'],
  recherche: ['M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14Z', 'm20 20-3.5-3.5'],
  photo: ['M4 8h3l2-3h6l2 3h3v11H4Z', 'M12 9.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z'],
  tenue: ['M8 3 4 6l2 4 2-1v12h8V9l2 1 2-4-4-3c0 1.7-1.8 3-4 3S8 4.7 8 3Z'],
  profil: ['M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z', 'M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5'],
  retour: ['M15 18l-6-6 6-6'],
  deconnexion: ['M10 5H5v14h5', 'M14 8l4 4-4 4', 'M18 12H9'],
  oeil: ['M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z', 'M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z'],
  'oeil-barre': ['M10.7 5.1A10.6 10.6 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.2 3.2', 'M6.6 6.6A17.4 17.4 0 0 0 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6', 'M9.9 9.9a3 3 0 0 0 4.2 4.2', 'm2 2 20 20'],
  reglages: ['M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z', 'M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3 1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8 1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z'],
}
</script>

<template>
  <svg
    viewBox="0 0 24 24"
    :width="taille"
    :height="taille"
    fill="none"
    stroke="currentColor"
    :stroke-width="epaisseur"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path v-for="trace in traces[nom]" :key="trace" :d="trace" />
  </svg>
</template>
