import { ref, computed, type ComputedRef } from 'vue'
import { useApiService, type ApiResponse } from './api'

// Type definitions
export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  access_token: string
  refresh_token: string
  user: User
}

export interface User {
  id: number
  name: string
  email: string
  [key: string]: any
}

export interface AuthTokens {
  accessToken: string
  refreshToken: string
}

// Authentication Service implementation
class AuthService {
  public apiService = useApiService()
  
  // Reactive state
  private tokens = ref<AuthTokens>({ accessToken: '', refreshToken: '' })
  private user = ref<User | null>(null)
  private initialized = ref(false)

  constructor() {
    this.setupInterceptors()
    this.initAuth()
  }

  /**
   * Reactive computed property for authentication status
   */
  get isAuthenticated(): ComputedRef<boolean> {
    return computed(() => {
      return !!(this.tokens.value.accessToken && this.tokens.value.accessToken.length > 0)
    })
  }

  /**
   * Check if auth service is initialized
   */
  get isInitialized(): ComputedRef<boolean> {
    return computed(() => this.initialized.value)
  }

  /**
   * Login user with credentials
   */
  async login(credentials: LoginCredentials): Promise<ApiResponse<LoginResponse>> {
    try {
      const response = await this.apiService.request<LoginResponse>('/api/v1/auth/login', {
        method: 'POST',
        body: credentials
      })

      if (response.success && response.data) {
        // Save tokens and user data
        this.saveTokens({
          accessToken: response.data.access_token,
          refreshToken: response.data.refresh_token
        })
        this.saveUser(response.data.user)
      }

      return response
    } catch (error) {
      throw error
    }
  }

  /**
   * Logout current user
   */
  async logout(): Promise<ApiResponse<void>> {
    try {
      const response = await this.apiService.request<void>('/api/v1/auth/logout', {
        method: 'POST'
      })

      return response
    } catch (error) {
      throw error
    } finally {
      // Always clear local data regardless of API response
      this.clearLocalData()
    }
  }

  /**
   * Refresh authentication token
   */
  async refreshToken(): Promise<ApiResponse<LoginResponse>> {
    const currentTokens = this.getTokens()
    
    if (!currentTokens.refreshToken) {
      throw new Error('No refresh token available')
    }

    try {
      const response = await this.apiService.request<LoginResponse>('/api/v1/auth/refresh', {
        method: 'POST',
        body: { refresh_token: currentTokens.refreshToken }
      })

      if (response.success && response.data) {
        // Update tokens and user data
        this.saveTokens({
          accessToken: response.data.access_token,
          refreshToken: response.data.refresh_token
        })
        this.saveUser(response.data.user)
      }

      return response
    } catch (error) {
      // If refresh fails, clear local data
      this.clearLocalData()
      throw error
    }
  }

  /**
   * Check current authentication status
   */
  async checkAuthStatus(): Promise<ApiResponse<{ authenticated: boolean }>> {
    return this.apiService.request<{ authenticated: boolean }>('/api/v1/auth/status', {
      method: 'GET'
    })
  }

  /**
   * Save authentication tokens
   */
  saveTokens(authTokens: AuthTokens): void {
    this.tokens.value = { ...authTokens }
    try {
      localStorage.setItem('auth_tokens', JSON.stringify(authTokens))
    } catch (error) {
      console.error('Failed to save tokens to localStorage:', error)
    }
  }

  /**
   * Get current authentication tokens
   */
  getTokens(): AuthTokens {
    return { ...this.tokens.value }
  }

  /**
   * Save user data
   */
  saveUser(userData: User | null): void {
    this.user.value = userData
    try {
      if (userData) {
        localStorage.setItem('auth_user', JSON.stringify(userData))
      } else {
        localStorage.removeItem('auth_user')
      }
    } catch (error) {
      console.error('Failed to save user data to localStorage:', error)
    }
  }

  /**
   * Get current user data
   */
  getCurrentUser(): User | null {
    return this.user.value
  }

  /**
   * Initialize authentication state from localStorage
   */
  initAuth(): void {
    try {
      // Load tokens from localStorage
      const storedTokens = localStorage.getItem('auth_tokens')
      if (storedTokens) {
        const tokens = JSON.parse(storedTokens)
        this.tokens.value = {
          accessToken: tokens.accessToken || '',
          refreshToken: tokens.refreshToken || ''
        }
      }

      // Load user data from localStorage
      const storedUser = localStorage.getItem('auth_user')
      if (storedUser) {
        this.user.value = JSON.parse(storedUser)
      }
    } catch (error) {
      console.error('Failed to initialize auth from localStorage:', error)
      this.clearLocalData()
    } finally {
      this.initialized.value = true
    }
  }

  /**
   * Clear all authentication data
   */
  private clearLocalData(): void {
    this.tokens.value = { accessToken: '', refreshToken: '' }
    this.user.value = null
    
    try {
      localStorage.removeItem('auth_tokens')
      localStorage.removeItem('auth_user')
    } catch (error) {
      console.error('Failed to clear localStorage:', error)
    }
  }

  /**
   * Setup API interceptors for authentication
   */
  private setupInterceptors(): void {
    // Request interceptor to add auth headers
    this.apiService.addRequestInterceptor((config) => {
      const tokens = this.getTokens()
      if (tokens.accessToken) {
        config.headers = {
          ...config.headers,
          'Authorization': `Bearer ${tokens.accessToken}`
        }
      }
      return config
    })

    // Response interceptor to handle token expiration
    this.apiService.addResponseInterceptor((response) => {
      // Check if response indicates token expiration
      if (!response.success && response.error?.code === 'TOKEN_EXPIRED') {
        // Could trigger automatic token refresh here
        this.clearLocalData()
      }
      return response
    })
  }
}

// Create singleton instance
let authServiceInstance: AuthService | null = null

/**
 * Composable to access the authentication service
 */
export function useAuthService(): AuthService {
  if (!authServiceInstance) {
    authServiceInstance = new AuthService()
  }
  return authServiceInstance
}

// Reset function for testing
;(useAuthService as any).reset = () => {
  authServiceInstance = null
}

// Export for direct use if needed
export { AuthService }