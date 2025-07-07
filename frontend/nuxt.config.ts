// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: process.env.NUXT_PUBLIC_ENABLE_DEVTOOLS === 'true' },

  // Add CSS configuration
  css: [
    'bootstrap/dist/css/bootstrap.min.css',
    'bootstrap-icons/font/bootstrap-icons.css'
  ],
  
  // Add Vite configuration for Bootstrap JS
  vite: {
    define: {
      'process.env.DEBUG': process.env.NUXT_PUBLIC_ENABLE_DEBUG === 'true',
    },
  },

  // Error handling configuration
  experimental: {
    // Enable error page improvements
    emitRouteChunkError: 'automatic'
  },

  // Router configuration for error handling
  router: {
    options: {
      // Custom route matching for better error handling
      strict: false, // Allow trailing slashes to prevent unnecessary 404s
      sensitive: false, // Case-insensitive routing
    }
  },

  // Nitro configuration for API proxy and error handling
  nitro: {
    devProxy: {
      '/api': {
        target: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api',
        changeOrigin: true,
        prependPath: true,
      }
    }
    // Note: Removed errorHandler config as it's causing build issues
    // Error handling is managed through pages/404.vue and middleware
  },

  // Runtime configuration for centralized config
  runtimeConfig: {
    // Private keys (only available on server-side)
    appVersion: process.env.NUXT_APP_VERSION || '1.0.0',
    
    // Public keys (exposed to client-side)
    public: {
      // Application Configuration
      appName: process.env.NUXT_APP_NAME || 'Thanzil',
      appDescription: process.env.NUXT_APP_DESCRIPTION || 'Thanzil - Your Business Management Solution',
      appVersion: process.env.NUXT_APP_VERSION || '1.0.0',
      
      // Domain Configuration
      frontendUrl: process.env.NUXT_FRONTEND_URL || 'http://localhost:3000',
      backendUrl: process.env.NUXT_BACKEND_URL || 'http://127.0.0.1:8000',
      
      // API Configuration
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api',
      apiVersion: process.env.NUXT_PUBLIC_API_VERSION || 'v1',
      apiTimeout: parseInt(process.env.NUXT_PUBLIC_API_TIMEOUT || '30000'),
      
      // Authentication Configuration
      enableAuth: process.env.NUXT_PUBLIC_ENABLE_AUTH === 'true',
      sessionTimeout: parseInt(process.env.NUXT_PUBLIC_SESSION_TIMEOUT || '3600'),
      tokenRefreshInterval: parseInt(process.env.NUXT_PUBLIC_TOKEN_REFRESH_INTERVAL || '300000'),
      
      // Feature Flags
      enableDevtools: process.env.NUXT_PUBLIC_ENABLE_DEVTOOLS === 'true',
      enableDebug: process.env.NUXT_PUBLIC_ENABLE_DEBUG === 'true',
        // UI Configuration
      theme: process.env.NUXT_PUBLIC_THEME || 'default',
      itemsPerPage: parseInt(process.env.NUXT_PUBLIC_ITEMS_PER_PAGE || '20'),
      maxUploadSize: parseInt(process.env.NUXT_PUBLIC_MAX_UPLOAD_SIZE || '10485760'),
      
      // Currency Configuration
      currencyCode: process.env.NUXT_PUBLIC_CURRENCY_CODE || 'INR',
      currencySymbol: process.env.NUXT_PUBLIC_CURRENCY_SYMBOL || '₹',
      currencyLocale: process.env.NUXT_PUBLIC_CURRENCY_LOCALE || 'en-IN',
      
      // Error Handling Configuration
      enableErrorReporting: process.env.NUXT_PUBLIC_ENABLE_ERROR_REPORTING === 'true',
      errorReportingEndpoint: process.env.NUXT_PUBLIC_ERROR_REPORTING_ENDPOINT,
    }
  },

  app: {
    head: {
      title: process.env.NUXT_APP_NAME || 'Thanzil'
    }
  },

  // Plugin configuration with proper loading order
  plugins: [
    // Core plugins first
    { src: '~/plugins/api.client.ts', mode: 'client' },
    { src: '~/plugins/loading.client.ts', mode: 'client' },
    
    // Navigation and error handling plugins
    { src: '~/plugins/navigation-history.client.ts', mode: 'client' },
    { src: '~/plugins/error-handler.client.ts', mode: 'client' },
    
    // UI plugins
    { src: '~/plugins/bootstrap.client.ts', mode: 'client' },
    { src: '~/plugins/nprogress.client.ts', mode: 'client' },
    { src: '~/plugins/toast.client.ts', mode: 'client' },
    
    // Configuration validation (load last)
    { src: '~/plugins/config-validation.client.ts', mode: 'client' }
  ],

  // Route rules for error handling and performance
  routeRules: {
    // Static pages - cache longer
    '/': { 
      prerender: false // Dynamic content with auth
    },
    
    // 404 page - client-side only (uses authentication)
    '/404': { 
      prerender: false, // Client-side rendering for auth state
      headers: { 
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'X-Robots-Tag': 'noindex, nofollow'
      }
    },
    
    // API routes - never cache, pass through to backend
    '/api/**': { 
      cors: true,
      headers: { 
        'Cache-Control': 'no-cache, no-store, must-revalidate' 
      }
    },
    
    // Dynamic pages - short cache
    '/estimates/**': { 
      isr: 60, // Incremental static regeneration
      headers: { 'Cache-Control': 's-maxage=60' }
    },
    '/doc/**': { 
      isr: 60,
      headers: { 'Cache-Control': 's-maxage=60' }
    },
    '/list/**': { 
      isr: 60,
      headers: { 'Cache-Control': 's-maxage=60' }
    }
  },

  // SSR configuration for error handling
  ssr: true,

  // Optional: If using TypeScript extensively
  typescript: {
    strict: true,
    typeCheck: true // Enable type checking during development and build
  },
  compatibilityDate: '2025-04-21'
})