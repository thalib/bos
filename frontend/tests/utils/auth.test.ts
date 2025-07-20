import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// Mock the API service at the module level first
const mockApiService = {
  request: vi.fn(),
  addRequestInterceptor: vi.fn(() => () => {}),
  addResponseInterceptor: vi.fn(() => () => {})
}

vi.mock('../../app/utils/api', () => ({
  useApiService: () => mockApiService
}))

// Now import after mocking
import { useAuthService } from '../../app/utils/auth'

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString()
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    }
  }
})()

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

describe('Authentication Service', () => {
  let authService: ReturnType<typeof useAuthService>

  beforeEach(() => {
    vi.clearAllMocks()
    localStorageMock.clear()
    
    // Reset singleton instance for fresh start
    ;(useAuthService as any).reset()
    
    // Get fresh instance
    authService = useAuthService()
  })

  afterEach(() => {
    localStorageMock.clear()
    vi.clearAllMocks()
  })

  describe('login', () => {
    it('should successfully login and save tokens', async () => {
      const credentials = { email: 'test@example.com', password: 'password123' }
      const mockResponse = {
        success: true,
        message: 'Login successful',
        data: {
          access_token: 'access_token_123',
          refresh_token: 'refresh_token_123',
          user: { id: 1, name: 'Test User', email: 'test@example.com' }
        }
      }

      mockApiService.request.mockResolvedValueOnce(mockResponse)

      const result = await authService.login(credentials)

      expect(mockApiService.request).toHaveBeenCalledWith('/api/v1/auth/login', {
        method: 'POST',
        body: credentials
      })
      expect(result).toEqual(mockResponse)
      expect(authService.isAuthenticated.value).toBe(true)
      expect(localStorageMock.getItem('auth_tokens')).toBeTruthy()
      expect(localStorageMock.getItem('auth_user')).toBeTruthy()
    })

    it('should handle login failure', async () => {
      const credentials = { email: 'wrong@example.com', password: 'wrongpassword' }
      const mockError = new Error('Invalid credentials')
      
      mockApiService.request.mockRejectedValueOnce(mockError)

      await expect(authService.login(credentials)).rejects.toThrow('Invalid credentials')
      expect(authService.isAuthenticated.value).toBe(false)
    })
  })

  describe('logout', () => {
    it('should successfully logout and clear stored data', async () => {
      // Setup authenticated state
      authService.saveTokens({
        accessToken: 'token123',
        refreshToken: 'refresh123'
      })
      authService.saveUser({ id: 1, name: 'Test User', email: 'test@example.com' })

      const mockResponse = {
        success: true,
        message: 'Logout successful'
      }

      mockApiService.request.mockResolvedValueOnce(mockResponse)

      const result = await authService.logout()

      expect(mockApiService.request).toHaveBeenCalledWith('/api/v1/auth/logout', {
        method: 'POST'
      })
      expect(result).toEqual(mockResponse)
      expect(authService.isAuthenticated.value).toBe(false)
      expect(localStorageMock.getItem('auth_tokens')).toBeNull()
      expect(localStorageMock.getItem('auth_user')).toBeNull()
    })

    it('should clear local data even if API call fails', async () => {
      // Setup authenticated state
      authService.saveTokens({
        accessToken: 'token123',
        refreshToken: 'refresh123'
      })

      mockApiService.request.mockRejectedValueOnce(new Error('Network error'))

      await expect(authService.logout()).rejects.toThrow('Network error')
      
      // Should still clear local data
      expect(authService.isAuthenticated.value).toBe(false)
      expect(localStorageMock.getItem('auth_tokens')).toBeNull()
    })
  })

  describe('refreshToken', () => {
    it('should successfully refresh token', async () => {
      const refreshToken = 'refresh_token_123'
      authService.saveTokens({
        accessToken: 'old_token',
        refreshToken
      })

      const mockResponse = {
        success: true,
        message: 'Token refreshed',
        data: {
          access_token: 'new_access_token',
          refresh_token: 'new_refresh_token',
          user: { id: 1, name: 'Test User', email: 'test@example.com' }
        }
      }

      mockApiService.request.mockResolvedValueOnce(mockResponse)

      const result = await authService.refreshToken()

      expect(mockApiService.request).toHaveBeenCalledWith('/api/v1/auth/refresh', {
        method: 'POST',
        body: { refresh_token: refreshToken }
      })
      expect(result).toEqual(mockResponse)
      
      const tokens = authService.getTokens()
      expect(tokens.accessToken).toBe('new_access_token')
      expect(tokens.refreshToken).toBe('new_refresh_token')
    })

    it('should handle refresh failure when no refresh token', async () => {
      // Ensure no tokens are set
      authService.saveTokens({ accessToken: '', refreshToken: '' })
      
      await expect(authService.refreshToken()).rejects.toThrow('No refresh token available')
    })
  })

  describe('checkAuthStatus', () => {
    it('should check authentication status', async () => {
      const mockResponse = {
        success: true,
        message: 'Authenticated',
        data: { authenticated: true }
      }

      mockApiService.request.mockResolvedValueOnce(mockResponse)

      const result = await authService.checkAuthStatus()

      expect(mockApiService.request).toHaveBeenCalledWith('/api/v1/auth/status', {
        method: 'GET'
      })
      expect(result).toEqual(mockResponse)
    })
  })

  describe('token management', () => {
    it('should save and retrieve tokens', () => {
      const tokens = {
        accessToken: 'access_123',
        refreshToken: 'refresh_123'
      }

      authService.saveTokens(tokens)
      const retrievedTokens = authService.getTokens()

      expect(retrievedTokens).toEqual(tokens)
    })

    it('should return empty tokens when none saved', () => {
      // Clear any existing tokens first
      authService.saveTokens({ accessToken: '', refreshToken: '' })
      
      const tokens = authService.getTokens()

      expect(tokens).toEqual({
        accessToken: '',
        refreshToken: ''
      })
    })

    it('should save and retrieve user data', () => {
      const userData = {
        id: 1,
        name: 'Test User',
        email: 'test@example.com'
      }

      authService.saveUser(userData)
      const retrievedUser = authService.getCurrentUser()

      expect(retrievedUser).toEqual(userData)
    })

    it('should return null when no user data saved', () => {
      // Clear any existing user data first
      authService.saveUser(null)
      
      const user = authService.getCurrentUser()
      expect(user).toBeNull()
    })
  })

  describe('initialization', () => {
    it('should initialize authentication state from localStorage', () => {
      // Manually set localStorage data
      localStorageMock.setItem('auth_tokens', JSON.stringify({
        accessToken: 'stored_token',
        refreshToken: 'stored_refresh'
      }))
      localStorageMock.setItem('auth_user', JSON.stringify({
        id: 1,
        name: 'Stored User'
      }))

      // Create new instance to test initialization
      ;(useAuthService as any).reset()
      const newAuthService = useAuthService()

      expect(newAuthService.isAuthenticated.value).toBe(true)
      expect(newAuthService.getCurrentUser()).toEqual({
        id: 1,
        name: 'Stored User'
      })
    })

    it('should handle invalid stored data gracefully', () => {
      // Set invalid JSON data
      localStorageMock.setItem('auth_tokens', 'invalid json')
      localStorageMock.setItem('auth_user', 'invalid json')

      ;(useAuthService as any).reset()
      const newAuthService = useAuthService()

      expect(newAuthService.isAuthenticated.value).toBe(false)
      expect(newAuthService.getCurrentUser()).toBeNull()
    })
  })

  describe('reactive authentication state', () => {
    it('should update isAuthenticated when tokens change', () => {
      expect(authService.isAuthenticated.value).toBe(false)

      authService.saveTokens({
        accessToken: 'token123',
        refreshToken: 'refresh123'
      })

      expect(authService.isAuthenticated.value).toBe(true)

      authService.saveTokens({
        accessToken: '',
        refreshToken: ''
      })

      expect(authService.isAuthenticated.value).toBe(false)
    })
  })

  describe('interceptors setup', () => {
    it('should set up request interceptor to add auth headers', () => {
      expect(mockApiService.addRequestInterceptor).toHaveBeenCalledWith(
        expect.any(Function)
      )
    })

    it('should set up response interceptor for token refresh', () => {
      expect(mockApiService.addResponseInterceptor).toHaveBeenCalledWith(
        expect.any(Function)
      )
    })
  })
})