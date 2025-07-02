/**
 * Base API Service for Thanzil project
 * Handles API requests with proper error handling and standardized responses
 * Includes request and response interceptors
 */
import { ref, watch, computed, type Ref } from 'vue'
import type { ApiResponse, ApiError } from '~/types'
import { createAuthTokenInterceptor } from './auth'
import { useApplicationConfig } from '~/composables/useApplicationConfig'

// Get configuration from centralized config
const getApiConfig = () => {
  const config = useApplicationConfig()
  return {
    baseUrl: config.api.baseUrl,
    version: config.api.version,
    timeout: config.api.timeout
  }
}

// Global loading store for multiple components to access loading state
export const globalLoadingState = ref<boolean>(false)

// Global interceptor stores - shared across all API service instances
const globalRequestInterceptors = ref<RequestInterceptor[]>([])
const globalResponseInterceptors = ref<ResponseInterceptor[]>([])

// Singleton instance
let apiServiceInstance: ApiService | null = null

// Debug: Track API service instance creation
let apiInstanceCreationCount = 0;

/**
 * Request interceptor function type
 * Function that can modify request options before they are sent
 */
export type RequestInterceptor = (
  url: string,
  options: RequestInit & { resource: string; id?: string | number }
) => RequestInit

/**
 * Response interceptor function type
 * Function that can process or modify responses before they are returned
 */
export type ResponseInterceptor = (
  response: Response,
  options: { resource: string; id?: string | number }
) => Promise<Response | any>

/**
 * API Service interface
 */
export interface ApiService {
  request<T = any>(
    resource: string,
    options?: RequestOptions,
    id?: string | number
  ): Promise<ApiResponse<T>>
  loading: Ref<boolean>
  globalVersion: Ref<string>
  setGlobalVersion: (version: string) => void
  buildUrl: (resource: string, id?: string | number, version?: string) => string
  addRequestInterceptor: (interceptor: RequestInterceptor) => () => void
  addResponseInterceptor: (interceptor: ResponseInterceptor) => () => void
  cancelAllRequests: () => void
  cancelRequest: (requestId: string) => void
  isOnline: Ref<boolean>
  initialize: () => void
  cleanup: () => void
}

/**
 * Options for API requests
 */
export interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  headers?: HeadersInit
  body?: any
  version?: string
  params?: Record<string, string | number | boolean | undefined>
  signal?: AbortSignal
  skipInterceptors?: boolean
  showGlobalLoading?: boolean
}

/**
 * Base API Service using the Fetch API
 * Provides consistent error handling and response formatting
 */
const createApiService = (): ApiService => {
  // Get configuration from centralized config
  const { baseUrl: API_BASE_URL, version: DEFAULT_VERSION } = getApiConfig()
  
  // Reactive state
  const loading = ref<boolean>(false)
  const globalVersion = ref<string>(DEFAULT_VERSION)
  const isOnline = ref<boolean>(true)
  
  // Use global interceptor stores instead of local ones
  const requestInterceptors = globalRequestInterceptors
  const responseInterceptors = globalResponseInterceptors
  
  // Active requests for cancellation tracking
  const activeRequests = ref<Map<string, AbortController>>(new Map())
  
  /**
   * Set the global API version
   */
  const setGlobalVersion = (version: string) => {
    globalVersion.value = version
  }
  
  /**
   * Add a request interceptor
   * @param interceptor - Function that modifies request options
   * @returns Function to remove the interceptor
   */
  const addRequestInterceptor = (interceptor: RequestInterceptor): () => void => {
    requestInterceptors.value.push(interceptor)
    
    // Return a function that removes this interceptor
    return () => {
      const index = requestInterceptors.value.indexOf(interceptor)
      if (index !== -1) {
        requestInterceptors.value.splice(index, 1)
      }
    }
  }
  
  /**
   * Add a response interceptor
   * @param interceptor - Function that processes response
   * @returns Function to remove the interceptor
   */
  const addResponseInterceptor = (interceptor: ResponseInterceptor): () => void => {
    responseInterceptors.value.push(interceptor)
    
    // Return a function that removes this interceptor
    return () => {
      const index = responseInterceptors.value.indexOf(interceptor)
      if (index !== -1) {
        responseInterceptors.value.splice(index, 1)
      }
    }
  }  /**
   * Apply all request interceptors to a request
   * @param url - Request URL
   * @param options - Request options
   * @param resource - API resource
   * @param id - Resource identifier
   * @returns Modified request options
   */
  const applyRequestInterceptors = (
    url: string,
    options: RequestInit,
    resource: string,
    id?: string | number
  ): RequestInit => {
    let modifiedOptions = { ...options }
    
    for (const interceptor of requestInterceptors.value) {
      modifiedOptions = interceptor(url, { ...modifiedOptions, resource, id })
    }
    
    return modifiedOptions
  }
  
  /**
   * Apply all response interceptors to a response
   * @param response - Fetch response
   * @param resource - API resource
   * @param id - Resource identifier
   * @returns Modified response
   */
  const applyResponseInterceptors = async (
    response: Response,
    resource: string,
    id?: string | number
  ): Promise<any> => {
    let modifiedResponse = response
    
    for (const interceptor of responseInterceptors.value) {
      modifiedResponse = await interceptor(modifiedResponse, { resource, id })
    }
    
    return modifiedResponse
  }
  
  /**
   * Cancel all active requests
   */
  const cancelAllRequests = () => {
    activeRequests.value.forEach((controller) => {
      controller.abort()
    })
    activeRequests.value.clear()
  }
  
  /**
   * Cancel a specific request
   * @param requestId - The identifier of the request to cancel
   */
  const cancelRequest = (requestId: string) => {
    const controller = activeRequests.value.get(requestId)
    if (controller) {
      controller.abort()
      activeRequests.value.delete(requestId)
    }
  }
  
  /**
   * Handle network status changes
   */
  const updateNetworkStatus = () => {
    isOnline.value = navigator.onLine
    
    if (!isOnline.value) {
      // Cache the network status change
      console.warn('Network connection lost')
    } else {
      console.info('Network connection restored')
    }
  }
  
  /**
   * Generate a unique request ID for tracking
   */
  const generateRequestId = (resource: string, id?: string | number): string => {
    return `${resource}${id ? `_${id}` : ''}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
  }
  
  /**
   * Builds the full API URL
   * @param resource - The API resource/endpoint
   * @param id - Optional resource identifier
   * @param version - Optional API version override
   * @returns The complete API URL
   */  const buildUrl = (resource: string, id?: string | number, version?: string): string => {
    // Get fresh config values
    const { baseUrl: API_BASE_URL, version: DEFAULT_VERSION } = getApiConfig()
    
    // Use the provided version or fall back to the global version
    const apiVersion = version || globalVersion.value || DEFAULT_VERSION
    
    // Build the base URL with version and resource
    let url = `${API_BASE_URL}/${apiVersion}/${resource}`
    
    // Append ID if provided
    if (id !== undefined) {
      url += `/${id}`
    }
    
    return url
  }
  
  /**
   * Handles and formats API errors
   * @param error - The error object
   * @param url - The requested URL
   * @returns Formatted API error
   */
  const handleError = (error: any, url: string): ApiError => {
    // Create a base error object
    const apiError: ApiError = new Error(
      error.message || 'An unexpected error occurred'
    ) as ApiError
    
    // Add additional context
    apiError.name = error.name || 'ApiError'
    apiError.statusCode = error.statusCode
    
    // Log the error for debugging
    console.error(`API Error (${url}):`, apiError)
    
    return apiError
  }
    /**
   * Adds query parameters to a URL
   * @param url - Base URL
   * @param params - Query parameters
   * @returns URL with query parameters
   */
  const addQueryParams = (url: string, params?: Record<string, any>): string => {
    if (!params) return url
    
    // Transform parameters to match backend expectations
    const transformedParams = { ...params }
    
    // Convert frontend parameter names to backend format
    if (transformedParams.perPage) {
      transformedParams.per_page = transformedParams.perPage
      delete transformedParams.perPage
    }
    
    // Filter out undefined values
    const filteredParams = Object.fromEntries(
      Object.entries(transformedParams).filter(([_, value]) => value !== undefined)
    )
    
    // If no valid params, return original URL
    if (Object.keys(filteredParams).length === 0) return url
    
    // Build query string
    const queryString = new URLSearchParams(
      // Convert all values to strings
      Object.fromEntries(
        Object.entries(filteredParams).map(([key, value]) => [key, String(value)])
      )
    ).toString()
    
    return `${url}?${queryString}`
  }
    /**
   * Main request method to perform API calls
   * @param resource - The API resource/endpoint
   * @param options - Request options
   * @param id - Optional resource identifier
   * @returns API response with data, error, and loading state
   */
  const request = async function <T = any>(
    resource: string,
    options: RequestOptions = {},
    id?: string | number
  ): Promise<ApiResponse<T>> {
    // Extract options with defaults
    const {
      method = 'GET',
      headers = {},
      body,
      version,
      params,
      signal,
      skipInterceptors = false,
      showGlobalLoading = true
    } = options
    
    // Set loading state
    loading.value = true
    if (showGlobalLoading) {
      globalLoadingState.value = true
    }
    
    // Initialize response object
    const response: ApiResponse<T> = {
      data: null,
      error: null,
      loading: true
    }
    
    // Generate a unique request ID for tracking
    const requestId = generateRequestId(resource, id)
    
    // Create an AbortController if not provided
    let requestAbortController: AbortController | undefined
    let requestSignal = signal
    
    if (!signal) {
      requestAbortController = new AbortController()
      requestSignal = requestAbortController.signal
      activeRequests.value.set(requestId, requestAbortController)
    }
    
    try {
      // Check online status first
      if (!isOnline.value) {
        throw new Error('No internet connection available')
      }
      
      // Build request URL
      let url = buildUrl(resource, id, version)
      
      // Add query parameters if provided
      if (params) {
        url = addQueryParams(url, params)
      }
      
      // Prepare request headers
      const requestHeaders: HeadersInit = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...headers
      }
      
      // Prepare request options
      let requestOptions: RequestInit = {
        method,
        headers: requestHeaders,
        signal: requestSignal
      }
      
      // Add body for non-GET requests
      if (body && method !== 'GET') {
        requestOptions.body = JSON.stringify(body)
      }        // Apply request interceptors
      if (!skipInterceptors) {
        requestOptions = applyRequestInterceptors(url, requestOptions, resource, id)
      }
      
      // Make the fetch request
      let fetchResponse = await fetch(url, requestOptions)
      
      // Apply response interceptors
      if (!skipInterceptors) {
        fetchResponse = await applyResponseInterceptors(fetchResponse, resource, id)
      }
        // Handle non-2xx responses
      if (!fetchResponse.ok) {
        let errorData
        
        try {
          // Try to parse error response
          errorData = await fetchResponse.json()
        } catch (e) {
          // If parsing fails, use basic status text
          errorData = { message: fetchResponse.statusText }
        }
        
        // Handle new standardized error format
        let errorMessage: string
        let validationErrors: any = undefined
        
        if (errorData.success === false && errorData.error) {
          // New standardized error format
          errorMessage = errorData.error.message || `Request failed with status ${fetchResponse.status}`
          validationErrors = errorData.error.validation_errors
        } else if (errorData.message) {
          // Legacy error format
          errorMessage = errorData.message
          validationErrors = errorData.errors
        } else {
          // Fallback
          errorMessage = `Request failed with status ${fetchResponse.status}`
        }
        
        // Create error with details from response
        const error: ApiError = new Error(errorMessage) as ApiError
        
        error.statusCode = fetchResponse.status
        error.validationErrors = validationErrors
        
        throw error
      }
      
      // Parse successful response
      let data
      
      // Check if the response is a Response object (from interceptor) or already processed data
      if (fetchResponse instanceof Response) {
        // Check if response is empty
        const contentType = fetchResponse.headers.get('content-type')
        if (contentType && contentType.includes('application/json')) {
          data = await fetchResponse.json()
        } else {
          data = await fetchResponse.text()
        }
      } else {
        // Response already processed by an interceptor
        data = fetchResponse
      }      // Handle new standardized response format
      if (data && typeof data === 'object' && 'success' in data) {
        // New standardized format: { success: true, data: {...}, ... }
        if (data.success) {
          // Preserve the entire response structure, not just the data
          response.data = data
        } else {
          // Handle error case
          const errorMessage = data.error?.message || 'Request failed'
          const error: ApiError = new Error(errorMessage) as ApiError
          error.validationErrors = data.error?.validation_errors
          throw error
        }
      } else {
        // Legacy format or already unwrapped data
        response.data = data
      }
      
    } catch (error: any) {
      // Handle fetch errors (network issues, etc.)
      if (error.name === 'AbortError') {
        response.error = new Error('Request was aborted') as ApiError
      } else {
        response.error = handleError(error, resource)
      }
    } finally {
      // Update loading state
      loading.value = false
      response.loading = false
      
      if (showGlobalLoading) {
        globalLoadingState.value = false
      }
        // Remove from active requests
      if (requestId) {
        activeRequests.value.delete(requestId)
      }
    }
    
    return response
  }
  // Initialize API service (should be called in components that need it)
  const initialize = () => {
    if (typeof window !== 'undefined') {
      // Set initial network status
      isOnline.value = navigator.onLine
      
      // Add event listeners for network status changes
      window.addEventListener('online', updateNetworkStatus)
      window.addEventListener('offline', updateNetworkStatus)      // Add default request interceptor for handling offline status
      addRequestInterceptor((url, options) => {
        if (!isOnline.value) {
          throw new Error('No internet connection available')
        }
        return options
      })      // Add auth token interceptor
      const { requestInterceptor, responseInterceptor } = createAuthTokenInterceptor()
      addRequestInterceptor(requestInterceptor)
      addResponseInterceptor(responseInterceptor)
    }
  }
  
  // Clean up event listeners (should be called when component unmounts)
  const cleanup = () => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('online', updateNetworkStatus)
      window.removeEventListener('offline', updateNetworkStatus)
    }
  }
  return {
    request,
    loading,
    globalVersion,
    setGlobalVersion,
    buildUrl,
    addRequestInterceptor,
    addResponseInterceptor,
    cancelAllRequests,
    cancelRequest,
    isOnline,
    initialize,
    cleanup
  }
}

/**
 * Singleton API Service
 * Ensures all components use the same API service instance with shared interceptors
 */
export const useApiService = (): ApiService => {
  if (!apiServiceInstance) {
    apiInstanceCreationCount++;
    apiServiceInstance = createApiService()
  }
  return apiServiceInstance
}

/**
 * Creates an abort controller for request cancellation
 */
export const useAbortController = () => {
  const controller = new AbortController()
  const signal = controller.signal
  
  // Abort the request
  const abortRequest = () => {
    controller.abort()
  }
  
  return {
    signal,
    abortRequest
  }
}

// Example interceptors for common use cases

/**
 * Create a loading indicator interceptor
 * Shows a global loading indicator during requests
 */
export const createLoadingInterceptor = () => {
  // Request interceptor to show loading
  const requestInterceptor: RequestInterceptor = (url, options) => {
    globalLoadingState.value = true
    return options
  }
  
  // Response interceptor to hide loading
  const responseInterceptor: ResponseInterceptor = async (response) => {
    globalLoadingState.value = false
    return response
  }
  
  return { requestInterceptor, responseInterceptor }
}

// Auth interceptor implementation moved to auth.ts

// Export the CRUD API service for convenient imports
export { useApiCrud } from './apiCrud'
// Export the Auth service for convenient imports  
export { useAuthService } from './auth'
