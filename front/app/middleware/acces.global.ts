/**
 * Accès aux pages, appliqué à toutes les routes.
 * Comme côté API (D-014), une page est réservée aux personnes connectées sauf si elle dit le contraire :
 * - `acces: 'public'` : visible par tout le monde (profils publics, liens de partage, à venir) ;
 * - `acces: 'invite'` : connexion et inscription, inutiles une fois connecté.
 *
 * Ce n'est qu'un confort d'affichage : la vraie protection reste l'API, qui refuse (401) sans jeton.
 */
export default defineNuxtRouteMiddleware(async (vers) => {
  const acces = vers.meta.acces
  if (acces === 'public') {
    return
  }

  const moi = await useSession().charger()

  if (acces === 'invite') {
    return moi ? navigateTo('/') : undefined
  }
  if (!moi) {
    return navigateTo({ path: '/connexion', query: vers.fullPath === '/' ? {} : { retour: vers.fullPath } })
  }
})

declare module '#app' {
  interface PageMeta {
    acces?: 'public' | 'invite'
  }
}
