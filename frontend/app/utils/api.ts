/**
 * API Service Layer
 * 
 * Centralized service for handling all HTTP requests in the Nuxt 4 application.
 * Provides standardized response handling, error management, and CRUD operations.
 */

// Types and Interfaces
export interface RequestOptions {
  method?: string
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
  pagination?: PaginationMeta
  error?: ApiError
}

export interface ApiError {
  code: string
  details: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number
  to?: number
}

export interface PaginatedResponse<T> {
  data: T[]
  pagination: PaginationMeta
}

export interface PaginationParams {
  page?: number
  per_page?: number
  sort?: string
  order?: 'asc' | 'desc'
  search?: string
  [key: string]: any
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  user: User
  tokens: AuthTokens
}

export interface AuthTokens {
  access_token: string
  refresh_token?: string
  token_type: string
  expires_at?: string
}

export interface User {
  id: number
  name: string
  email: string
  [key: string]: any
}

// Request and Response Interceptors
export type RequestInterceptor = (config: RequestOptions) => RequestOptions | Promise<RequestOptions>
export type ResponseInterceptor = (response: any) => any | Promise<any>

class ApiService {
  private baseUrl: string
  private requestInterceptors: RequestInterceptor[] = []
  private responseInterceptors: ResponseInterceptor[] = []

  constructor() {
    // Get base URL from runtime config or environment
    const config = useRuntimeConfig()
    this.baseUrl = config.public?.apiBase || '/api/v1'
  }

  /**
   * Add a request interceptor
   * @param interceptor The interceptor function
   * @returns Function to remove the interceptor
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
   * @param interceptor The interceptor function
   * @returns Function to remove the interceptor
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
   * @param resource The resource path
   * @param params Query parameters
   * @returns Complete URL string
   */
  buildUrl(resource: string, params?: Record<string, any>): string {
    const url = new URL(`${this.baseUrl}/${resource}`, 'http://localhost')
    
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          url.searchParams.append(key, String(value))
        }
      })
    }
    
    return url.pathname + url.search
  }

  /**
   * Handle and format API errors
   * @param error The error object
   * @returns Formatted API error
   */
  handleError(error: any): ApiError {
    console.error('API Error:', error)
    
    // Handle different error types
    if (error?.data) {
      return {
        code: error.data.error?.code || 'API_ERROR',
        details: error.data.message || 'An unexpected error occurred'
      }
    }
    
    if (error?.message) {
      return {
        code: 'NETWORK_ERROR',
        details: error.message
      }
    }
    
    return {
      code: 'UNKNOWN_ERROR',
      details: 'An unexpected error occurred'
    }
  }

  /**
   * Generic API request method
   * @param url The request URL
   * @param options Request options
   * @returns Promise with standardized API response
   */
  async request<T = any>(url: string, options: RequestOptions = {}): Promise<ApiResponse<T>> {
    try {
      // Apply request interceptors
      let config = { ...options }
      for (const interceptor of this.requestInterceptors) {
        config = await interceptor(config)
      }

      // Prepare fetch options
      const fetchOptions: any = {
        method: config.method || 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...config.headers
        }
      }

      // Add body for non-GET requests
      if (config.body && config.method !== 'GET') {
        fetchOptions.body = typeof config.body === 'string' 
          ? config.body 
          : JSON.stringify(config.body)
      }

      // Add signal for cancellation
      if (config.signal) {
        fetchOptions.signal = config.signal
      }

      // Build final URL with query params
      const finalUrl = config.params 
        ? this.buildUrl(url, config.params)
        : url.startsWith('http') ? url : `${this.baseUrl}/${url}`

      // Make the request
      const response = await $fetch<ApiResponse<T>>(finalUrl, fetchOptions)

      // Apply response interceptors
      let processedResponse = response
      for (const interceptor of this.responseInterceptors) {
        processedResponse = await interceptor(processedResponse)
      }

      return processedResponse
    } catch (error) {
      const apiError = this.handleError(error)
      return {
        success: false,
        message: apiError.details,
        error: apiError
      }
    }
  }

  /**
   * Fetch a list of resources with pagination
   * @param resource The resource name
   * @param params Pagination and query parameters
   * @returns Promise with paginated response
   */
  async fetch<T = any>(resource: string, params?: PaginationParams): Promise<ApiResponse<PaginatedResponse<T>>> {
    return this.request<PaginatedResponse<T>>(resource, {
      method: 'GET',
      params
    })
  }

  /**
   * Get a single resource by ID
   * @param resource The resource name
   * @param id The resource ID
   * @returns Promise with single resource
   */
  async get<T = any>(resource: string, id: string | number): Promise<ApiResponse<T>> {
    return this.request<T>(`${resource}/${id}`, {
      method: 'GET'
    })
  }

  /**
   * Create a new resource
   * @param resource The resource name
   * @param data The resource data
   * @returns Promise with created resource
   */
  async create<T = any, D = any>(resource: string, data: D): Promise<ApiResponse<T>> {
    return this.request<T>(resource, {
      method: 'POST',
      body: data
    })
  }

  /**
   * Update an existing resource
   * @param resource The resource name
   * @param id The resource ID
   * @param data The updated resource data
   * @returns Promise with updated resource
   */
  async update<T = any, D = any>(resource: string, id: string | number, data: D): Promise<ApiResponse<T>> {
    return this.request<T>(`${resource}/${id}`, {
      method: 'PUT',
      body: data
    })
  }

  /**
   * Delete a resource by ID
   * @param resource The resource name
   * @param id The resource ID
   * @returns Promise with deletion confirmation
   */
  async delete<T = any>(resource: string, id: string | number): Promise<ApiResponse<T>> {
    return this.request<T>(`${resource}/${id}`, {
      method: 'DELETE'
    })
  }
}

// Global API service instance
let apiServiceInstance: ApiService | null = null

/**
 * Get the API service instance (singleton)
 * @returns API service instance
 */
export function useApiService(): ApiService {
  if (!apiServiceInstance) {
    apiServiceInstance = new ApiService()
  }
  return apiServiceInstance
}

export default ApiService