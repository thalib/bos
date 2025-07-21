import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// Mock toastr before importing anything else
vi.mock('toastr', () => ({
  default: {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    clear: vi.fn(),
    options: {}
  }
}))

vi.mock('toastr/build/toastr.min.css', () => ({}))

// Mock global fetch
global.fetch = vi.fn()

// Now import the services after mocking
import { useApiService } from '../../app/utils/api'
import { useAuthService } from '../../app/utils/auth'
import { useNotifyService } from '../../app/utils/notify'

describe('Notify Service Integration', () => {
  const mockFetch = global.fetch as any
  let apiService: ReturnType<typeof useApiService>
  let authService: ReturnType<typeof useAuthService>
  let notifyService: ReturnType<typeof useNotifyService>

  beforeEach(() => {
    // Reset all mocks
    vi.clearAllMocks()
    mockFetch.mockClear()

    // Reset service instances
    ;(useApiService as any).reset?.()
    ;(useAuthService as any).reset?.()
    ;(useNotifyService as any).reset?.()

    // Create fresh instances
    apiService = useApiService()
    authService = useAuthService()
    notifyService = useNotifyService()
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  describe('API Service Integration', () => {
    it('should display notifications from successful API response', async () => {
      const mockResponse = {
        success: true,
        message: 'Success',
        data: { id: 1, name: 'Test' },
        notifications: [
          { type: 'info' as const, message: 'Data loaded successfully' },
          { type: 'warning' as const, message: 'Some items were filtered' }
        ]
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      // Spy on the toastr methods directly since they're called by the service
      const toastr = (await vi.importMock('toastr')).default as any
      
      await apiService.request('/api/test')

      expect(toastr.info).toHaveBeenCalledWith('Data loaded successfully', '')
      expect(toastr.warning).toHaveBeenCalledWith('Some items were filtered', '')
    })

    it('should display error notification for failed API response', async () => {
      const mockErrorResponse = {
        success: false,
        message: 'Request failed',
        error: {
          code: 'VALIDATION_ERROR',
          details: ['Email is required']
        }
      }

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 400,
        json: () => Promise.resolve(mockErrorResponse)
      })

      const toastr = (await vi.importMock('toastr')).default as any

      try {
        await apiService.request('/api/test')
      } catch (error) {
        // Expected to throw
      }

      expect(toastr.error).toHaveBeenCalledWith('Request failed', 'API Error')
    })

    it('should display network error notification for fetch failures', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      const toastr = (await vi.importMock('toastr')).default as any

      try {
        await apiService.request('/api/test')
      } catch (error) {
        // Expected to throw
      }

      expect(toastr.error).toHaveBeenCalledWith('Network error', 'Network Error')
    })

    it('should include validation warnings from buildUrl in response', async () => {
      const mockResponse = {
        success: true,
        message: 'Success',
        data: { data: [], pagination: {} }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      // This should trigger validation warnings
      const response = await apiService.fetch('users', { page: -1, per_page: 150 })

      // Check that validation notifications were added to the response
      expect(response.notifications).toEqual(
        expect.arrayContaining([
          expect.objectContaining({
            type: 'warning',
            message: 'Invalid page number \'-1\', using page 1'
          }),
          expect.objectContaining({
            type: 'warning',
            message: 'Page size \'150\' exceeds maximum of 100, using maximum 100'
          })
        ])
      )
    })
  })

  describe('Auth Service Integration', () => {
    it('should display success notification on successful login', async () => {
      const mockLoginResponse = {
        success: true,
        message: 'Login successful',
        data: {
          access_token: 'test-token',
          refresh_token: 'refresh-token',
          user: { id: 1, name: 'John Doe', email: 'john@example.com' }
        }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockLoginResponse)
      })

      const toastr = (await vi.importMock('toastr')).default as any

      await authService.login({ email: 'john@example.com', password: 'password' })

      expect(toastr.success).toHaveBeenCalledWith('Welcome back, John Doe!', 'Login Successful')
    })

    it('should display error notification on failed login', async () => {
      const mockErrorResponse = {
        success: false,
        message: 'Invalid credentials',
        error: { code: 'INVALID_CREDENTIALS', details: [] }
      }

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: () => Promise.resolve(mockErrorResponse)
      })

      const toastr = (await vi.importMock('toastr')).default as any

      try {
        await authService.login({ email: 'john@example.com', password: 'wrong' })
      } catch (error) {
        // Expected to throw
      }

      expect(toastr.error).toHaveBeenCalledWith('Invalid credentials', 'API Error')
      expect(toastr.error).toHaveBeenCalledWith(
        'Invalid credentials. Please check your email and password.',
        'Login Failed'
      )
    })

    it('should display success notification on successful logout', async () => {
      const mockLogoutResponse = {
        success: true,
        message: 'Logged out successfully'
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockLogoutResponse)
      })

      const toastr = (await vi.importMock('toastr')).default as any

      await authService.logout()

      expect(toastr.success).toHaveBeenCalledWith(
        'You have been logged out successfully.',
        'Logged Out'
      )
    })

    it('should display warning notification when logout API fails', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      const toastr = (await vi.importMock('toastr')).default as any

      try {
        await authService.logout()
      } catch (error) {
        // Expected to throw
      }

      expect(toastr.warning).toHaveBeenCalledWith(
        'Logout request failed, but you have been logged out locally.',
        'Logout Warning'
      )
    })

    it('should display info notification on successful token refresh', async () => {
      // Set up existing tokens
      authService.saveTokens({
        accessToken: 'old-token',
        refreshToken: 'refresh-token'
      })

      const mockRefreshResponse = {
        success: true,
        message: 'Token refreshed',
        data: {
          access_token: 'new-token',
          refresh_token: 'new-refresh-token',
          user: { id: 1, name: 'John Doe', email: 'john@example.com' }
        }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockRefreshResponse)
      })

      const toastr = (await vi.importMock('toastr')).default as any

      await authService.refreshToken()

      expect(toastr.info).toHaveBeenCalledWith(
        'Session refreshed successfully.',
        'Session Updated'
      )
    })

    it('should display error notification when token refresh fails', async () => {
      // Set up existing tokens
      authService.saveTokens({
        accessToken: 'old-token',
        refreshToken: 'invalid-refresh-token'
      })

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: () => Promise.resolve({
          success: false,
          message: 'Invalid refresh token'
        })
      })

      const toastr = (await vi.importMock('toastr')).default as any

      try {
        await authService.refreshToken()
      } catch (error) {
        // Expected to throw
      }

      expect(toastr.error).toHaveBeenCalledWith(
        'Session expired. Please log in again.',
        'Session Expired'
      )
    })
  })
})