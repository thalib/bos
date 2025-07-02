/**
 * Centralized Configuration Composable
 * 
 * This composable provides access to all configuration settings from a single place.
 * When changing domains or other configuration, update the .env file and this composable
 * will automatically reflect those changes throughout the application.
 * 
 * Usage:
 * const config = useApplicationConfig()
 * console.log(config.api.baseUrl) // Access API base URL
 * console.log(config.app.name)    // Access app name
 */

export interface AppConfig {
  app: {
    name: string
    description: string
    version: string
    title: string
  }
  domains: {
    frontend: string
    backend: string
  }
  api: {
    baseUrl: string
    version: string
    timeout: number
    fullBaseUrl: string
  }
  auth: {
    enabled: boolean
    sessionTimeout: number
    tokenRefreshInterval: number
  }
  ui: {
    theme: string
    itemsPerPage: number
    maxUploadSize: number
  }
  features: {
    devtools: boolean
    debug: boolean
  }
}

export const useApplicationConfig = (): AppConfig => {
  const config = useRuntimeConfig()
  
  return {
    app: {
      name: config.public.appName,
      description: config.public.appDescription,
      version: config.public.appVersion,
      title: config.public.appName
    },
    domains: {
      frontend: config.public.frontendUrl,
      backend: config.public.backendUrl
    },
    api: {
      baseUrl: config.public.apiBase,
      version: config.public.apiVersion,
      timeout: config.public.apiTimeout,
      fullBaseUrl: `${config.public.apiBase}/${config.public.apiVersion}`
    },
    auth: {
      enabled: config.public.enableAuth,
      sessionTimeout: config.public.sessionTimeout,
      tokenRefreshInterval: config.public.tokenRefreshInterval
    },
    ui: {
      theme: config.public.theme,
      itemsPerPage: config.public.itemsPerPage,
      maxUploadSize: config.public.maxUploadSize
    },
    features: {
      devtools: config.public.enableDevtools,
      debug: config.public.enableDebug
    }
  }
}

/**
 * Helper function to get API endpoint URL
 * @param endpoint - The API endpoint path
 * @param version - Optional API version override
 * @returns Full API URL
 */
export const useApiEndpoint = (endpoint: string, version?: string): string => {
  const config = useApplicationConfig()
  const apiVersion = version || config.api.version
  
  // Remove leading slash if present
  const cleanEndpoint = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint
  
  return `${config.api.baseUrl}/${apiVersion}/${cleanEndpoint}`
}

/**
 * Helper function to check if a feature is enabled
 * @param feature - Feature name
 * @returns Whether the feature is enabled
 */
export const useFeatureFlag = (feature: keyof AppConfig['features']): boolean => {
  const config = useApplicationConfig()
  return config.features[feature]
}

/**
 * Helper function to get environment-specific configuration
 * Useful for different settings in development vs production
 */
export const useEnvironmentConfig = () => {
  const config = useApplicationConfig()
  const isDevelopment = process.dev
  
  return {
    isDevelopment,
    isProduction: !isDevelopment,
    showDebugInfo: isDevelopment && config.features.debug,
    apiTimeout: isDevelopment ? config.api.timeout * 2 : config.api.timeout, // Longer timeout in dev
  }
}
