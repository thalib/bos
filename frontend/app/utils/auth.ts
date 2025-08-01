import { ref, computed, type ComputedRef } from 'vue'
import { useApiService, type ApiResponse } from './api'
import { useNotifyService } from './notify'

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
  private notifyService = useNotifyService()
  
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
      const response = await this.apiService.request<LoginResponse>('/auth/login', {
        method: 'POST',
        body: {
          username: credentials.email, // Backend expects 'username' field
          password: credentials.password
        }
      })

      if (response.success && response.data) {
        // Save tokens and user data
        this.saveTokens({
          accessToken: response.data.access_token,
          refreshToken: response.data.refresh_token
        })
        this.saveUser(response.data.user)
        
        // Show success notification
        this.notifyService.success(
          `Welcome back, ${response.data.user.name}!`,
          'Login Successful'
        )
      }

      return response
    } catch (error) {
      // Error notifications are already handled by the API service
      this.notifyService.error(
        'Invalid credentials. Please check your email and password.',
        'Login Failed'
      )
      throw error
    }
  }

  /**
   * Logout current user
   */
  async logout(): Promise<ApiResponse<void>> {
    try {
      const response = await this.apiService.request<void>('/auth/logout', {
        method: 'POST'
      })

      if (response.success) {
        this.notifyService.success(
          'You have been logged out successfully.',
          'Logged Out'
        )
      }

      return response
    } catch (error) {
      // Log the logout error but don't prevent logout
      this.notifyService.warning(
        'Logout request failed, but you have been logged out locally.',
        'Logout Warning'
      )
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
      const response = await this.apiService.request<LoginResponse>('/auth/refresh', {
        method: 'POST',
        body: { refreshToken: currentTokens.refreshToken } // Backend expects 'refreshToken' field
      })

      if (response.success && response.data) {
        // Update tokens and user data
        this.saveTokens({
          accessToken: response.data.access_token,
          refreshToken: response.data.refresh_token
        })
        this.saveUser(response.data.user)
        
        // Optionally show a subtle notification for token refresh
        this.notifyService.info(
          'Session refreshed successfully.',
          'Session Updated'
        )
      }

      return response
    } catch (error) {
      // If refresh fails, clear local data
      this.clearLocalData()
      this.notifyService.error(
        'Session expired. Please log in again.',
        'Session Expired'
      )
      throw error
    }
  }

  /**
   * Check current authentication status
   */
  async checkAuthStatus(): Promise<ApiResponse<{ authenticated: boolean }>> {
    return this.apiService.request<{ authenticated: boolean }>('/auth/status', {
      method: 'GET'
    })
  }

  /**
   * Save authentication tokens
   */
  saveTokens(authTokens: AuthTokens): void {
    this.tokens.value = { ...authTokens }
    try {
      if (process.client && typeof window !== 'undefined') {
        localStorage.setItem('auth_tokens', JSON.stringify(authTokens))
      }
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
      if (process.client && typeof window !== 'undefined') {
        if (userData) {
          localStorage.setItem('auth_user', JSON.stringify(userData))
        } else {
          localStorage.removeItem('auth_user')
        }
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
      // Only try to access localStorage on the client side
      if (process.client && typeof window !== 'undefined') {
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
      if (process.client && typeof window !== 'undefined') {
        localStorage.removeItem('auth_tokens')
        localStorage.removeItem('auth_user')
      }
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