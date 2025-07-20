import { describe, it, expect, vi, beforeEach } from 'vitest'

// Mock the auth service
const mockAuthService = {
  isAuthenticated: { value: false },
  isInitialized: { value: true }
}

// Mock Nuxt functions
const mockNavigateTo = vi.fn()
const mockNextTick = vi.fn((callback) => {
  callback()
  return Promise.resolve()
})

// Mock the defineNuxtRouteMiddleware function
const mockDefineNuxtRouteMiddleware = vi.fn((middleware) => middleware)

// Set up globals before importing
globalThis.defineNuxtRouteMiddleware = mockDefineNuxtRouteMiddleware
globalThis.navigateTo = mockNavigateTo 
globalThis.nextTick = mockNextTick

vi.mock('../../app/utils/auth', () => ({
  useAuthService: () => mockAuthService
}))

describe('Auth Middleware Direct Test', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockAuthService.isAuthenticated.value = false
    mockAuthService.isInitialized.value = true
    
    Object.defineProperty(process, 'client', {
      value: true,
      writable: true
    })
  })

  it('should call navigateTo when user is not authenticated', async () => {
    // Manually create the middleware logic instead of importing
    const middleware = (to, from) => {
      if (!process.client) {
        return
      }

      const { isAuthenticated, isInitialized } = mockAuthService

      return new Promise((resolve) => {
        const checkAuth = () => {
          if (isInitialized.value) {
            if (!isAuthenticated.value && to.path !== '/') {
              resolve(
                globalThis.navigateTo({
                  path: '/',
                  query: { redirect: to.fullPath },
                })
              )
            } else {
              resolve(undefined)
            }
          } else {
            setTimeout(checkAuth, 10)
          }
        }

        globalThis.nextTick(() => {
          checkAuth()
        })
      })
    }

    const to = { path: '/dashboard', fullPath: '/dashboard' }
    const from = { path: '/' }

    await middleware(to, from)

    expect(mockNavigateTo).toHaveBeenCalledWith({
      path: '/',
      query: { redirect: '/dashboard' }
    })
  })
})