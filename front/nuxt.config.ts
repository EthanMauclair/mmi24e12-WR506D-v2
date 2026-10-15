// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  css: ['~/assets/css/principal.css'],

  app: {
    head: {
      htmlAttrs: { lang: 'fr' },
      title: 'Penderie',
      meta: [{ name: 'theme-color', content: '#6E1A2B' }],
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          // Atkinson Hyperlegible : police sans empattements dessinée pour la lisibilité (D-026).
          href: 'https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&display=swap',
        },
      ],
    },
  },

  // Adresse de l'API : elle n'est pas la même selon l'endroit où s'exécute l'appel.
  // - Rendu serveur (conteneur front) : « localhost » désigne le conteneur lui-même, on passe par le service Docker nginx.
  // - Navigateur : l'API est publiée sur le port 8080 de la machine.
  // Surchargées par NUXT_API_BASE_SERVEUR et NUXT_PUBLIC_API_BASE (compose.yaml).
  runtimeConfig: {
    apiBaseServeur: 'http://nginx',
    public: {
      apiBase: 'http://localhost:8080',
    },
  },
})
