<script setup lang="ts">
/** Icônes au trait, dessinées pour PENDERIE (aucune bibliothèque chargée pour quelques pictogrammes). */
const props = defineProps<{
  nom: 'accueil' | 'recherche' | 'photo' | 'logement' | 'profil' | 'deconnexion'
}>()

const traces: Record<typeof props.nom, string[]> = {
  accueil: ['M3 11 12 4l9 7', 'M5 10v10h14V10'],
  recherche: ['M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14Z', 'm20 20-4-4'],
  photo: ['M4 8h3l2-3h6l2 3h3v11H4Z', 'M12 10a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z'],
  logement: ['M4 20V8l8-4 8 4v12', 'M9 20v-6h6v6', 'M4 20h16'],
  profil: ['M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z', 'M4 20c1.5-4 4.5-6 8-6s6.5 2 8 6'],
  deconnexion: ['M10 5H5v14h5', 'M14 8l4 4-4 4', 'M18 12H9'],
}
</script>

<template>
  <svg
    viewBox="0 0 24 24"
    width="24"
    height="24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path v-for="trace in traces[nom]" :key="trace" :d="trace" />
  </svg>
</template>
