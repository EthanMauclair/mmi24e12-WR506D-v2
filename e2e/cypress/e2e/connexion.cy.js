/** P1 : accès à l'application, connexion (F-01) et protection des pages (D-025). */
describe('Connexion', () => {
  it('renvoie vers la connexion une personne qui n\'est pas connectée, puis la ramène où elle allait', () => {
    cy.visiter('/logements')
    cy.location('pathname').should('eq', '/connexion')
    cy.location('search').should('contain', 'retour=/logements')

    cy.get('#identifiant').type('camille')
    cy.get('#mot-de-passe').type(Cypress.env('motDePasse'))
    cy.contains('button', 'Se connecter').click()

    cy.location('pathname').should('eq', '/logements')
    cy.contains('h1', 'Mes logements')
  })

  it('refuse un mauvais mot de passe sans dire si le compte existe', () => {
    cy.visiter('/connexion')
    cy.get('#identifiant').type('camille')
    cy.get('#mot-de-passe').type('mauvais-mot-de-passe')
    cy.contains('button', 'Se connecter').click()

    cy.get('[role="alert"]').should('contain', 'Identifiant ou mot de passe incorrect.')
    cy.location('pathname').should('eq', '/connexion')
  })

  it('affiche le mot de passe en clair à la demande (D-026)', () => {
    cy.visiter('/connexion')
    cy.get('#mot-de-passe').type('secret').should('have.attr', 'type', 'password')

    cy.get('button[aria-label="Afficher le mot de passe"]').click()

    cy.get('#mot-de-passe').should('have.attr', 'type', 'text')
    cy.get('button[aria-label="Masquer le mot de passe"]').should('have.attr', 'aria-pressed', 'true')
  })

  it('mène au profil après la connexion, sans afficher l\'e-mail (D-028)', () => {
    cy.seConnecter('camille')
    cy.visiter('/')

    cy.location('pathname').should('eq', '/profil')
    cy.contains('h1', '@camille')
    cy.get('main').should('not.contain', '@exemple.fr')
  })
})
