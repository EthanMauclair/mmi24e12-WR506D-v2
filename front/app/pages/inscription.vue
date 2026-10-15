<script setup lang="ts">
/**
 * Écran 2 – Inscription (F-01, F-02, P1).
 * Les contrôles du formulaire reprennent ceux de l'API (InscriptionEntree) pour prévenir l'erreur
 * avant l'envoi ; l'API reste seule juge et ses messages s'affichent sous le champ concerné.
 */
definePageMeta({ layout: false, acces: 'invite' })
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
  <EcranAccueil>
    <template #panneau>
      <span class="marque-nom">Penderie</span>
      <div class="etapes-bloc">
        <p class="etapes-titre">En trois étapes, votre dressing est en ligne.</p>
        <ol class="etapes">
          <li><span class="pastille pastille-active">1</span>Créer votre compte</li>
          <li><span class="pastille">2</span>Créer votre logement et ses pièces</li>
          <li><span class="pastille">3</span>Photographier un premier article</li>
        </ol>
      </div>
      <p class="panneau-pied">
        Déjà inscrit ? <NuxtLink to="/connexion">Se connecter</NuxtLink>
      </p>
    </template>

    <div class="contenu">
      <NuxtLink to="/connexion" class="retour" aria-label="Retour à la connexion">
        <Icone nom="retour" :taille="22" :epaisseur="1.8" />
      </NuxtLink>

      <h1>Créer votre compte</h1>
      <p class="sous-titre">Ensuite, on crée ensemble votre premier logement.</p>

      <form class="formulaire" novalidate @submit.prevent="sInscrire">
        <p v-if="erreurGenerale" class="alerte" role="alert">{{ erreurGenerale }}</p>

        <div class="paire">
          <div class="champ">
            <label for="pseudo">Pseudo</label>
            <input
              id="pseudo"
              v-model="formulaire.pseudo"
              name="pseudo"
              placeholder="camille.dressing"
              autocomplete="username"
              autocapitalize="none"
              spellcheck="false"
              maxlength="30"
              :aria-invalid="!!erreurs.pseudo"
              aria-describedby="pseudo-aide"
            >
            <span v-if="erreurs.pseudo" id="pseudo-aide" class="message-champ">{{ erreurs.pseudo }}</span>
          </div>

          <div class="champ champ-date">
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
        </div>

        <div class="champ">
          <label for="email">E-mail</label>
          <input
            id="email"
            v-model="formulaire.email"
            type="email"
            name="email"
            placeholder="camille@exemple.fr"
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
          <span v-else id="mot-de-passe-aide" class="aide">12 caractères minimum</span>
        </div>

        <fieldset class="champ visibilite">
          <legend>Qui peut voir vos articles ?</legend>
          <div class="choix-liste">
            <label class="choix" :class="{ actif: !formulaire.estPrive }">
              <span class="choix-titre">
                <input v-model="formulaire.estPrive" type="radio" name="estPrive" :value="false"> Public
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
        </fieldset>

        <div class="espace" />

        <button type="submit" class="bouton bouton-principal bouton-large envoyer" :disabled="envoi">
          {{ envoi ? 'Création…' : 'Créer mon compte' }}
        </button>
        <p class="note">Les articles marqués « personnel » restent toujours cachés.</p>
      </form>
    </div>
  </EcranAccueil>
</template>

<style scoped>
/* ---------- Panneau desktop ---------- */

.marque-nom {
  font-family: var(--police-titre);
  font-size: 32px;
  font-weight: 600;
}

.etapes-bloc {
  display: grid;
  gap: 28px;
  max-width: 460px;
}

.etapes-titre {
  margin: 0;
  font-family: var(--police-titre);
  font-size: 56px;
  font-weight: 600;
  line-height: 1.02;
}

.etapes {
  display: grid;
  gap: 16px;
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 17px;
}

.etapes li {
  display: flex;
  align-items: center;
  gap: 14px;
}

.pastille {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid #f2dde2;
  border-radius: 50%;
  font-weight: 700;
}

.pastille-active {
  border: 0;
  background: #fff;
  color: var(--bordeaux);
}

.panneau-pied {
  margin: 0;
  font-size: 14px;
  color: #e8c9d0;
}

.panneau-pied a {
  color: #fff;
  font-weight: 700;
}

/* ---------- Formulaire ---------- */

.contenu {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  width: 100%;
  max-width: 440px;
  margin: 0 auto;
}

.contenu > .formulaire {
  flex-grow: 1;
  align-content: start;
  gap: 14px;
}

.retour {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  margin-left: -10px;
  color: var(--texte);
}

h1 {
  margin: 12px 0 6px;
  font-size: 36px;
  line-height: 1.05;
  color: var(--bordeaux);
}

.sous-titre {
  margin: 0 0 24px;
  font-size: 14px;
  color: var(--texte-doux);
}

.paire {
  display: grid;
  gap: 14px;
}

.champ input {
  height: 48px;
}

.visibilite {
  margin: 4px 0 0;
  padding: 0;
  border: 0;
}

.visibilite legend {
  margin-bottom: 8px;
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
  font-size: 12px;
  line-height: 1.4;
  color: var(--texte-doux);
  font-weight: 400;
}

.espace {
  flex-grow: 1;
}

.note {
  margin: 0;
  font-size: 12px;
  text-align: center;
  color: var(--texte-aide);
}

/* Mobile : ordre de la maquette (pseudo, e-mail, mot de passe, date). La paire pseudo / date,
   côte à côte sur desktop, est « dépliée » pour que la date passe après le mot de passe. */
@media (max-width: 1023px) {
  .paire {
    display: contents;
  }

  .champ-date {
    order: 1;
  }

  .visibilite,
  .espace,
  .envoyer,
  .note {
    order: 2;
  }
}

@media (min-width: 1024px) {
  .retour,
  .sous-titre,
  .espace {
    display: none;
  }

  .contenu {
    flex-grow: 0;
  }

  h1 {
    margin: 0 0 6px;
    font-size: 40px;
  }

  .paire {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .choix-liste {
    gap: 12px;
  }

  .choix {
    padding: 15px;
  }

  .choix.actif {
    padding: 14px;
  }

  .envoyer {
    margin-top: 8px;
  }
}
</style>
