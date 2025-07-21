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
          totalItems: 1,
          currentPage: 1,
          itemsPerPage: 10,
          totalPages: 1,
          urlPath: '/api/v1/users',
          urlQuery: 'page=1&per_page=10',
          nextPage: null,
          prevPage: null
        }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.fetch('users', { page: 1, per_page: 10 })

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/v1/users?page=1&per_page=10',
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

  describe('notifications handling', () => {
    it('should merge parameter validation notifications with response notifications', async () => {
      const mockResponse = {
        success: true,
        message: 'Success',
        data: [{ id: 1, name: 'Item 1' }],
        pagination: {
          totalItems: 1,
          currentPage: 1,
          itemsPerPage: 10,
          totalPages: 1,
          urlPath: '/api/v1/users',
          urlQuery: null,
          nextPage: null,
          prevPage: null
        },
        notifications: [
          { type: 'info', message: 'Server notification' }
        ]
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      // Use invalid parameters to trigger validation notifications
      const result = await apiService.fetch('users', { page: 0, per_page: 150 })

      expect(result.notifications).toEqual([
        { type: 'info', message: 'Server notification' },
        { type: 'warning', message: "Invalid page number '0', using page 1" },
        { type: 'warning', message: "Page size '150' exceeds maximum of 100, using maximum 100" }
      ])
    })

    it('should add validation notifications when no response notifications exist', async () => {
      const mockResponse = {
        success: true,
        message: 'Success',
        data: [{ id: 1, name: 'Item 1' }],
        pagination: {
          totalItems: 1,
          currentPage: 1,
          itemsPerPage: 10,
          totalPages: 1,
          urlPath: '/api/v1/users',
          urlQuery: null,
          nextPage: null,
          prevPage: null
        }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.fetch('users', { page: -1 })

      expect(result.notifications).toEqual([
        { type: 'warning', message: "Invalid page number '-1', using page 1" }
      ])
    })

    it('should not add notifications array when no validation issues occur', async () => {
      const mockResponse = {
        success: true,
        message: 'Success',
        data: [{ id: 1, name: 'Item 1' }],
        pagination: {
          totalItems: 1,
          currentPage: 1,
          itemsPerPage: 10,
          totalPages: 1,
          urlPath: '/api/v1/users',
          urlQuery: null,
          nextPage: null,
          prevPage: null
        }
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockResponse)
      })

      const result = await apiService.fetch('users', { page: 1, per_page: 10 })

      expect(result.notifications).toBeUndefined()
    })
  })

  describe('utility methods', () => {
    it('should build URLs with valid parameters', () => {
      const result = apiService.buildUrl('users', { page: 1, filter: 'status:active' })
      expect(result.url).toBe('/api/v1/users?page=1&filter=status%3Aactive')
      expect(result.notifications).toEqual([])
    })

    it('should validate and provide fallbacks for invalid parameters', () => {
      const result = apiService.buildUrl('users', { 
        page: 0, 
        per_page: 150, 
        dir: 'invalid',
        search: 'a',
        filter: 'invalid_format'
      })
      
      expect(result.url).toBe('/api/v1/users?page=1&per_page=100&dir=asc')
      expect(result.notifications).toEqual([
        { type: 'warning', message: "Invalid page number '0', using page 1" },
        { type: 'warning', message: "Page size '150' exceeds maximum of 100, using maximum 100" },
        { type: 'warning', message: "Sort direction 'invalid' not recognized, using 'asc'" },
        { type: 'warning', message: 'Search term too short (minimum 2 characters), search ignored' },
        { type: 'warning', message: "Filter format 'invalid_format' not recognized, filter ignored" }
      ])
    })

    it('should handle negative per_page values', () => {
      const result = apiService.buildUrl('users', { per_page: -5 })
      expect(result.url).toBe('/api/v1/users?per_page=1')
      expect(result.notifications).toEqual([
        { type: 'warning', message: "Page size '-5' below minimum of 1, using minimum 1" }
      ])
    })

    it('should pass through valid filter format', () => {
      const result = apiService.buildUrl('users', { filter: 'status:active' })
      expect(result.url).toBe('/api/v1/users?filter=status%3Aactive')
      expect(result.notifications).toEqual([])
    })

    it('should pass through valid search terms', () => {
      const result = apiService.buildUrl('users', { search: 'mobile' })
      expect(result.url).toBe('/api/v1/users?search=mobile')
      expect(result.notifications).toEqual([])
    })

    it('should handle error properly', () => {
      const error = new Error('Network error')
      const processedError = apiService.handleError(error)
      
      expect(processedError).toHaveProperty('message')
      expect(processedError).toHaveProperty('code')
    })
  })
})