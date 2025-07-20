import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mockNavigateTo } from '../setup-test'

// Mock the auth service
const mockAuthService = {
  isAuthenticated: { value: false },
  isInitialized: { value: true }
}

vi.mock('../../app/utils/auth', () => ({
  useAuthService: () => mockAuthService
}))

describe('Auth Middleware', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockAuthService.isAuthenticated.value = false
    mockAuthService.isInitialized.value = true
    
    // Mock process.client
    Object.defineProperty(process, 'client', {
      value: true,
      writable: true
    })
  })

  it('should redirect unauthenticated users to login page', async () => {
    const { default: authMiddleware } = await import('../../app/middleware/auth')

    const to = { path: '/dashboard', fullPath: '/dashboard' }
    const from = { path: '/' }

    const result = await authMiddleware(to as any, from as any)

    expect(mockNavigateTo).toHaveBeenCalledWith({
      path: '/',
      query: { redirect: '/dashboard' }
    })
  })

  it('should allow authenticated users to access protected routes', async () => {
    mockAuthService.isAuthenticated.value = true

    const { default: authMiddleware } = await import('../../app/middleware/auth')

    const to = { path: '/dashboard', fullPath: '/dashboard' }
    const from = { path: '/' }

    const result = await authMiddleware(to as any, from as any)

    expect(mockNavigateTo).not.toHaveBeenCalled()
  })

  it('should allow access to login page even when unauthenticated', async () => {
    mockAuthService.isAuthenticated.value = false

    const { default: authMiddleware } = await import('../../app/middleware/auth')

    const to = { path: '/', fullPath: '/' }
    const from = { path: '/dashboard' }

    const result = await authMiddleware(to as any, from as any)

    expect(mockNavigateTo).not.toHaveBeenCalled()
  })

  it('should skip middleware on server side', async () => {
    Object.defineProperty(process, 'client', {
      value: false,
      writable: true
    })

    const { default: authMiddleware } = await import('../../app/middleware/auth')

    const to = { path: '/dashboard', fullPath: '/dashboard' }
    const from = { path: '/' }

    const result = authMiddleware(to as any, from as any)

    expect(result).toBeUndefined()
    expect(mockNavigateTo).not.toHaveBeenCalled()
  })

  it('should wait for auth initialization', async () => {
    mockAuthService.isInitialized.value = false
    
    const { default: authMiddleware } = await import('../../app/middleware/auth')

    const to = { path: '/dashboard', fullPath: '/dashboard' }
    const from = { path: '/' }

    // Start the middleware
    const middlewarePromise = authMiddleware(to as any, from as any)

    // Should not have redirected yet
    expect(mockNavigateTo).not.toHaveBeenCalled()

    // Simulate auth initialization completing
    setTimeout(() => {
      mockAuthService.isInitialized.value = true
    }, 5)

    await middlewarePromise

    expect(mockNavigateTo).toHaveBeenCalledWith({
      path: '/',
      query: { redirect: '/dashboard' }
    })
  })
})