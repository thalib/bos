import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { useApiService } from '../../app/utils/api'

// Mock global fetch
global.fetch = vi.fn()

describe('API Service', () => {
  const mockFetch = global.fetch as any
  let apiService: ReturnType<typeof useApiService>

  beforeEach(() => {
    mockFetch.mockClear()
    apiService = useApiService()
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  describe('request method', () => {
    it('should make a basic GET request', async () => {
      const mockResponse = {
        success: true,
        message: 'Success',
        data: { id: 1, name: 'Test' }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.request('/api/test')

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/test',
        expect.objectContaining({
          method: 'GET',
          headers: expect.objectContaining({
            'Content-Type': 'application/json'
          })
        })
      )
      expect(result).toEqual(mockResponse)
    })

    it('should make a POST request with data', async () => {
      const requestData = { name: 'New Item' }
      const mockResponse = {
        success: true,
        message: 'Created',
        data: { id: 2, name: 'New Item' }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 201,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.request('/api/test', {
        method: 'POST',
        body: requestData
      })

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/test',
        expect.objectContaining({
          method: 'POST',
          headers: expect.objectContaining({
            'Content-Type': 'application/json'
          }),
          body: JSON.stringify(requestData)
        })
      )
      expect(result).toEqual(mockResponse)
    })

    it('should handle request errors', async () => {
      const errorResponse = {
        success: false,
        message: 'Not Found',
        error: {
          code: 'NOT_FOUND',
          details: 'Resource not found'
        }
      }

      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 404,
        json: () => Promise.resolve(errorResponse)
      })

      await expect(apiService.request('/api/notfound')).rejects.toThrow()
    })
  })

  describe('CRUD methods', () => {
    it('should fetch resources with pagination', async () => {
      const mockResponse = {
        success: true,
        message: 'Success',
        data: [{ id: 1, name: 'Item 1' }],
        pagination: {
          current_page: 1,
          per_page: 10,
          total: 1
        }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.fetch('users', { page: 1, limit: 10 })

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/users?page=1&limit=10',
        expect.any(Object)
      )
      expect(result).toEqual(mockResponse)
    })

    it('should get a single resource', async () => {
      const mockResponse = {
        success: true,
        message: 'Success',
        data: { id: 1, name: 'User 1' }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.get('users', 1)

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/users/1',
        expect.any(Object)
      )
      expect(result).toEqual(mockResponse)
    })

    it('should create a new resource', async () => {
      const createData = { name: 'New User', email: 'user@example.com' }
      const mockResponse = {
        success: true,
        message: 'Created',
        data: { id: 2, ...createData }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 201,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.create('users', createData)

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/users',
        expect.objectContaining({
          method: 'POST',
          body: JSON.stringify(createData)
        })
      )
      expect(result).toEqual(mockResponse)
    })

    it('should update an existing resource', async () => {
      const updateData = { name: 'Updated User' }
      const mockResponse = {
        success: true,
        message: 'Updated',
        data: { id: 1, name: 'Updated User' }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.update('users', 1, updateData)

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/users/1',
        expect.objectContaining({
          method: 'PUT',
          body: JSON.stringify(updateData)
        })
      )
      expect(result).toEqual(mockResponse)
    })

    it('should delete a resource', async () => {
      const mockResponse = {
        success: true,
        message: 'Deleted'
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.delete('users', 1)

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/users/1',
        expect.objectContaining({
          method: 'DELETE'
        })
      )
      expect(result).toEqual(mockResponse)
    })
  })

  describe('interceptors', () => {
    it('should support request interceptors', async () => {
      let interceptorCalled = false
      const removeInterceptor = apiService.addRequestInterceptor((config) => {
        interceptorCalled = true
        config.headers = { ...config.headers, 'X-Custom': 'test' }
        return config
      })

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ success: true })
      })

      await apiService.request('/api/test')

      expect(interceptorCalled).toBe(true)
      expect(mockFetch).toHaveBeenCalledWith(
        '/api/test',
        expect.objectContaining({
          headers: expect.objectContaining({
            'X-Custom': 'test'
          })
        })
      )

      removeInterceptor()
    })

    it('should support response interceptors', async () => {
      let interceptorCalled = false
      const removeInterceptor = apiService.addResponseInterceptor((response) => {
        interceptorCalled = true
        return { ...response, intercepted: true }
      })

      const mockResponse = { success: true, data: 'test' }
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.request('/api/test')

      expect(interceptorCalled).toBe(true)
      expect(result).toEqual({ ...mockResponse, intercepted: true })

      removeInterceptor()
    })
  })

  describe('utility methods', () => {
    it('should build URLs with parameters', () => {
      const url = apiService.buildUrl('users', { page: 1, filter: 'active' })
      expect(url).toBe('/api/v1/users?page=1&filter=active')
    })

    it('should handle error properly', () => {
      const error = new Error('Network error')
      const processedError = apiService.handleError(error)
      
      expect(processedError).toHaveProperty('message')
      expect(processedError).toHaveProperty('code')
    })
  })
})