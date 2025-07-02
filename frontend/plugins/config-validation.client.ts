/**
 * Configuration Validation Plugin
 * 
 * This plugin validates the configuration on application startup
 * and logs warnings for missing or invalid configuration values.
 */

import { useApplicationConfig } from '~/composables/useApplicationConfig'

export default defineNuxtPlugin(() => {
  // Only run validation in development or when debug is enabled
  const config = useRuntimeConfig()
  const isDev = process.dev
  const enableDebug = config.public.enableDebug
  
  if (!isDev && !enableDebug) {
    return
  }
  console.group('🔧 Configuration Validation')
    try {
    const appConfig = useApplicationConfig()
    const warnings: string[] = []
    const errors: string[] = []
    
    // Validate required configuration
    if (!appConfig.app.name || appConfig.app.name === 'Thanzil') {
      warnings.push('App name is using default value. Consider customizing NUXT_APP_NAME.')
    }
    
    if (!appConfig.domains.frontend || appConfig.domains.frontend.includes('localhost')) {
      if (!isDev) {
        errors.push('Frontend URL should not use localhost in production.')
      }
    }
    
    if (!appConfig.domains.backend || appConfig.domains.backend.includes('127.0.0.1')) {
      if (!isDev) {
        errors.push('Backend URL should not use 127.0.0.1 in production.')
      }
    }
    
    if (!appConfig.api.baseUrl) {
      errors.push('API base URL is not configured.')
    }
    
    // Validate URL formats
    try {
      new URL(appConfig.domains.frontend)
    } catch {
      errors.push('Frontend URL is not a valid URL format.')
    }
    
    try {
      new URL(appConfig.domains.backend)
    } catch {
      errors.push('Backend URL is not a valid URL format.')
    }
    
    try {
      new URL(appConfig.api.baseUrl)
    } catch {
      errors.push('API base URL is not a valid URL format.')
    }
    
    // Validate feature flags
    if (appConfig.features.debug && !isDev) {
      warnings.push('Debug mode is enabled in production. Consider disabling NUXT_PUBLIC_ENABLE_DEBUG.')
    }
    
    if (appConfig.features.devtools && !isDev) {
      warnings.push('Devtools are enabled in production. Consider disabling NUXT_PUBLIC_ENABLE_DEVTOOLS.')
    }
    
    // Validate timeout values
    if (appConfig.api.timeout < 5000) {
      warnings.push('API timeout is very low. Consider increasing NUXT_PUBLIC_API_TIMEOUT.')
    }
    
    if (appConfig.auth.sessionTimeout < 300) {
      warnings.push('Session timeout is very low. Consider increasing NUXT_PUBLIC_SESSION_TIMEOUT.')
    }
    
    // Log results
    console.log('✅ Configuration loaded successfully')
    console.log('📍 Frontend URL:', appConfig.domains.frontend)
    console.log('🔗 Backend URL:', appConfig.domains.backend)
    console.log('🌐 API Base URL:', appConfig.api.baseUrl)
    console.log('🔒 Authentication:', appConfig.auth.enabled ? 'Enabled' : 'Disabled')
    console.log('🛠️ Environment:', isDev ? 'Development' : 'Production')
    
    if (warnings.length > 0) {
      console.group('⚠️ Configuration Warnings')
      warnings.forEach(warning => console.warn(warning))
      console.groupEnd()
    }
    
    if (errors.length > 0) {
      console.group('❌ Configuration Errors')
      errors.forEach(error => console.error(error))
      console.groupEnd()
      
      if (!isDev) {
        throw new Error('Configuration validation failed. Check the errors above.')
      }
    }
    
    if (warnings.length === 0 && errors.length === 0) {
      console.log('✨ All configuration checks passed!')
    }
    
  } catch (error) {
    console.error('Failed to validate configuration:', error)
    
    if (!isDev) {
      throw error
    }
  } finally {
    console.groupEnd()
  }
})
