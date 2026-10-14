<script setup lang="ts">
/** Écran 1 – Connexion par e-mail ou pseudo (F-01, P1). */
definePageMeta({ layout: 'accueil', acces: 'invite' })
useHead({ title: 'Connexion – Penderie' })

const route = useRoute()
const { connecter } = useSession()

const identifiant = ref('')
const motDePasse = ref('')
const envoi = ref(false)
const erreur = ref<string | null>(null)

/** Page demandée avant la connexion. Seul un chemin interne est accepté (pas de redirection vers un autre site). */
function destination(): string {
  const retour = route.query.retour
  return typeof retour === 'string' && retour.startsWith('/') && !retour.startsWith('//') ? retour : '/'
}

async function seConnecter() {
  envoi.value = true
  erreur.value = null
  try {
    await connecter(identifiant.value.trim(), motDePasse.value)
    await navigateTo(destination())
  }
  catch (e) {
    // 401 : on ne dit pas lequel des deux est faux, pour ne pas révéler qu'un compte existe.
    erreur.value = statutErreur(e) === 401
      ? 'Identifiant ou mot de passe incorrect.'
      : lireErreur(e).general
  }
  finally {
    envoi.value = false
  }
}
</script>

<template>
  <h1>Bon retour</h1>
  <p class="aide">Connectez-vous pour retrouver vos affaires.</p>

  <form class="formulaire" novalidate @submit.prevent="seConnecter">
    <p v-if="erreur" class="alerte" role="alert">{{ erreur }}</p>

    <div class="champ">
      <label for="identifiant">E-mail ou pseudo</label>
      <input
        id="identifiant"
        v-model="identifiant"
        name="identifiant"
        autocomplete="username"
        autocapitalize="none"
        spellcheck="false"
        required
      >
    </div>

    <div class="champ">
      <label for="mot-de-passe">Mot de passe</label>
      <input
        id="mot-de-passe"
        v-model="motDePasse"
        type="password"
        name="motDePasse"
        autocomplete="current-password"
        required
      >
    </div>

    <button type="submit" class="bouton bouton-principal bouton-large" :disabled="envoi || !identifiant || !motDePasse">
      {{ envoi ? 'Connexion…' : 'Se connecter' }}
    </button>
  </form>

  <p class="bascule">
    Pas encore de compte ?
    <NuxtLink to="/inscription">Créer un compte</NuxtLink>
  </p>
</template>

<style scoped>
h1 + .aide {
  margin-bottom: 1.75rem;
}

.bascule {
  margin-top: 1.75rem;
  text-align: center;
}
</style>
