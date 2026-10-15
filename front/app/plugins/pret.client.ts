/**
 * Pose data-pret sur <html> quand Vue a pris la main sur la page rendue par le serveur (hydratation).
 * Avant ce moment, les boutons ne réagissent pas encore : les tests Cypress l'attendent avant d'agir (D-035).
 */
export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.hook('app:suspense:resolve', () => {
    document.documentElement.dataset.pret = ''
  })
})
