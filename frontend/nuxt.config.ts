// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },

  // Add CSS configuration
  css: [
    'bootstrap/dist/css/bootstrap.min.css',
    'bootstrap-icons/font/bootstrap-icons.css'
  ],

  // Add Vite configuration for Bootstrap JS
  vite: {
    define: {
      'process.env.DEBUG': false,
    },
  },

  app: {
    head: {
      title: 'Thanzil'
    }
  },

  // Ensure Bootstrap JS is available client-side
  plugins: [
    { src: '~/plugins/bootstrap.client.ts', mode: 'client' }
  ],

  // Optional: If using TypeScript extensively
  typescript: {
    strict: true,
    typeCheck: true // Enable type checking during development and build
  },

  compatibilityDate: '2025-04-21',

  runtimeConfig: {
    public: {
      ENABLE_AUTH: process.env.ENABLE_AUTH !== 'false', // default true, set to 'false' to disable auth
    },
  }
})