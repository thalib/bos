/**
 * Runtime Configuration Type Definitions
 * 
 * This file extends Nuxt's runtime configuration types to include
 * our custom configuration structure with proper type checking.
 */

declare module '@nuxt/schema' {
  interface RuntimeConfig {
    // Private runtime config (server-side only)
    appVersion: string
  }

  interface PublicRuntimeConfig {
    // Application Configuration
    appName: string
    appDescription: string
    appVersion: string
    
    // Domain Configuration
    frontendUrl: string
    backendUrl: string
    
    // API Configuration
    apiBase: string
    apiVersion: string
    apiTimeout: number
    
    // Authentication Configuration
    enableAuth: boolean
    sessionTimeout: number
    tokenRefreshInterval: number
    
    // Feature Flags
    enableDevtools: boolean
    enableDebug: boolean
    
    // UI Configuration
    theme: string
    itemsPerPage: number
    maxUploadSize: number
  }
}

// Extend the global Nuxt runtime config interface
declare module 'nuxt/app' {
  interface RuntimeConfig {
    appVersion: string
  }

  interface PublicRuntimeConfig {
    appName: string
    appDescription: string
    appVersion: string
    frontendUrl: string
    backendUrl: string
    apiBase: string
    apiVersion: string
    apiTimeout: number
    enableAuth: boolean
    sessionTimeout: number
    tokenRefreshInterval: number
    enableDevtools: boolean
    enableDebug: boolean
    theme: string
    itemsPerPage: number
    maxUploadSize: number
  }
}

export {}
