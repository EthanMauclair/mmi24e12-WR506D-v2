/** Schéma MonCompte du contrat (GET /api/moi). */
export interface MonCompte {
  id: number
  pseudo: string
  email: string
  dateNaissance: string
  estMineur: boolean
  estPrive: boolean
  referent: { pseudo: string } | null
}

export interface Inscription {
  pseudo: string
  email: string
  motDePasse: string
  dateNaissance: string
  estPrive: boolean
}

/**
 * Personne connectée. Le front ne peut pas lire le jeton (cookie httpOnly) :
 * la seule façon de savoir qui est connecté est de demander GET /api/moi.
 * L'état est partagé entre le rendu serveur et le navigateur (useState).
 */
export function useSession() {
  const api = useApi()
  const moi = useState<MonCompte | null>('moi', () => null)
  const verifiee = useState('session-verifiee', () => false)

  async function charger(): Promise<MonCompte | null> {
    if (verifiee.value) {
      return moi.value
    }
    try {
      moi.value = await api<MonCompte>('/api/moi')
    }
    catch (erreur) {
      // 401 : pas de jeton, ou jeton expiré. Toute autre erreur doit remonter.
      if (statutErreur(erreur) !== 401) {
        throw erreur
      }
      moi.value = null
    }
    verifiee.value = true

    return moi.value
  }

  async function connecter(identifiant: string, motDePasse: string): Promise<void> {
    // La réponse contient aussi le jeton (pour Postman) : le front l'ignore, le cookie suffit.
    await api('/api/auth/connexion', { method: 'POST', body: { identifiant, motDePasse } })
    verifiee.value = false
    await charger()
  }

  async function inscrire(inscription: Inscription): Promise<void> {
    await api('/api/auth/inscription', { method: 'POST', body: inscription })
    await connecter(inscription.pseudo, inscription.motDePasse)
  }

  async function deconnecter(): Promise<void> {
    await api('/api/auth/deconnexion', { method: 'POST' })
    moi.value = null
    verifiee.value = true
  }

  return { moi, charger, connecter, inscrire, deconnecter }
}
