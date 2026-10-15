<script setup lang="ts">
import type { MonCompte } from '~/composables/useSession'

/**
 * Paramètres du compte : informations personnelles (visibles par moi seul, S-05),
 * visibilité du compte (F-02) et mot de passe, via PATCH /api/moi ; déconnexion.
 */
useHead({ title: 'Paramètres – Penderie' })

const api = useApi()
const { moi, deconnecter } = useSession()
const compte = moi.value!

const formulaire = reactive({
  email: compte.email,
  estPrive: compte.estPrive,
  motDePasse: '',
})
const envoi = ref(false)
const succes = ref<string | null>(null)
const erreurGenerale = ref<string | null>(null)
const erreurs = ref<Record<string, string>>({})

const dateNaissance = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeZone: 'UTC' })
  .format(new Date(compte.dateNaissance))

/** Seuls les champs modifiés sont envoyés : un champ absent n'est pas touché par l'API. */
function modifications(): Record<string, unknown> {
  const corps: Record<string, unknown> = {}
  if (formulaire.email.trim() !== moi.value!.email) {
    corps.email = formulaire.email.trim()
  }
  if (formulaire.estPrive !== moi.value!.estPrive) {
    corps.estPrive = formulaire.estPrive
  }
  if (formulaire.motDePasse) {
    corps.motDePasse = formulaire.motDePasse
  }

  return corps
}

async function enregistrer() {
  succes.value = null
  erreurGenerale.value = null
  erreurs.value = {}

  const corps = modifications()
  if (Object.keys(corps).length === 0) {
    succes.value = 'Aucune modification à enregistrer.'
    return
  }
  if (corps.motDePasse !== undefined && formulaire.motDePasse.length < 12) {
    erreurs.value = { motDePasse: 'Au moins 12 caractères.' }
    return
  }

  envoi.value = true
  try {
    moi.value = await api<MonCompte>('/api/moi', { method: 'PATCH', body: corps })
    formulaire.motDePasse = ''
    succes.value = 'Vos modifications sont enregistrées.'
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

async function seDeconnecter() {
  await deconnecter()
  await navigateTo('/connexion')
}
</script>

<template>
  <div class="parametres">
    <NuxtLink to="/profil" class="retour">
      <Icone nom="retour" :taille="20" :epaisseur="1.8" /> Mon profil
    </NuxtLink>
    <h1>Paramètres du compte</h1>

    <form class="formulaire" novalidate @submit.prevent="enregistrer">
      <p v-if="erreurGenerale" class="alerte" role="alert">{{ erreurGenerale }}</p>
      <p v-if="succes" class="confirmation" role="status">{{ succes }}</p>

      <section class="carte">
        <h2>Mes informations</h2>
        <p class="aide">Visibles par vous seul : les autres ne voient que votre pseudo.</p>

        <dl class="infos">
          <div>
            <dt>Pseudo</dt>
            <dd>@{{ compte.pseudo }}</dd>
          </div>
          <div>
            <dt>Date de naissance</dt>
            <dd>{{ dateNaissance }}</dd>
          </div>
        </dl>

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
      </section>

      <section class="carte">
        <fieldset class="visibilite">
          <legend><h2>Qui peut voir vos articles ?</h2></legend>
          <div class="choix-liste">
            <label class="choix" :class="{ actif: !formulaire.estPrive }">
              <span class="choix-titre">
                <input v-model="formulaire.estPrive" type="radio" name="estPrive" :value="false" :disabled="compte.estMineur"> Public
              </span>
              <span class="choix-texte">Tout le monde, abonnement direct</span>
            </label>
            <label class="choix" :class="{ actif: formulaire.estPrive }">
              <span class="choix-titre">
                <input v-model="formulaire.estPrive" type="radio" name="estPrive" :value="true"> Privé
              </span>
              <span class="choix-texte">Vous acceptez chaque abonné</span>
            </label>
          </div>
          <p v-if="compte.estMineur" class="aide">Un compte mineur reste toujours privé.</p>
          <p v-else class="aide">En passant public, les demandes d'abonnement en attente sont acceptées.</p>
        </fieldset>
      </section>

      <section class="carte">
        <h2>Mot de passe</h2>
        <div class="champ">
          <label for="mot-de-passe">Nouveau mot de passe</label>
          <ChampMotDePasse
            id="mot-de-passe"
            v-model="formulaire.motDePasse"
            name="motDePasse"
            autocomplete="new-password"
            :aria-invalid="!!erreurs.motDePasse"
            aria-describedby="mot-de-passe-aide"
          />
          <span v-if="erreurs.motDePasse" id="mot-de-passe-aide" class="message-champ">{{ erreurs.motDePasse }}</span>
          <span v-else id="mot-de-passe-aide" class="aide">12 caractères minimum. Laissez vide pour garder l'actuel.</span>
        </div>
      </section>

      <button type="submit" class="bouton bouton-principal bouton-large" :disabled="envoi">
        {{ envoi ? 'Enregistrement…' : 'Enregistrer' }}
      </button>
    </form>

    <button type="button" class="bouton bouton-secondaire bouton-large deconnexion" @click="seDeconnecter">
      <Icone nom="deconnexion" :taille="20" /> Se déconnecter
    </button>
  </div>
</template>

<style scoped>
.parametres {
  max-width: 560px;
}

.retour {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: var(--zone-tactile);
  margin-left: -6px;
  color: var(--texte-doux);
  font-weight: 700;
  text-decoration: none;
}

h1 {
  margin: 4px 0 20px;
  color: var(--bordeaux);
}

h2 {
  margin: 0 0 4px;
  font-size: 17px;
}

.carte {
  display: grid;
  gap: 14px;
}

.carte > .aide {
  margin: -10px 0 0;
}

.infos {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin: 0;
}

.infos dt {
  color: var(--texte-doux);
  font-size: 13px;
}

.infos dd {
  margin: 0;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.visibilite {
  display: grid;
  gap: 10px;
  margin: 0;
  padding: 0;
  border: 0;
}

.visibilite legend {
  padding: 0;
  margin-bottom: 10px;
}

.visibilite .aide {
  margin: 0;
}

.choix-liste {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.choix {
  display: grid;
  gap: 4px;
  padding: 13px;
  border: 1px solid var(--bordure);
  border-radius: var(--rayon);
  background: var(--surface);
  cursor: pointer;
}

.choix.actif {
  padding: 12px;
  border: 2px solid var(--bordeaux);
  background: var(--bordeaux-clair);
}

.choix-titre {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 700;
}

.choix-titre input {
  margin: 0;
  accent-color: var(--bordeaux);
}

.choix-texte {
  color: var(--texte-doux);
  font-size: 12px;
  line-height: 1.4;
}

.confirmation {
  padding: 0.75rem 1rem;
  border-radius: var(--rayon);
  background: #e7f2ea;
  color: #1f5130;
}

.deconnexion {
  margin-top: 28px;
  color: var(--erreur);
}
</style>
