// Type definitions
export interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
  headers?: Record<string, string>
  body?: any
  params?: Record<string, any>
  signal?: AbortSignal
  responseType?: 'json' | 'text' | 'blob'
}

export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
  pagination?: PaginationInfo
  error?: {
    code: string
    details: string
  }
}

export interface PaginatedResponse<T = any> {
  data: T[]
  pagination: PaginationInfo
}

export interface PaginationInfo {
  current_page: number
  per_page: number
  total: number
  last_page?: number
  from?: number
  to?: number
}

export interface PaginationParams {
  page?: number
  limit?: number
  per_page?: number
  sort?: string
  order?: 'asc' | 'desc'
  search?: string
  [key: string]: any
}

export interface ApiError {
  message: string
  code: string
  details?: string
  status?: number
}

export type RequestInterceptor = (config: RequestOptions & { url: string }) => RequestOptions & { url: string }
export type ResponseInterceptor = (response: ApiResponse) => ApiResponse

// API Service implementation
class ApiService {
  private baseURL = ''
  private requestInterceptors: RequestInterceptor[] = []
  private responseInterceptors: ResponseInterceptor[] = []

  /**
   * Generic request method for API calls
   */
  async request<T = any>(url: string, options: RequestOptions = {}): Promise<ApiResponse<T>> {
    try {
      // Prepare request config
      let config: RequestOptions & { url: string } = {
        url,
        method: options.method || 'GET',
        headers: {
          'Content-Type': 'application/json',
          ...options.headers
        },
        ...options
      }

      // Apply request interceptors
      for (const interceptor of this.requestInterceptors) {
        config = interceptor(config)
      }

      // Prepare fetch options
      const fetchOptions: RequestInit = {
        method: config.method,
        headers: config.headers,
        signal: config.signal
      }

      // Add body for POST/PUT/PATCH requests
      if (config.body && ['POST', 'PUT', 'PATCH'].includes(config.method!)) {
        fetchOptions.body = typeof config.body === 'string' ? config.body : JSON.stringify(config.body)
      }

      // Add query parameters to URL
      let requestUrl = config.url
      if (config.params) {
        const params = new URLSearchParams(config.params).toString()
        requestUrl += (requestUrl.includes('?') ? '&' : '?') + params
      }

      // Make the request
      const response = await fetch(requestUrl, fetchOptions)
      let data: ApiResponse<T>

      // Parse response based on responseType
      if (config.responseType === 'text') {
        const textData = await response.text()
        data = { success: response.ok, message: 'Success', data: textData as T }
      } else if (config.responseType === 'blob') {
        const blobData = await response.blob()
        data = { success: response.ok, message: 'Success', data: blobData as T }
      } else {
        data = await response.json()
      }

      // Apply response interceptors
      for (const interceptor of this.responseInterceptors) {
        data = interceptor(data)
      }

      // Handle HTTP errors
      if (!response.ok) {
        throw this.createError(data, response.status)
      }

      return data
    } catch (error) {
      if (error instanceof Error && error.name === 'ApiError') {
        throw error
      }
      throw this.handleError(error)
    }
  }

  /**
   * Fetch a list of resources with pagination
   */
  async fetch<T = any>(resource: string, params?: PaginationParams): Promise<ApiResponse<PaginatedResponse<T>>> {
    const url = this.buildUrl(resource, params)
    return this.request<PaginatedResponse<T>>(url)
  }

  /**
   * Get a single resource by ID
   */
  async get<T = any>(resource: string, id: string | number): Promise<ApiResponse<T>> {
    const url = `/api/v1/${resource}/${id}`
    return this.request<T>(url)
  }

  /**
   * Create a new resource
   */
  async create<T = any, D = any>(resource: string, data: D): Promise<ApiResponse<T>> {
    const url = `/api/v1/${resource}`
    return this.request<T>(url, {
      method: 'POST',
      body: data
    })
  }

  /**
   * Update an existing resource
   */
  async update<T = any, D = any>(resource: string, id: string | number, data: D): Promise<ApiResponse<T>> {
    const url = `/api/v1/${resource}/${id}`
    return this.request<T>(url, {
      method: 'PUT',
      body: data
    })
  }

  /**
   * Delete a resource
   */
  async delete<T = any>(resource: string, id: string | number): Promise<ApiResponse<T>> {
    const url = `/api/v1/${resource}/${id}`
    return this.request<T>(url, {
      method: 'DELETE'
    })
  }

  /**
   * Add a request interceptor
   */
  addRequestInterceptor(interceptor: RequestInterceptor): () => void {
    this.requestInterceptors.push(interceptor)
    return () => {
      const index = this.requestInterceptors.indexOf(interceptor)
      if (index > -1) {
        this.requestInterceptors.splice(index, 1)
      }
    }
  }

  /**
   * Add a response interceptor
   */
  addResponseInterceptor(interceptor: ResponseInterceptor): () => void {
    this.responseInterceptors.push(interceptor)
    return () => {
      const index = this.responseInterceptors.indexOf(interceptor)
      if (index > -1) {
        this.responseInterceptors.splice(index, 1)
      }
    }
  }

  /**
   * Build URL with query parameters
   */
  buildUrl(resource: string, params?: Record<string, any>): string {
    let url = `/api/v1/${resource}`
    
    if (params) {
      const queryParams = new URLSearchParams()
      
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          queryParams.append(key, String(value))
        }
      })
      
      const queryString = queryParams.toString()
      if (queryString) {
        url += `?${queryString}`
      }
    }
    
    return url
  }

  /**
   * Handle and format API errors
   */
  handleError(error: any): ApiError {
    const apiError: ApiError = {
      message: 'An unexpected error occurred',
      code: 'UNKNOWN_ERROR'
    }

    if (error instanceof Error) {
      apiError.message = error.message
      apiError.details = error.stack
    } else if (typeof error === 'string') {
      apiError.message = error
    } else if (error && typeof error === 'object') {
      apiError.message = error.message || apiError.message
      apiError.code = error.code || apiError.code
      apiError.details = error.details
      apiError.status = error.status
    }

    // Create a proper Error object
    const finalError = new Error(apiError.message)
    finalError.name = 'ApiError'
    Object.assign(finalError, apiError)
    
    return finalError as any
  }

  /**
   * Create API error from response
   */
  private createError(data: ApiResponse, status: number): ApiError {
    const error: ApiError = {
      message: data.message || 'Request failed',
      code: data.error?.code || 'REQUEST_FAILED',
      details: data.error?.details,
      status
    }

    const apiError = new Error(error.message)
    apiError.name = 'ApiError'
    Object.assign(apiError, error)
    
    return apiError as any
  }
}

// Create singleton instance
let apiServiceInstance: ApiService | null = null

/**
 * Composable to access the API service
 */
export function useApiService(): ApiService {
  if (!apiServiceInstance) {
    apiServiceInstance = new ApiService()
  }
  return apiServiceInstance
}

// Export for direct use if needed
export { ApiService }