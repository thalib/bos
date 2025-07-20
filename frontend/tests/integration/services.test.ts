import { describe, it, expect, vi } from 'vitest'
import { useApiService } from '../../app/utils/api'
import { useAuthService } from '../../app/utils/auth'

// Mock fetch for integration test
global.fetch = vi.fn()

describe('Services Integration', () => {
  it('should demonstrate API and Auth services working together', async () => {
    const mockFetch = global.fetch as any
    const apiService = useApiService()
    const authService = useAuthService()

    // Mock login response
    const loginResponse = {
      success: true,
      message: 'Login successful',
      data: {
        access_token: 'test_access_token',
        refresh_token: 'test_refresh_token',
        user: { id: 1, name: 'Test User', email: 'test@example.com' }
      }
    }

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: () => Promise.resolve(loginResponse)
    })

    // Login user
    await authService.login({
      email: 'test@example.com',
      password: 'password123'
    })

    expect(authService.isAuthenticated.value).toBe(true)
    expect(authService.getCurrentUser()?.name).toBe('Test User')

    // Mock API call that should include auth headers
    const protectedResponse = {
      success: true,
      message: 'Data retrieved',
      data: [{ id: 1, title: 'Protected Data' }]
    }

    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: () => Promise.resolve(protectedResponse)
    })

    // Make authenticated API call
    await apiService.fetch('protected-resource')

    // Verify the last call included the Authorization header
    const lastCall = mockFetch.mock.calls[mockFetch.mock.calls.length - 1]
    expect(lastCall[1].headers).toHaveProperty('Authorization', 'Bearer test_access_token')

    // Mock logout response
    mockFetch.mockResolvedValueOnce({
      ok: true,
      status: 200,
      json: () => Promise.resolve({ success: true, message: 'Logged out' })
    })

    // Logout
    await authService.logout()

    expect(authService.isAuthenticated.value).toBe(false)
    expect(authService.getCurrentUser()).toBeNull()
  })
})