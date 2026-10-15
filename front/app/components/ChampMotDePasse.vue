<script setup lang="ts">
/**
 * Champ mot de passe avec un bouton pour l'afficher en clair : on vérifie sa saisie sans la retaper,
 * utile au téléphone (critère d'accessibilité WCAG 3.3.8). Les attributs (id, autocomplete…) vont sur l'input.
 */
defineOptions({ inheritAttrs: false })

const valeur = defineModel<string>({ required: true })
const visible = ref(false)
</script>

<template>
  <div class="mot-de-passe">
    <input v-model="valeur" v-bind="$attrs" :type="visible ? 'text' : 'password'" autocapitalize="none" spellcheck="false">
    <button
      type="button"
      class="basculer"
      :aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
      :aria-pressed="visible"
      @click="visible = !visible"
    >
      <Icone :nom="visible ? 'oeil-barre' : 'oeil'" :taille="22" />
    </button>
  </div>
</template>

<style scoped>
.mot-de-passe {
  position: relative;
  display: grid;
}

.mot-de-passe input {
  width: 100%;
  padding-right: 52px;
}

.basculer {
  position: absolute;
  top: 50%;
  right: 3px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--zone-tactile);
  height: var(--zone-tactile);
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: var(--texte-doux);
  cursor: pointer;
  transform: translateY(-50%);
}

.basculer:hover {
  color: var(--bordeaux);
}
</style>
