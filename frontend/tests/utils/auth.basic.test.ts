/**
 * Basic integration tests for Authentication Service
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'

// Mock globals
globalThis.useRuntimeConfig = vi.fn(() => ({
  public: {
    apiBase: '/api/v1'
  }
}))

globalThis.$fetch = vi.fn()
globalThis.ref = vi.fn((value: any) => ({ value }))
globalThis.computed = vi.fn((fn: Function) => ({ value: fn() }))

// Mock localStorage
const mockLocalStorage = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn()
}
Object.defineProperty(globalThis, 'localStorage', {
  value: mockLocalStorage
})

Object.defineProperty(globalThis, 'process', {
  value: { client: true }
})

describe('Authentication Service Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockLocalStorage.getItem.mockReturnValue(null)
  })

  it('should create service instance', async () => {
    const { useAuthService } = await import('~/app/utils/auth')
    const authService = useAuthService()
    
    expect(authService).toBeDefined()
    expect(typeof authService.login).toBe('function')
    expect(typeof authService.logout).toBe('function')
    expect(typeof authService.refreshToken).toBe('function')
    expect(typeof authService.checkAuthStatus).toBe('function')
    expect(typeof authService.saveTokens).toBe('function')
    expect(typeof authService.getTokens).toBe('function')
    expect(typeof authService.saveUser).toBe('function')
  })

  it('should manage tokens correctly', async () => {
    const { useAuthService } = await import('~/app/utils/auth')
    const authService = useAuthService()
    
    const tokens = {
      access_token: 'test-token',
      refresh_token: 'refresh-token',
      token_type: 'Bearer'
    }
    
    authService.saveTokens(tokens)
    
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
      'auth_tokens',
      JSON.stringify(tokens)
    )
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
      'refresh_token',
      'refresh-token'
    )
  })

  it('should manage user data correctly', async () => {
    const { useAuthService } = await import('~/app/utils/auth')
    const authService = useAuthService()
    
    const user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com'
    }
    
    authService.saveUser(user)
    
    expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
      'auth_user',
      JSON.stringify(user)
    )
  })

  it('should return same instance (singleton)', async () => {
    const { useAuthService } = await import('~/app/utils/auth')
    const instance1 = useAuthService()
    const instance2 = useAuthService()
    
    expect(instance1).toBe(instance2)
  })
})