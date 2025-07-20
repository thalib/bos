// setup-test.ts
import { vi } from 'vitest'

// Setup global mocks for Nuxt functions
const mockNavigateTo = vi.fn()
const mockNextTick = vi.fn((callback) => {
  callback()
  return Promise.resolve()
})
const mockDefineNuxtRouteMiddleware = vi.fn((middleware) => middleware)

// Make these globally available
globalThis.navigateTo = mockNavigateTo
globalThis.nextTick = mockNextTick
globalThis.defineNuxtRouteMiddleware = mockDefineNuxtRouteMiddleware

// Export for test access
export { mockNavigateTo, mockNextTick, mockDefineNuxtRouteMiddleware }