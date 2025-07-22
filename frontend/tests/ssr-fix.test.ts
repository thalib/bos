import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useAuthService } from '../app/utils/auth'

// Mock import.meta.client for SSR testing
const mockImportMeta = {
  client: false
}

vi.stubGlobal('import.meta', mockImportMeta)

describe('SSR Fix - Auth Service', () => {
  beforeEach(() => {
    // Reset auth service instance
    ;(useAuthService as any).reset()
  })

  it('should initialize without localStorage errors on server side', () => {
    // Simulate server-side environment
    mockImportMeta.client = false
    
    // This should not throw localStorage errors
    expect(() => {
      const authService = useAuthService()
      expect(authService.isInitialized.value).toBe(true)
      expect(authService.isAuthenticated.value).toBe(false)
    }).not.toThrow()
  })

  it('should handle token saving safely on server side', () => {
    mockImportMeta.client = false
    
    const authService = useAuthService()
    
    // This should not throw localStorage errors
    expect(() => {
      authService.saveTokens({
        accessToken: 'test-token',
        refreshToken: 'test-refresh'
      })
    }).not.toThrow()
    
    // Tokens should still be stored in memory
    expect(authService.getTokens().accessToken).toBe('test-token')
  })

  it('should handle user data saving safely on server side', () => {
    mockImportMeta.client = false
    
    const authService = useAuthService()
    
    // This should not throw localStorage errors
    expect(() => {
      authService.saveUser({
        id: 1,
        name: 'Test User',
        email: 'test@example.com'
      })
    }).not.toThrow()
    
    // User should still be stored in memory
    expect(authService.getCurrentUser()?.name).toBe('Test User')
  })
})