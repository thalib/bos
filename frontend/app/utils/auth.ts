/**
 * Authentication Service
 * 
 * Centralized service for handling user authentication and authorization.
 * Provides methods for login, logout, token management, and route protection.
 */

import { useApiService } from './api'
import type { 
  ApiResponse, 
  LoginCredentials, 
  LoginResponse, 
  AuthTokens, 
  User 
} from './api'

class AuthService {
  private readonly TOKEN_KEY = 'auth_tokens'
  private readonly USER_KEY = 'auth_user'
  private readonly REFRESH_TOKEN_KEY = 'refresh_token'
  
  private _isAuthenticated = ref(false)
  private _user = ref<User | null>(null)
  private _isInitialized = ref(false)
  private apiService = useApiService()

  constructor() {
    // Initialize authentication state
    this.initAuth()
    
    // Setup request interceptor to add auth headers
    this.apiService.addRequestInterceptor(async (config) => {
      const tokens = this.getTokens()
      if (tokens?.access_token) {
        config.headers = {
          ...config.headers,
          'Authorization': `${tokens.token_type} ${tokens.access_token}`
        }
      }
      return config
    })

    // Setup response interceptor to handle token refresh
    this.apiService.addResponseInterceptor(async (response) => {
      if (!response.success && response.error?.code === 'UNAUTHORIZED') {
        // Try to refresh token
        const refreshResult = await this.refreshToken()
        if (!refreshResult.success) {
          // Refresh failed, clear auth state
          this.clearAuthState()
        }
      }
      return response
    })
  }

  /**
   * Reactive computed property for authentication status
   */
  get isAuthenticated(): ComputedRef<boolean> {
    return computed(() => this._isAuthenticated.value)
  }

  /**
   * Reactive computed property for current user
   */
  get currentUser(): ComputedRef<User | null> {
    return computed(() => this._user.value)
  }

  /**
   * Reactive computed property for initialization status
   */
  get isInitialized(): ComputedRef<boolean> {
    return computed(() => this._isInitialized.value)
  }

  /**
   * Initialize authentication state from stored data
   */
  initAuth(): void {
    if (process.client) {
      const tokens = this.getTokens()
      const user = this.getStoredUser()
      
      if (tokens?.access_token && user) {
        this._isAuthenticated.value = true
        this._user.value = user
        
        // Validate token by checking auth status
        this.checkAuthStatus().then((response) => {
          if (!response.success) {
            this.clearAuthState()
          }
        }).catch(() => {
          this.clearAuthState()
        })
      }
    }
    
    this._isInitialized.value = true
  }

  /**
   * Save authentication tokens to local storage
   * @param tokens The authentication tokens
   */
  saveTokens(tokens: AuthTokens): void {
    if (process.client) {
      localStorage.setItem(this.TOKEN_KEY, JSON.stringify(tokens))
      if (tokens.refresh_token) {
        localStorage.setItem(this.REFRESH_TOKEN_KEY, tokens.refresh_token)
      }
    }
  }

  /**
   * Get authentication tokens from local storage
   * @returns The stored authentication tokens
   */
  getTokens(): AuthTokens | null {
    if (process.client) {
      try {
        const stored = localStorage.getItem(this.TOKEN_KEY)
        return stored ? JSON.parse(stored) : null
      } catch (error) {
        console.error('Error parsing stored tokens:', error)
        return null
      }
    }
    return null
  }

  /**
   * Save user data to local storage
   * @param user The user data
   */
  saveUser(user: User | null): void {
    if (process.client) {
      if (user) {
        localStorage.setItem(this.USER_KEY, JSON.stringify(user))
      } else {
        localStorage.removeItem(this.USER_KEY)
      }
    }
    this._user.value = user
  }

  /**
   * Get stored user data from local storage
   * @returns The stored user data
   */
  getStoredUser(): User | null {
    if (process.client) {
      try {
        const stored = localStorage.getItem(this.USER_KEY)
        return stored ? JSON.parse(stored) : null
      } catch (error) {
        console.error('Error parsing stored user:', error)
        return null
      }
    }
    return null
  }

  /**
   * Clear all authentication state
   */
  private clearAuthState(): void {
    this._isAuthenticated.value = false
    this._user.value = null
    
    if (process.client) {
      localStorage.removeItem(this.TOKEN_KEY)
      localStorage.removeItem(this.USER_KEY)
      localStorage.removeItem(this.REFRESH_TOKEN_KEY)
    }
  }

  /**
   * Authenticate a user with credentials
   * @param credentials The login credentials
   * @returns Promise with login response
   */
  async login(credentials: LoginCredentials): Promise<ApiResponse<LoginResponse>> {
    try {
      const response = await this.apiService.request<LoginResponse>('auth/login', {
        method: 'POST',
        body: credentials
      })

      if (response.success && response.data) {
        // Store tokens and user data
        this.saveTokens(response.data.tokens)
        this.saveUser(response.data.user)
        this._isAuthenticated.value = true
      }

      return response
    } catch (error) {
      console.error('Login error:', error)
      return {
        success: false,
        message: 'Login failed. Please try again.',
        error: {
          code: 'LOGIN_ERROR',
          details: 'An error occurred during login'
        }
      }
    }
  }

  /**
   * Log out the current user
   * @returns Promise with logout response
   */
  async logout(): Promise<ApiResponse<void>> {
    try {
      const response = await this.apiService.request<void>('auth/logout', {
        method: 'POST'
      })

      // Clear auth state regardless of API response
      this.clearAuthState()

      return response.success ? response : {
        success: true,
        message: 'Logged out successfully'
      }
    } catch (error) {
      // Clear auth state even if API call fails
      this.clearAuthState()
      console.error('Logout error:', error)
      
      return {
        success: true,
        message: 'Logged out successfully'
      }
    }
  }

  /**
   * Refresh the authentication token
   * @returns Promise with new token response
   */
  async refreshToken(): Promise<ApiResponse<LoginResponse>> {
    try {
      const refreshToken = process.client 
        ? localStorage.getItem(this.REFRESH_TOKEN_KEY)
        : null

      if (!refreshToken) {
        return {
          success: false,
          message: 'No refresh token available',
          error: {
            code: 'NO_REFRESH_TOKEN',
            details: 'Refresh token not found'
          }
        }
      }

      const response = await this.apiService.request<LoginResponse>('auth/refresh', {
        method: 'POST',
        body: { refresh_token: refreshToken }
      })

      if (response.success && response.data) {
        // Update stored tokens and user data
        this.saveTokens(response.data.tokens)
        this.saveUser(response.data.user)
        this._isAuthenticated.value = true
      } else {
        // Refresh failed, clear auth state
        this.clearAuthState()
      }

      return response
    } catch (error) {
      console.error('Token refresh error:', error)
      this.clearAuthState()
      
      return {
        success: false,
        message: 'Failed to refresh authentication token',
        error: {
          code: 'REFRESH_ERROR',
          details: 'Token refresh failed'
        }
      }
    }
  }

  /**
   * Check the current authentication status
   * @returns Promise with authentication status
   */
  async checkAuthStatus(): Promise<ApiResponse<{ authenticated: boolean }>> {
    try {
      const response = await this.apiService.request<{ authenticated: boolean }>('auth/status', {
        method: 'GET'
      })

      if (response.success && response.data) {
        this._isAuthenticated.value = response.data.authenticated
        
        if (!response.data.authenticated) {
          this.clearAuthState()
        }
      }

      return response
    } catch (error) {
      console.error('Auth status check error:', error)
      this.clearAuthState()
      
      return {
        success: false,
        message: 'Failed to check authentication status',
        error: {
          code: 'STATUS_CHECK_ERROR',
          details: 'Authentication status check failed'
        }
      }
    }
  }
}

// Global authentication service instance
let authServiceInstance: AuthService | null = null

/**
 * Get the authentication service instance (singleton)
 * @returns Authentication service instance
 */
export function useAuthService(): AuthService {
  if (!authServiceInstance) {
    authServiceInstance = new AuthService()
  }
  return authServiceInstance
}

export default AuthService