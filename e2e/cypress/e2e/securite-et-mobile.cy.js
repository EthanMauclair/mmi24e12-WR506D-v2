/** Ce qu'on ne doit pas voir (D-014) et la mise en page mobile (barre du bas, bouton photo au centre). */
describe('Sécurité', () => {
  beforeEach(() => cy.seConnecter('camille'))

  it('ne montre pas le logement d\'une autre personne, même en changeant l\'adresse', () => {
    // Le studio de Sophie existe, mais Camille n'en est pas membre : l'API répond 404, pas 403.
    cy.request({ url: `${Cypress.env('api')}/api/logements`, headers: { Accept: 'application/json' } })
      .its('body')
      .then((mesLogements) => {
        const mesIds = mesLogements.map(l => l.id)
        const autre = [...Array(60).keys()].map(i => i + 1).find(id => !mesIds.includes(id))
        cy.request({ url: `${Cypress.env('api')}/api/logements/${autre}`, headers: { Accept: 'application/json' }, failOnStatusCode: false })
          .its('status').should('eq', 404)
        cy.visiter(`/logements/${autre}/pieces/1`, { failOnStatusCode: false })
        cy.contains('Pièce introuvable')
      })
  })

  it('ne révèle pas l\'e-mail d\'une autre personne dans son profil (S-05)', () => {
    cy.request({ url: `${Cypress.env('api')}/api/utilisateurs/nora`, headers: { Accept: 'application/json' } })
      .its('body')
      .should((profil) => {
        expect(profil).to.have.property('pseudo', 'nora')
        expect(profil).not.to.have.property('email')
        expect(profil).not.to.have.property('dateNaissance')
      })
  })
})

describe('Mobile', () => {
  beforeEach(() => {
    cy.viewport('iphone-x')
    cy.seConnecter('camille')
  })

  it('affiche la barre du bas avec le bouton photo au centre, qui mène à l\'ajout', () => {
    cy.visiter('/profil')
    cy.get('.barre-bas').should('be.visible')
    cy.get('.menu-lateral').should('not.be.visible')

    cy.get('.barre-bas a[aria-label="Ajouter un article en photo"]').click()

    cy.location('pathname').should('eq', '/ajout')
    cy.contains('h1', 'Ajouter un article').should('be.visible')
  })
})
