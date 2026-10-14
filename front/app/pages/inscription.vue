<script setup lang="ts">
/**
 * Écran 2 – Inscription (F-01, F-02, P1).
 * Les contrôles du formulaire reprennent ceux de l'API (InscriptionEntree) pour prévenir l'erreur
 * avant l'envoi ; l'API reste seule juge et ses messages s'affichent sous le champ concerné.
 */
definePageMeta({ layout: 'accueil', acces: 'invite' })
useHead({ title: 'Inscription – Penderie' })

const { inscrire } = useSession()

const formulaire = reactive({
  pseudo: '',
  email: '',
  motDePasse: '',
  dateNaissance: '',
  estPrive: false,
})
const envoi = ref(false)
const erreurGenerale = ref<string | null>(null)
const erreurs = ref<Record<string, string>>({})

// Une inscription classique est réservée aux majeurs (I-01) : on borne la date au jour des 18 ans.
// Format AAAA-MM-JJ en heure locale (toISOString passerait en UTC et décalerait d'un jour).
const aujourdhui = new Date()
const dateMax = [
  aujourdhui.getFullYear() - 18,
  String(aujourdhui.getMonth() + 1).padStart(2, '0'),
  String(aujourdhui.getDate()).padStart(2, '0'),
].join('-')

function verifier(): boolean {
  const e: Record<string, string> = {}
  if (!/^[a-z0-9_.]{3,30}$/.test(formulaire.pseudo)) {
    e.pseudo = 'De 3 à 30 caractères : lettres minuscules, chiffres, « _ » et « . ».'
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formulaire.email)) {
    e.email = 'Adresse e-mail invalide.'
  }
  if (formulaire.motDePasse.length < 12) {
    e.motDePasse = 'Au moins 12 caractères.'
  }
  if (!formulaire.dateNaissance) {
    e.dateNaissance = 'Indiquez votre date de naissance.'
  }
  else if (formulaire.dateNaissance > dateMax) {
    e.dateNaissance = 'Il faut avoir 18 ans pour créer un compte.'
  }
  erreurs.value = e

  return Object.keys(e).length === 0
}

async function sInscrire() {
  erreurGenerale.value = null
  if (!verifier()) {
    return
  }
  envoi.value = true
  try {
    await inscrire({ ...formulaire, email: formulaire.email.trim() })
    // D-010 : la création guidée du logement suit l'inscription (écran 3, prochaine étape).
    await navigateTo('/')
  }
  catch (e) {
    const { general, champs } = lireErreur(e)
    erreurGenerale.value = general
    erreurs.value = champs
  }
  finally {
    envoi.value = false
  }
}
</script>

<template>
  <h1>Créer un compte</h1>
  <p class="aide">Ensuite, on vous guide pour décrire votre logement et ses rangements.</p>

  <form class="formulaire" novalidate @submit.prevent="sInscrire">
    <p v-if="erreurGenerale" class="alerte" role="alert">{{ erreurGenerale }}</p>

    <div class="champ">
      <label for="pseudo">Pseudo</label>
      <input
        id="pseudo"
        v-model="formulaire.pseudo"
        name="pseudo"
        autocomplete="username"
        autocapitalize="none"
        spellcheck="false"
        maxlength="30"
        :aria-invalid="!!erreurs.pseudo"
        aria-describedby="pseudo-aide"
      >
      <span v-if="erreurs.pseudo" id="pseudo-aide" class="message-champ">{{ erreurs.pseudo }}</span>
      <span v-else id="pseudo-aide" class="aide">Seul votre pseudo est visible des autres.</span>
    </div>

    <div class="champ">
      <label for="email">E-mail</label>
      <input
        id="email"
        v-model="formulaire.email"
        type="email"
        name="email"
        autocomplete="email"
        :aria-invalid="!!erreurs.email"
        aria-describedby="email-erreur"
      >
      <span v-if="erreurs.email" id="email-erreur" class="message-champ">{{ erreurs.email }}</span>
    </div>

    <div class="champ">
      <label for="mot-de-passe">Mot de passe</label>
      <input
        id="mot-de-passe"
        v-model="formulaire.motDePasse"
        type="password"
        name="motDePasse"
        autocomplete="new-password"
        :aria-invalid="!!erreurs.motDePasse"
        aria-describedby="mot-de-passe-aide"
      >
      <span v-if="erreurs.motDePasse" id="mot-de-passe-aide" class="message-champ">{{ erreurs.motDePasse }}</span>
      <span v-else id="mot-de-passe-aide" class="aide">12 caractères minimum.</span>
    </div>

    <div class="champ">
      <label for="date-naissance">Date de naissance</label>
      <input
        id="date-naissance"
        v-model="formulaire.dateNaissance"
        type="date"
        name="dateNaissance"
        autocomplete="bday"
        :max="dateMax"
        :aria-invalid="!!erreurs.dateNaissance"
        aria-describedby="date-naissance-erreur"
      >
      <span v-if="erreurs.dateNaissance" id="date-naissance-erreur" class="message-champ">{{ erreurs.dateNaissance }}</span>
    </div>

    <fieldset class="champ confidentialite">
      <legend>Qui peut voir vos articles ?</legend>
      <label class="choix" :class="{ actif: !formulaire.estPrive }">
        <input v-model="formulaire.estPrive" type="radio" name="estPrive" :value="false">
        <span>
          <strong>Public</strong>
          <span class="aide">Tout le monde voit vos articles, sauf ceux que vous marquez personnels.</span>
        </span>
      </label>
      <label class="choix" :class="{ actif: formulaire.estPrive }">
        <input v-model="formulaire.estPrive" type="radio" name="estPrive" :value="true">
        <span>
          <strong>Privé</strong>
          <span class="aide">Seuls les abonnés et amis que vous acceptez les voient.</span>
        </span>
      </label>
      <span class="aide">Modifiable à tout moment.</span>
    </fieldset>

    <button type="submit" class="bouton bouton-principal bouton-large" :disabled="envoi">
      {{ envoi ? 'Création…' : 'Créer mon compte' }}
    </button>
  </form>

  <p class="bascule">
    Déjà un compte ?
    <NuxtLink to="/connexion">Se connecter</NuxtLink>
  </p>
</template>

<style scoped>
h1 + .aide {
  margin-bottom: 1.75rem;
}

.confidentialite {
  margin: 0;
  padding: 0;
  border: 0;
}

.confidentialite legend {
  margin-bottom: 0.35rem;
}

.choix {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  padding: 0.75rem 0.9rem;
  border: 1px solid var(--bordure);
  border-radius: var(--rayon);
  background: var(--surface);
  font-weight: 400;
  cursor: pointer;
}

.choix.actif {
  border-color: var(--bordeaux);
  background: var(--bordeaux-clair);
}

.choix input {
  min-height: auto;
  margin-top: 0.3rem;
  accent-color: var(--bordeaux);
}

.choix span {
  display: grid;
}

.bascule {
  margin-top: 1.75rem;
  text-align: center;
}
</style>
