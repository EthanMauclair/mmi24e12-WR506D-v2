/**
 * P1 : Camille crée son compte, puis l'application la guide pour décrire son logement (D-010).
 * Le test crée un compte à chaque passage (pseudo unique) : make fixtures remet la base à zéro.
 */
describe('Inscription puis création guidée du logement', () => {
  it('crée un compte, un logement, une pièce et un rangement', () => {
    const pseudo = `e2e${Date.now()}`

    cy.visiter('/inscription')
    cy.get('#pseudo').type(pseudo)
    cy.get('#email').type(`${pseudo}@exemple.fr`)
    cy.get('#mot-de-passe').type('mot-de-passe-de-test')
    cy.get('#date-naissance').type('1995-05-12')
    cy.contains('label', 'Privé').click()
    cy.contains('button', 'Créer mon compte').click()

    // D-010 : l'inscription mène directement à la création du logement.
    cy.location('pathname').should('eq', '/logements/nouveau')
    cy.contains('h1', 'Votre premier logement')

    cy.get('#nom-logement').type('Appartement de test')
    cy.contains('button', '+ Chambre').click()
    cy.contains('button', '+ Rangement').click()
    cy.focused().type('Grande armoire{enter}')
    cy.get('#nouvelle-piece').type('Garage')
    cy.contains('button', /^Ajouter$/).click()
    cy.contains('button', 'Enregistrer sans ajouter d\'article').click()

    cy.location('pathname').should('eq', '/logements')
    cy.contains('h2', 'Appartement de test')
    cy.contains('a', 'Grande armoire')
    cy.contains('h3', 'Garage')
  })

  it('refuse un pseudo invalide avant même l\'envoi', () => {
    cy.visiter('/inscription')
    cy.get('#pseudo').type('Pas Valide!')
    cy.contains('button', 'Créer mon compte').click()

    cy.contains('De 3 à 30 caractères')
    cy.location('pathname').should('eq', '/inscription')
  })
})
