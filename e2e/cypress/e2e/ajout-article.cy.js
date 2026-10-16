/** P2 : Camille ajoute un article en le prenant en photo, puis le retrouve dans sa pièce (P3). */
describe('Ajout d\'un article par photo', () => {
  beforeEach(() => cy.seConnecter('camille'))

  it('envoie une photo, remplit la fiche, et retrouve l\'article dans sa pièce', () => {
    const marque = `Cypress ${Date.now()}`

    cy.visiter('/ajout')
    // Sur ordinateur : choix d'un fichier (le champ est caché derrière le bouton « Choisir un fichier »).
    cy.get('input[type="file"][accept="image/jpeg,image/png,image/webp"]').selectFile('cypress/fixtures/pull.jpg', { force: true })

    cy.contains('h1', 'Nouvel article')
    cy.get('img[alt="Photo de l\'article"]').should('be.visible')
    cy.get('#categorie').select('Pull')
    cy.get('#marque').type(marque)
    cy.get('#couleur').type('Bordeaux')
    cy.get('#rangement').select('Appartement Lyon › Chambre › Grande armoire')
    cy.contains('button', 'Enregistrer l\'article').click()

    cy.location('pathname').should('eq', '/profil')

    // Le plus récent apparaît en premier dans la pièce et dans son rangement.
    cy.visiter('/logements')
    cy.contains('section', 'Appartement Lyon').contains('a', 'Chambre').click()
    cy.contains('h1', 'Chambre')
    cy.contains('button', 'Grande armoire').click()
    cy.location('search').should('match', /rangement=\d+/)
    cy.get('[data-cy=articles] li').first().should('contain', marque)
  })

  it('exige une catégorie et un rangement', () => {
    cy.visiter('/ajout')
    cy.get('input[type="file"][accept="image/jpeg,image/png,image/webp"]').selectFile('cypress/fixtures/pull.jpg', { force: true })

    cy.contains('button', 'Enregistrer l\'article').click()

    cy.contains('Choisissez une catégorie.')
    cy.contains('Choisissez où ranger l\'article.')
    cy.location('pathname').should('eq', '/ajout')
  })
})
