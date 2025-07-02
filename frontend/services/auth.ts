/**
 * Authentication Service for Thanzil project
 * Handles authentication tokens and auth state management
 */
import { ref, computed } from 'vue'
import type { User, ApiResponse } from '~/types'
import { useAuthApi } from './authApi'
import { useApiService, type RequestInterceptor, type ResponseInterceptor } from './api'

// Token storage keys
const ACCESS_TOKEN_KEY = 'auth_token'
const REFRESH_TOKEN_KEY = 'auth_refresh_token'
const USER_DATA_KEY = 'auth_user'

// Authentication Configuration
export const authConfig = ref({
  // Base endpoint for auth requests
  endpoint: 'auth',
  // Token type (Bearer is the standard)
  tokenType: 'Bearer',
  // API version for auth requests
  version: 'v1'
})

/**
 * Authentication interface for token-related operations
 */
export interface AuthTokens {
  accessToken: string | null
  refreshToken: string | null
}

/**
 * Login credentials interface
 */
export interface LoginCredentials {
  username: string
  password: string
}

/**
 * Login response from the API
 */
export interface LoginResponse {
  user: User
  access_token: string
  refresh_token?: string
  token_type?: string
  expires_in?: number
  message?: string
}

// Singleton instance to ensure only one auth service exists
let authServiceInstance: ReturnType<typeof createAuthService> | null = null

// Debug: Track how many times the service is created
let instanceCreationCount = 0;

/**
 * Authentication Service Factory
 * Handles login, logout, token management, and auth state
 */
const createAuthService = () => {
  // State
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
    // Get base API service
  const api = useApiService()
    /**
   * Get stored auth tokens
   */
  const getTokens = (): AuthTokens => {
    if (!process.client) {
      return { accessToken: null, refreshToken: null }
    }
    
    const accessToken = localStorage.getItem(ACCESS_TOKEN_KEY)
    const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY)
    
    return {
      accessToken,
      refreshToken
    }
  }
  /**
   * Load stored auth data on initialization
   */
  const initAuth = () => {
    if (process.client) {
      // Load user data
      try {
        const storedUser = localStorage.getItem(USER_DATA_KEY)
        
        if (storedUser) {
          const userData = JSON.parse(storedUser)
          user.value = userData
        }
      } catch (err) {
        console.error('Failed to load stored user data:', err)
        // Clear potentially corrupted data
        localStorage.removeItem(USER_DATA_KEY)
      }
      
      // Also check tokens      const tokens = getTokens()
    }
  }
  
  // Initialize auth on creation
  initAuth()
  
  /**
   * Save authentication tokens to storage
   */
  const saveTokens = (tokens: AuthTokens): void => {
    if (!process.client) return
    
    if (tokens.accessToken) {
      localStorage.setItem(ACCESS_TOKEN_KEY, tokens.accessToken)
    } else {
      localStorage.removeItem(ACCESS_TOKEN_KEY)
    }
    
    if (tokens.refreshToken) {
      localStorage.setItem(REFRESH_TOKEN_KEY, tokens.refreshToken)
    } else {
      localStorage.removeItem(REFRESH_TOKEN_KEY)
    }
  }
  /**
   * Save user data to storage
   */
  const saveUser = (userData: User | null): void => {
    if (!process.client) return
    
    if (userData) {
      localStorage.setItem(USER_DATA_KEY, JSON.stringify(userData))
      user.value = userData
    } else {
      localStorage.removeItem(USER_DATA_KEY)
      user.value = null
    }
  }  /**
   * Check if user is authenticated
   */
  const isAuthenticated = computed((): boolean => {
    if (!process.client) {
      return false
    }
    
    // Check if there's a user and a token
    const hasUser = !!user.value
    const tokens = getTokens()
    const hasToken = !!tokens.accessToken
    
    return hasUser && hasToken;
  })/**
   * Login with credentials
   */
  const login = async (credentials: LoginCredentials): Promise<ApiResponse<LoginResponse>> => {    loading.value = true
    error.value = null
    
    try {
      // Use the authApi service
      const authApi = useAuthApi()
      const response = await authApi.login(credentials)
      
      // Handle login response
      if (response.data && !response.error) {
        // Extract tokens using the correct field names from backend (snake_case)
        const accessToken = response.data.access_token;
        const refreshToken = response.data.refresh_token;
        
        // Save tokens and user data
        saveTokens({
          accessToken,
          refreshToken: refreshToken || null
        })
        
        saveUser(response.data.user)
      } else if (response.error) {
        error.value = response.error.message || 'Failed to login'
      }
      
      return response
    } catch (err: any) {
      error.value = err.message || 'An error occurred during login'
      return {
        data: null,
        error: err as Error,
        loading: false
      }
    } finally {
      loading.value = false
    }
  }/**
   * Refresh the access token using refresh token
   */
  const refreshToken = async (): Promise<ApiResponse<LoginResponse>> => {
    const tokens = getTokens()
    
    if (!tokens.refreshToken) {
      const err = new Error('No refresh token available')
      return {
        data: null,
        error: err,
        loading: false
      }
    }
    
    loading.value = true
    
    try {
      // Use the authApi service
      const authApi = useAuthApi()
      const response = await authApi.refreshToken(tokens.refreshToken)
        // Handle refresh response
      if (response.data && !response.error) {
        // Save new tokens using correct field names
        saveTokens({
          accessToken: response.data.access_token,
          refreshToken: response.data.refresh_token || tokens.refreshToken
        })
        
        // Update user data if included in response
        if (response.data.user) {
          saveUser(response.data.user)
        }
      } else if (response.error) {
        // If refresh fails, clear tokens and user
        logout()
        error.value = response.error.message || 'Failed to refresh token'
      }
      
      return response
    } catch (err: any) {
      // If refresh fails with an error, clear tokens and user
      logout()
      error.value = err.message || 'An error occurred during token refresh'
      return {
        data: null,
        error: err as Error,
        loading: false
      }
    } finally {
      loading.value = false
    }
  }  /**
   * Log out the current user
   */
  const logout = (): void => {
    // Clear tokens and user data
    saveTokens({ accessToken: null, refreshToken: null })
    saveUser(null)
    
    // Make logout request
    const authApi = useAuthApi()
    authApi.logout().catch((err: Error) => {
      console.error('Error during logout:', err)
    })  }
  
  return {
    user,
    loading,
    error,
    isAuthenticated,
    login,
    logout,
    refreshToken,
    getTokens,
    saveTokens,
    saveUser,
    initAuth,
    authConfig
  }
}

/**
 * Singleton Auth Service
 * Ensures only one instance of the auth service exists
 */
export const useAuthService = () => {
  if (!authServiceInstance) {
    instanceCreationCount++;
    authServiceInstance = createAuthService()
  }
  return authServiceInstance
}

// Create auth token interceptor for the API service
export const createAuthTokenInterceptor = () => {
  // Use the singleton auth service to ensure we're using the same instance
  const authService = useAuthService()
  const { getTokens, refreshToken, isAuthenticated } = authService
  
  // Keep track of refresh attempts to prevent infinite loops
  let isRefreshing = false  // Request interceptor to add auth token
  const requestInterceptor: RequestInterceptor = (url, options) => {
    // Get current tokens
    const tokens = getTokens()
    
    if (!isAuthenticated.value) {
      return options
    }
    
    const { accessToken } = tokens
      if (accessToken) {
      const authHeader = `${authConfig.value.tokenType} ${accessToken}`
      
      // Create headers object properly
      const headers = {
        ...options.headers,
        'Authorization': authHeader
      }
      
      return {
        ...options,
        headers
      }
    }
    
    return options
  }
    // Response interceptor to handle token refresh on 401 errors
  const responseInterceptor: ResponseInterceptor = async (response, options) => {
    // If not authenticated, pass through
    if (!isAuthenticated.value) {
      return response
    }
    
    // If response is unauthorized and we're not already refreshing
    if (response.status === 401 && !isRefreshing) {
      isRefreshing = true
      
      try {
        // Try to refresh the token
        const refreshResponse = await refreshToken()
        
        // If refresh was successful, retry the original request
        if (refreshResponse.data && !refreshResponse.error) {
          const { accessToken } = getTokens()
          
          // Get the URL from the response
          const responseUrl = response.url
          
          // Create new headers with fresh token
          const requestHeaders = new Headers()
          
          // Copy original headers
          if (options.resource) {
            const originalOptions = options as any
            if (originalOptions.headers) {
              for (const [key, value] of Object.entries(originalOptions.headers)) {
                requestHeaders.set(key, value as string)
              }
            }
          }
          
          // Add authorization header
          requestHeaders.set('Authorization', `${authConfig.value.tokenType} ${accessToken}`)
          
          // Retry original request with new token
          const retryResponse = await fetch(responseUrl, {
            method: 'GET', // Default to GET
            headers: requestHeaders
          })
          
          isRefreshing = false
          return retryResponse
        }
      } catch (err: any) {
        console.error('Token refresh failed:', err)
      } finally {
        isRefreshing = false
      }
    }
    
    return response
  }
  
  return { requestInterceptor, responseInterceptor }
}

// Create a composable to expose auth functionality consistently  
export const useAuthServiceDirect = () => {
  const auth = useAuthService()
  return auth
}
