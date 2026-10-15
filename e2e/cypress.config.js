const { defineConfig } = require('cypress')

/**
 * Tests de bout en bout (séances 16-21) : un vrai navigateur, contre l'application lancée par Docker.
 * Prérequis : make front, puis make fixtures (les tests utilisent le compte de démonstration de Camille).
 */
module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:3000',
    specPattern: 'cypress/e2e/**/*.cy.js',
    viewportWidth: 1280,
    viewportHeight: 800,
    // Le rendu serveur en mode développement peut prendre quelques secondes à la première visite.
    defaultCommandTimeout: 15000,
    pageLoadTimeout: 60000,
    video: false,
    screenshotOnRunFailure: true,
    env: {
      api: 'http://localhost:8080',
      // Mot de passe de tous les comptes des fixtures (AppFixtures::MOT_DE_PASSE).
      motDePasse: 'penderie-demo',
    },
  },
})
