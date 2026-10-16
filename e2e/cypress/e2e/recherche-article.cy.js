/**
 * P3 : Camille cherche sa veste noire, voit où elle est rangée, l'ouvre et la déplace (F-26, F-09).
 * Les fixtures donnent à Camille au moins une veste noire ; le test ne dépend pas de son rangement de départ.
 */
describe('Retrouver un article', () => {
  beforeEach(() => cy.seConnecter('camille'))

  it('cherche « veste », restreint à la couleur noire, puis voit où chaque article est rangé', () => {
    cy.visiter('/recherche')

    cy.get('[data-cy=recherche-texte]').type('veste')
    cy.location('search').should('eq', '?q=veste')
    cy.get('[data-cy=filtre-couleur]').type('noir')
    cy.location('search').should('eq', '?q=veste&couleur=noir')

    cy.get('[data-cy=resume]').should('contain', 'trouvé')
    cy.contains('[data-cy=resultats] [data-cy=article]', 'Veste')
    cy.get('[data-cy=resultats] [data-cy=article]').each((carte) => {
      // La couleur, et l'emplacement complet : logement › pièce › rangement.
      cy.wrap(carte).invoke('text').should('match', /noir/i).and('match', /\S › \S.* › \S/)
    })
  })

  it('ouvre la veste, la déplace, et retrouve sa recherche au retour', () => {
    cy.visiter('/recherche?q=veste&couleur=noir')
    cy.contains('[data-cy=article]', 'Veste').click()

    cy.location('pathname').should('match', /^\/articles\/\d+$/).as('fiche', { type: 'static' })
    cy.contains('h1', 'Veste')
    cy.get('[data-cy=emplacement]').invoke('text').then((avant) => {
      cy.get('[data-cy=deplacer]').click()

      // Premier rangement différent de l'actuel.
      cy.get('[data-cy=destination] option:not(:selected)').first().then((option) => {
        const destination = option.text()
        const rangement = destination.split(' › ').pop()
        cy.get('[data-cy=destination]').select(destination)
        cy.get('[data-cy=valider-deplacement]').click()

        cy.get('[data-cy=confirmation]').should('contain', 'Article déplacé.')
        cy.get('[data-cy=emplacement]').should('contain', rangement).invoke('text').should('not.eq', avant)

        // Retour à la recherche, critères compris ; la carte montre le nouvel emplacement.
        cy.contains('a', 'Retour').click()
        cy.location('search').should('eq', '?q=veste&couleur=noir')
        cy.get('[data-cy=recherche-texte]').should('have.value', 'veste')
        cy.get('[data-cy=filtre-couleur]').should('have.value', 'noir')
        cy.get('@fiche').then((fiche) => {
          cy.get(`[data-cy=article][href="${fiche}"]`).should('contain', destination)
        })
      })
    })
  })

  it('ne trouve rien pour un mot absent, et propose d\'effacer les filtres', () => {
    cy.visiter('/recherche?q=introuvable-xyz&couleur=noir')

    cy.get('[data-cy=resume]').should('have.text', 'Aucun article trouvé')
    cy.contains('Aucun article ne correspond')
    cy.contains('button', 'Effacer les filtres').click()
    cy.location('search').should('eq', '?q=introuvable-xyz')
  })
})
