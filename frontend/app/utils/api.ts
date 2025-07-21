// Refer to design/api/index.md for response structure
import { useNotifyService } from './notify'

export interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
  headers?: Record<string, string>
  body?: any
  params?: Record<string, any>
  signal?: AbortSignal
  responseType?: 'json' | 'text' | 'blob'
}

export interface Notification {
  type: 'info' | 'warning' | 'success'
  message: string
}

export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data?: T
  pagination?: PaginationInfo
  notifications?: Notification[]
  error?: {
    code: string
    details: string[]
  }
}

export interface PaginatedResponse<T = any> {
  data: T[]
  pagination: PaginationInfo
}

export interface PaginationInfo {
  totalItems: number
  currentPage: number
  itemsPerPage: number
  totalPages: number
  urlPath: string
  urlQuery: string | null
  nextPage: string | null
  prevPage: string | null
}

export interface PaginationParams {
  page?: number
  per_page?: number
  sort?: string
  dir?: 'asc' | 'desc'
  filter?: string
  search?: string
  [key: string]: any
}

export interface ApiError {
  message: string
  code: string
  details?: string[]
  status?: number
}

export type RequestInterceptor = (config: RequestOptions & { url: string }) => RequestOptions & { url: string }
export type ResponseInterceptor = (response: ApiResponse) => ApiResponse

// API Service implementation
class ApiService {
  private baseURL = ''
  private requestInterceptors: RequestInterceptor[] = []
  private responseInterceptors: ResponseInterceptor[] = []
  private notifyService = useNotifyService()

  /**
   * Display notifications from API response
   */
  private displayNotifications(notifications: Notification[]): void {
    notifications.forEach(notification => {
      this.notifyService.notify({
        type: notification.type,
        message: notification.message
      })
    })
  }

  /**
   * Handle API success response
   */
  private handleSuccess<T>(response: ApiResponse<T>): void {
    // Display any notifications from the response
    if (response.notifications && response.notifications.length > 0) {
      this.displayNotifications(response.notifications)
    }
  }

  /**
   * Handle API error response
   */
  private handleApiError(response: ApiResponse, status: number): void {
    // Log API error
    this.notifyService.error(
      response.message || 'Request failed',
      'API Error'
    )

    // Display any notifications from the error response
    if (response.notifications && response.notifications.length > 0) {
      this.displayNotifications(response.notifications)
    }
  }

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
        this.handleApiError(data, response.status)
        throw this.createError(data, response.status)
      }

      // Handle successful response
      this.handleSuccess(data)

      return data
    } catch (error) {
      if (error instanceof Error && error.name === 'ApiError') {
        throw error
      }
      
      // Handle unexpected errors
      const apiError = this.handleError(error)
      this.notifyService.error(
        apiError.message,
        'Network Error'
      )
      throw apiError
    }
  }

  /**
   * Fetch a list of resources with pagination
   */
  async fetch<T = any>(resource: string, params?: PaginationParams): Promise<ApiResponse<PaginatedResponse<T>>> {
    const { url, notifications } = this.buildUrl(resource, params)
    const response = await this.request<PaginatedResponse<T>>(url)
    
    // Merge validation notifications with response notifications
    if (notifications.length > 0) {
      response.notifications = [
        ...(response.notifications || []),
        ...notifications
      ]
    }
    
    return response
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
   * Build URL with query parameters and validation
   */
  buildUrl(resource: string, params?: Record<string, any>): { url: string; notifications: Notification[] } {
    let url = `/api/v1/${resource}`
    const notifications: Notification[] = []
    
    if (params) {
      const validatedParams: Record<string, any> = {}
      
      // Validate and process each parameter
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          switch (key) {
            case 'page':
              const pageNum = Number(value)
              if (isNaN(pageNum) || pageNum <= 0) {
                validatedParams.page = 1
                notifications.push({
                  type: 'warning',
                  message: `Invalid page number '${value}', using page 1`
                })
              } else {
                validatedParams.page = pageNum
              }
              break
              
            case 'per_page':
              const perPage = Number(value)
              if (isNaN(perPage) || perPage < 1) {
                validatedParams.per_page = 1
                notifications.push({
                  type: 'warning',
                  message: `Page size '${value}' below minimum of 1, using minimum 1`
                })
              } else if (perPage > 100) {
                validatedParams.per_page = 100
                notifications.push({
                  type: 'warning',
                  message: `Page size '${value}' exceeds maximum of 100, using maximum 100`
                })
              } else {
                validatedParams.per_page = perPage
              }
              break
              
            case 'dir':
              if (value !== 'asc' && value !== 'desc') {
                validatedParams.dir = 'asc'
                notifications.push({
                  type: 'warning',
                  message: `Sort direction '${value}' not recognized, using 'asc'`
                })
              } else {
                validatedParams.dir = value
              }
              break
              
            case 'search':
              if (typeof value === 'string' && value.length < 2) {
                // Ignore search term if too short
                notifications.push({
                  type: 'warning',
                  message: 'Search term too short (minimum 2 characters), search ignored'
                })
              } else {
                validatedParams.search = value
              }
              break
              
            case 'filter':
              if (typeof value === 'string' && !value.includes(':')) {
                notifications.push({
                  type: 'warning',
                  message: `Filter format '${value}' not recognized, filter ignored`
                })
              } else {
                validatedParams.filter = value
              }
              break
              
            default:
              validatedParams[key] = value
              break
          }
        }
      })
      
      const queryParams = new URLSearchParams()
      Object.entries(validatedParams).forEach(([key, value]) => {
        queryParams.append(key, String(value))
      })
      
      const queryString = queryParams.toString()
      if (queryString) {
        url += `?${queryString}`
      }
    }
    
    return { url, notifications }
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
      apiError.details = error.stack ? [error.stack] : undefined
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