/**
 * P2 : Camille ajoute un article en le prenant en photo, puis le retrouve dans sa pièce (P3).
 *
 * Depuis D-040, l'API fait lire la photo par un modèle de Google, ce qui prend jusqu'à 20 s et dépend
 * de sa disponibilité. Les tests interceptent donc la réponse de /api/reconnaissances : la proposition
 * est choisie par chaque test, et le résultat ne dépend ni du réseau ni du quota de Google.
 */
const PHOTO = 'cypress/fixtures/pull.jpg'
const CHAMP_PHOTO = 'input[type="file"][accept="image/jpeg,image/png,image/webp"]'

describe('Ajout d\'un article par photo', () => {
  beforeEach(() => cy.seConnecter('camille'))

  it('envoie une photo, remplit la fiche, et retrouve l\'article dans sa pièce', () => {
    const marque = `Cypress ${Date.now()}`
    // La vraie photo est enregistrée par l'API ; seule la proposition du modèle est retirée.
    cy.intercept('POST', '**/api/reconnaissances', (requete) => {
      requete.continue((reponse) => {
        reponse.body.proposition = null
      })
    }).as('photo')

    cy.visiter('/ajout')
    // Sur ordinateur : choix d'un fichier (le champ est caché derrière le bouton « Choisir un fichier »).
    cy.get(CHAMP_PHOTO).selectFile(PHOTO, { force: true })
    cy.wait('@photo', { timeout: 30000 })

    cy.contains('h1', 'Nouvel article')
    cy.get('[data-cy=bandeau-fiche]').should('contain', 'Indiquez la catégorie')
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

  it('préremplit la fiche avec la proposition faite à partir de la photo (D-040)', () => {
    cy.request({ url: `${Cypress.env('api')}/api/categories`, headers: { Accept: 'application/json' } })
      .its('body')
      .then((categories) => {
        const pull = categories.find(categorie => categorie.nom === 'Pull')
        cy.intercept('POST', '**/api/reconnaissances', {
          statusCode: 201,
          body: { photo: 'photos/0/simulee.jpg', proposition: { categorie: pull, marque: 'Uniqlo', couleur: 'bleu marine' } },
        })
      })

    cy.visiter('/ajout')
    cy.get(CHAMP_PHOTO).selectFile(PHOTO, { force: true })

    cy.get('[data-cy=bandeau-fiche]').should('contain', 'Remplie à partir de la photo')
    cy.get('#categorie').find('option:selected').should('have.text', 'Pull')
    cy.get('#marque').should('have.value', 'Uniqlo')
    cy.get('#couleur').should('have.value', 'bleu marine')
  })

  it('exige une catégorie et un rangement', () => {
    cy.intercept('POST', '**/api/reconnaissances', { statusCode: 201, body: { photo: 'photos/0/simulee.jpg', proposition: null } })

    cy.visiter('/ajout')
    cy.get(CHAMP_PHOTO).selectFile(PHOTO, { force: true })

    cy.contains('button', 'Enregistrer l\'article').click()

    cy.contains('Choisissez une catégorie.')
    cy.contains('Choisissez où ranger l\'article.')
    cy.location('pathname').should('eq', '/ajout')
  })
})
