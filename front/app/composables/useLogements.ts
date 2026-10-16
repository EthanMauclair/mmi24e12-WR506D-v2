/** Un logement avec ses pièces et leurs rangements (GET /api/logements/{id}). */
export interface LogementDetaille {
  id: number
  nom: string
  pieces?: { id: number, nom: string, stockages: { id: number, nom: string }[] }[]
}

/** Un article sous sa forme membre (docs/08-contrat-api.md, § 2) : emplacement, note et `estPersonnel` compris. */
export interface ArticleMembre {
  id: number
  photoUrl: string
  categorie: { id: number, nom: string }
  marque?: string
  couleur?: string
  note?: string
  estPersonnel?: boolean
  auteur: { pseudo: string }
  emplacement?: {
    logement: { id: number, nom: string }
    piece: { id: number, nom: string }
    stockage: { id: number, nom: string }
  }
}

/**
 * Mes logements, chacun avec ses pièces et ses rangements : filtres de la recherche et choix
 * d'un rangement (déplacer un article). Même clé que la page d'ajout : les données sont partagées.
 */
export function useLogements() {
  const api = useApi()

  return useAsyncData('logements-rangements', async () => {
    const liste = await api<LogementDetaille[]>('/api/logements')
    return Promise.all(liste.map(l => api<LogementDetaille>(`/api/logements/${l.id}`)))
  })
}

/** Les catégories, liste fixe (D-021). */
export function useCategories() {
  const api = useApi()

  return useAsyncData('categories', () => api<{ id: number, nom: string }[]>('/api/categories'))
}
