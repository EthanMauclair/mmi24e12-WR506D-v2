import type { FetchError } from 'ofetch'

/**
 * Client de l'API PENDERIE. Le front ne passe que par l'API (07-choix-techniques, § 2).
 *
 * Le jeton de connexion est dans le cookie httpOnly BEARER (D-015) : aucun script ne peut le lire.
 * - Dans le navigateur, `credentials: 'include'` fait envoyer ce cookie à l'API (autre port, même site).
 * - Pendant le rendu serveur, Nuxt n'a pas de cookies à lui : on recopie l'en-tête Cookie
 *   reçu du navigateur, sinon chaque page protégée serait rendue comme pour un visiteur.
 */
export function useApi() {
  const config = useRuntimeConfig()
  const cookies = import.meta.server ? useRequestHeaders(['cookie']) : {}

  return $fetch.create({
    baseURL: import.meta.server ? config.apiBaseServeur : config.public.apiBase,
    credentials: 'include',
    // Sans cet en-tête, Symfony renvoie ses erreurs en HTML.
    headers: { Accept: 'application/json', ...cookies },
  })
}

/** Statut HTTP d'une erreur de l'API, ou null si l'API n'a pas répondu. */
export function statutErreur(erreur: unknown): number | null {
  return (erreur as FetchError)?.response?.status ?? null
}

interface Violation {
  propertyPath: string
  title: string
}

/**
 * Traduit une erreur de l'API en messages affichables :
 * - `champs` : erreurs de validation (422), une par champ du formulaire ;
 * - `general` : le reste (409, 401, API injoignable…).
 */
export function lireErreur(erreur: unknown): { general: string | null, champs: Record<string, string> } {
  const statut = statutErreur(erreur)
  if (statut === null) {
    return { general: 'Le serveur ne répond pas. Réessayez dans un instant.', champs: {} }
  }

  const donnees = (erreur as FetchError).data ?? {}
  const champs: Record<string, string> = {}
  for (const violation of (donnees.violations ?? []) as Violation[]) {
    // On garde le premier message de chaque champ.
    champs[violation.propertyPath] ??= violation.title
  }
  if (Object.keys(champs).length > 0) {
    return { general: null, champs }
  }

  // Les règles métier arrivent préfixées de leur code (« [I-10] Videz d'abord… ») : utile dans Postman, pas à l'écran.
  const message: string = donnees.detail ?? donnees.message ?? 'Une erreur est survenue.'
  return { general: message.replace(/^\[[A-Z0-9-]+\]\s*/, ''), champs }
}
