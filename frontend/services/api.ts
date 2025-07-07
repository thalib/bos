/**
 * Base API Service for Thanzil project
 * Handles API requests with proper error handling and standardized responses
 * Includes request and response interceptors
 */
import { ref, watch, computed, type Ref } from 'vue'
import type { ApiResponse, ApiError } from '~/types'
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
 * Request options for API requests
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
  responseType?: 'json' | 'text' | 'blob' | 'arraybuffer'
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
        // Handle response based on the responseType option
        const responseType = options.responseType || 'json'
        const contentType = fetchResponse.headers.get('content-type') || ''
        
        if (responseType === 'blob') {
          // For PDF and other binary data
          data = await fetchResponse.blob()
          // Store the original response for additional processing if needed
          Object.defineProperty(data, '_response', { value: fetchResponse })
        } else if (responseType === 'arraybuffer') {
          // For binary data as ArrayBuffer
          data = await fetchResponse.arrayBuffer()
          Object.defineProperty(data, '_response', { value: fetchResponse })
        } else if (responseType === 'text' || 
                  (!contentType.includes('application/json') && responseType === 'json')) {
          // Text response or when content is not JSON but responseType is 'json'
          data = await fetchResponse.text()
        } else {
          // Default JSON parsing
          try {
            data = await fetchResponse.json()
          } catch (e) {
            // Fallback to text if JSON parsing fails
            data = await fetchResponse.text()
          }
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
      })  // Add auth token interceptor
  const createAuthTokenInterceptor = () => {
    const requestInterceptor: RequestInterceptor = (url, options) => {
      // Get token from localStorage
      const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null
      
      if (token) {
        const headers = new Headers(options.headers)
        headers.set('Authorization', `Bearer ${token}`)
        return {
          ...options,
          headers
        }
      }
      
      return options
    }

    const responseInterceptor: ResponseInterceptor = async (response, { resource, id }) => {
      // Handle 401 unauthorized responses
      if (response.status === 401) {
        // Clear stored token if unauthorized
        if (typeof window !== 'undefined') {
          localStorage.removeItem('auth_token')
          localStorage.removeItem('auth_refresh_token')
          localStorage.removeItem('auth_user')
          
          // Emit unauthorized event for other components to handle
          window.dispatchEvent(new CustomEvent('auth:unauthorized', { 
            detail: { source: 'api-service', resource, id } 
          }))
        }
      }
      
      return response
    }

    return { requestInterceptor, responseInterceptor }
  }

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
    
    // Initialize the API service to set up interceptors
    apiServiceInstance.initialize()
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

/**
 * Document PDF Generation API Functions
 * These functions integrate with the Laravel PDF generation backend
 */

/**
 * Generate PDF document using backend template
 * @param templateName - Backend template name (e.g., 'invoice', 'report', 'receipt')
 * @param data - Document data to populate the template
 * @param options - PDF generation options (format, orientation, etc.)
 * @returns Promise<Blob> - PDF file as blob for download
 * @throws Error if generation fails
 */
export const generateDocumentPdf = async (
  templateName: string, 
  data: any, 
  options?: any
): Promise<Blob> => {
  try {
    // Create payload
    const payload = {
      template: templateName,
      data: data,
      options: options || {},
      filename: `${templateName}_${Date.now()}.pdf`
    }

    // Use the authenticated API service instead of direct $fetch
    const apiService = useApiService()
    
    // Ensure API service is initialized (sets up auth interceptors)
    apiService.initialize()
    
    // Custom headers for blob response
    const headers = {
      'Accept': 'application/pdf',
      'Content-Type': 'application/json'
    }

    // Use the request method with proper authentication
    const response = await apiService.request<Blob>('documents/generate-pdf', {
      method: 'POST',
      body: payload,
      headers,
      responseType: 'blob',
      // Only skip response interceptors that might try to parse JSON, but keep auth interceptors
      skipInterceptors: false
    })

    // Handle the raw response to get the blob
    if (response.error) {
      throw new Error(`PDF generation failed: ${response.error.message || 'Unknown error'}`)
    }

    if (!response.data) {
      throw new Error('Failed to get PDF data from response')
    }
    
    const blob = response.data as Blob
    if (blob.size === 0) {
      throw new Error('Generated PDF is empty')
    }

    return blob
  } catch (error: any) {
    console.error('PDF generation error:', error)
    
    // Handle different error types
    if (error.statusCode === 422) {
      throw new Error(`PDF generation failed: Invalid data for template "${templateName}"`)
    } else if (error.statusCode === 404) {
      throw new Error(`PDF generation failed: Template "${templateName}" not found`)
    } else if (error.statusCode === 500) {
      throw new Error('PDF generation failed: Server error')
    }
    
    throw new Error(`Failed to generate PDF: ${error.message || 'Unknown error'}`)
  }
}

/**
 * Get available PDF templates from backend
 * @returns Promise<string[]> - Array of available template names
 * @throws Error if request fails
 */
export const getAvailableTemplates = async (): Promise<string[]> => {
  const apiService = useApiService()
  
  try {
    const response = await apiService.request<any>('documents/templates', {
      method: 'GET'
    })
    
    if (response.error || !response.data) {
      throw new Error(`Failed to fetch templates: ${response.error?.message || 'Invalid response'}`)
    }

    // Extract template names from the response data
    const templates = Object.keys(response.data)
    
    return templates

  } catch (error: any) {
    console.error('Template fetch error:', error)
    throw new Error(`Failed to get available templates: ${error.message || 'Unknown error'}`)
  }
}

/**
 * Generate HTML preview of document template
 * @param templateName - Backend template name
 * @param data - Document data to populate the template
 * @returns Promise<string> - HTML preview content
 * @throws Error if preview generation fails
 */
export const previewDocument = async (
  templateName: string, 
  data: any
): Promise<string> => {
  const apiService = useApiService()
  
  try {
    const payload = {
      template: templateName,
      data: data
    }

    const response = await apiService.request<any>('documents/preview', {
      method: 'POST',
      body: payload
    })
    
    if (response.error || !response.data?.preview) {
      throw new Error(`Failed to generate preview: ${response.error?.message || 'Invalid response'}`)
    }

    return response.data.preview

  } catch (error: any) {
    console.error('Preview generation error:', error)
    throw new Error(`Failed to generate preview: ${error.message || 'Unknown error'}`)
  }
}

/**
 * Validate template data against backend template requirements
 * @param templateName - Backend template name
 * @param data - Document data to validate
 * @returns Promise<boolean> - True if valid, throws error if invalid
 * @throws Error if validation fails
 */
export const validateTemplateData = async (
  templateName: string, 
  data: any
): Promise<boolean> => {
  const apiService = useApiService()
  
  try {
    const payload = {
      template: templateName,
      data: data
    }

    const response = await apiService.request<any>('documents/validate', {
      method: 'POST',
      body: payload
    })
    
    if (response.error) {
      throw new Error(`Validation failed: ${response.error.message || 'Unknown validation error'}`)
    }

    return response.data?.valid === true

  } catch (error: any) {
    console.error('Template validation error:', error)
    throw new Error(`Template validation failed: ${error.message || 'Unknown error'}`)
  }
}

/**
 * Get template information and metadata
 * @param templateName - Backend template name
 * @returns Promise<any> - Template information object
 * @throws Error if template not found
 */
export const getTemplateInfo = async (templateName: string): Promise<any> => {
  const apiService = useApiService()
  
  try {
    const response = await apiService.request<any>(`documents/templates/${templateName}`, {
      method: 'GET'
    })
    
    if (response.error) {
      throw new Error(`Failed to get template info: ${response.error.message}`)
    }

    return response.data

  } catch (error: any) {
    console.error('Template info fetch error:', error)
    throw new Error(`Failed to get template information: ${error.message || 'Unknown error'}`)
  }
}

/**
 * Check if templates are available from backend
 * @returns Promise<boolean> - True if templates service is available
 */
export const checkTemplateAvailability = async (): Promise<boolean> => {
  try {
    // Use the api service directly to check for auth issues
    const apiService = useApiService()
    const response = await apiService.request('documents/templates', {
      method: 'GET'
    })
    
    if (response.error) {
      // Check specifically for auth errors
      const apiError = response.error as any
      if (apiError.statusCode === 401) {
        console.warn('Template service authentication failed:', response.error)
        // Emit an event that can be caught by the auth system
        if (typeof window !== 'undefined') {
          window.dispatchEvent(new CustomEvent('auth:unauthorized', { 
            detail: { source: 'template-service', error: response.error } 
          }))
        }
      } else {
        console.warn('Template service error:', response.error)
      }
      return false
    }
    
    return true
  } catch (error) {
    console.warn('Template service unavailable:', error)
    return false
  }
}
