// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@nuxt/test-utils', '@pinia/nuxt'],
  css: [
    'bootstrap/dist/css/bootstrap.min.css',
    'bootstrap-icons/font/bootstrap-icons.css',
    'toastr/build/toastr.min.css'
  ],
  router: {
    middleware: ['auth']
  },
  app: {
    head: {
      htmlAttrs: {
        'data-bs-theme': 'dark'
      }
    }
  },
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api/v1'
    }
  }
})