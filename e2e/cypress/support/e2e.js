/**
 * Visite une page puis attend que Vue ait pris la main sur la page rendue par le serveur (data-pret, D-035) :
 * avant, un clic n'est pas encore pris en charge et le test échouerait au hasard.
 * (Une surcharge de cy.visit ferait la même chose, mais Cypress s'y bloque : d'où une commande à part.)
 */
Cypress.Commands.add('visiter', (adresse, options = {}) => {
  cy.visit(adresse, options)
  cy.get('html[data-pret]', { timeout: 60000, log: false })
})

/**
 * Connexion par l'API, sans passer par l'écran : l'API pose le cookie httpOnly BEARER (D-015),
 * que le navigateur de Cypress renvoie ensuite au front. cy.session garde la connexion entre les tests.
 */
Cypress.Commands.add('seConnecter', (identifiant) => {
  cy.session(identifiant, () => {
    cy.request({
      method: 'POST',
      url: `${Cypress.env('api')}/api/auth/connexion`,
      headers: { Accept: 'application/json' },
      body: { identifiant, motDePasse: Cypress.env('motDePasse') },
    })
  })
})
