<script setup lang="ts">
/** Écran 1 – Connexion par e-mail ou pseudo (F-01, P1). */
definePageMeta({ layout: false, acces: 'invite' })
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
  if (!identifiant.value.trim() || !motDePasse.value) {
    erreur.value = 'Renseignez votre e-mail ou pseudo et votre mot de passe.'
    return
  }
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
  <EcranAccueil>
    <template #panneau>
      <div class="marque">
        <span class="logo-carre"><Icone nom="cintre" :taille="26" :epaisseur="1.6" /></span>
        <span class="marque-nom">Penderie</span>
      </div>
      <div class="promesse">
        <p class="promesse-titre">Tout ce que vous possédez, rangé et retrouvé.</p>
        <p class="promesse-texte">Photographiez un article, rangez-le dans le bon placard. Retrouvez-le en un instant, composez vos tenues, partagez-les.</p>
      </div>
      <p class="panneau-pied">Vos articles personnels restent toujours cachés.</p>
    </template>

    <div class="contenu">
      <!-- Mobile : la marque est au-dessus du formulaire -->
      <div class="entete-mobile">
        <span class="logo-carre"><Icone nom="cintre" :taille="30" :epaisseur="1.6" /></span>
        <h1 class="logo-titre">Penderie</h1>
        <p class="accroche">Tout ce que vous possédez, rangé et retrouvé en un instant.</p>
      </div>

      <div class="entete-desktop">
        <h1>Connexion</h1>
        <p>Heureux de vous revoir.</p>
      </div>

      <form class="formulaire" method="post" novalidate @submit.prevent="seConnecter">
        <p v-if="erreur" class="alerte" role="alert">{{ erreur }}</p>

        <div class="champ">
          <label for="identifiant">E-mail ou pseudo</label>
          <input
            id="identifiant"
            v-model="identifiant"
            name="identifiant"
            placeholder="camille@exemple.fr"
            autocomplete="username"
            autocapitalize="none"
            spellcheck="false"
            required
          >
        </div>

        <div class="champ">
          <label for="mot-de-passe">Mot de passe</label>
          <ChampMotDePasse
            id="mot-de-passe"
            v-model="motDePasse"
            name="motDePasse"
            autocomplete="current-password"
            required
          />
        </div>

        <button type="submit" class="bouton bouton-principal bouton-large envoyer" :disabled="envoi">
          {{ envoi ? 'Connexion…' : 'Se connecter' }}
        </button>
      </form>

      <div class="espace" />
      <hr class="separateur">

      <p class="bascule">
        Pas encore de compte ?
        <NuxtLink to="/inscription">Créer un compte</NuxtLink>
      </p>
    </div>
  </EcranAccueil>
</template>

<style scoped>
/* ---------- Panneau desktop ---------- */

.marque {
  display: flex;
  align-items: center;
  gap: 14px;
}

.marque .logo-carre {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgb(255 255 255 / 0.14);
}

.marque-nom {
  font-family: var(--police-titre);
  font-size: 26px;
  font-weight: 700;
}

.promesse {
  display: grid;
  gap: 20px;
  max-width: 480px;
}

.promesse-titre {
  margin: 0;
  font-family: var(--police-titre);
  font-size: 46px;
  font-weight: 700;
  line-height: 1.1;
}

.promesse-texte {
  margin: 0;
  font-size: 18px;
  line-height: 1.55;
  color: #f2dde2;
}

.panneau-pied {
  margin: 0;
  font-size: 14px;
  color: #e8c9d0;
}

/* ---------- Formulaire ---------- */

.logo-carre {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.contenu {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
  padding-top: 48px;
}

.entete-mobile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  margin-bottom: 48px;
  text-align: center;
}

.entete-mobile .logo-carre {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: var(--bordeaux);
}

.logo-titre {
  margin: 8px 0 0;
  font-family: var(--police-titre);
  font-size: 34px;
  font-weight: 700;
  line-height: 1.1;
  color: var(--bordeaux);
}

.accroche {
  margin: 0;
  max-width: 260px;
  font-size: 15px;
  color: var(--texte-doux);
}

.entete-desktop {
  display: none;
}

.envoyer {
  margin-top: 8px;
}

.espace {
  flex-grow: 1;
  min-height: 32px;
}

.separateur {
  display: none;
}

.bascule {
  margin: 0;
  font-size: 14px;
  text-align: center;
  color: var(--texte-doux);
}

.bascule a {
  font-weight: 700;
}

@media (min-width: 1024px) {
  .contenu {
    flex-grow: 0;
    gap: 28px;
    padding-top: 0;
  }

  .entete-mobile,
  .espace {
    display: none;
  }

  .entete-desktop {
    display: grid;
    gap: 8px;
  }

  .entete-desktop h1 {
    margin: 0;
    font-size: 32px;
    color: var(--bordeaux);
  }

  .entete-desktop p {
    margin: 0;
    font-size: 15px;
    color: var(--texte-doux);
  }

  .separateur {
    display: block;
    width: 100%;
    margin: 0;
    border: 0;
    border-top: 1px solid var(--ligne);
  }

  .bascule {
    text-align: left;
  }
}
</style>
