/**
 * Basic integration tests for API Service
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'

// Mock globals
globalThis.useRuntimeConfig = vi.fn(() => ({
  public: {
    apiBase: '/api/v1'
  }
}))

globalThis.$fetch = vi.fn()

describe('API Service Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should create service instance', async () => {
    const { useApiService } = await import('~/app/utils/api')
    const apiService = useApiService()
    
    expect(apiService).toBeDefined()
    expect(typeof apiService.request).toBe('function')
    expect(typeof apiService.fetch).toBe('function')
    expect(typeof apiService.get).toBe('function')
    expect(typeof apiService.create).toBe('function')
    expect(typeof apiService.update).toBe('function')
    expect(typeof apiService.delete).toBe('function')
  })

  it('should build URLs correctly', async () => {
    const { useApiService } = await import('~/app/utils/api')
    const apiService = useApiService()
    
    const url1 = apiService.buildUrl('users')
    expect(url1).toBe('/api/v1/users')
    
    const url2 = apiService.buildUrl('users', { page: 1, limit: 10 })
    expect(url2).toBe('/api/v1/users?page=1&limit=10')
  })

  it('should handle errors correctly', async () => {
    const { useApiService } = await import('~/app/utils/api')
    const apiService = useApiService()
    
    const error = { message: 'Network error' }
    const result = apiService.handleError(error)
    
    expect(result).toEqual({
      code: 'NETWORK_ERROR',
      details: 'Network error'
    })
  })

  it('should return same instance (singleton)', async () => {
    const { useApiService } = await import('~/app/utils/api')
    const instance1 = useApiService()
    const instance2 = useApiService()
    
    expect(instance1).toBe(instance2)
  })
})