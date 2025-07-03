/**
 * API Error Handling Utilities
 * These utilities help handle and format API errors for a better user experience
 */
import { ref, computed, reactive } from 'vue'
import type { ApiError, ApiResponse } from '~/types'

// Toast/notification system state
interface Toast {
  id: string;
  message: string;
  type: 'success' | 'error' | 'warning' | 'info';
  timeout: number;
}

// Global toast state store
const toasts = reactive<Map<string, Toast>>(new Map())
const defaultToastTimeout = 5000 // 5 seconds

/**
 * Format API error message for user display
 * @param error - The API error object
 * @returns Formatted error message
 */
export function formatErrorMessage(error: ApiError | Error | null): string {
  if (!error) return 'Unknown error occurred'
  
  // Handle validation errors specifically
  if ('validationErrors' in error && error.validationErrors) {
    // Format the first validation error
    const firstField = Object.keys(error.validationErrors)[0]
    if (firstField) {
      const firstError = error.validationErrors[firstField][0]
      return `${firstField}: ${firstError}`
    }
  }
  
  // Handle HTTP status code specific messages
  if ('statusCode' in error && error.statusCode) {
    switch (error.statusCode) {
      case 400: return error.message || 'Bad request - please check your input'
      case 401: return 'Authentication required - please log in'
      case 403: return 'You do not have permission to perform this action'
      case 404: return 'The requested resource was not found'
      case 422: return error.message || 'Validation failed - please check your input'
      case 429: return 'Too many requests - please try again later'
      case 500: return 'Server error - please try again later'
      default: return error.message || `Error ${error.statusCode}`
    }
  }
  
  return error.message || 'An unexpected error occurred'
}

/**
 * Format validation errors from Laravel API
 * @param error - The API error object
 * @returns Object with fields as keys and formatted error messages as values
 */
export function formatValidationErrors(error: ApiError | null): Record<string, string> {
  if (!error || !('validationErrors' in error) || !error.validationErrors) {
    return {}
  }
  
  // Convert Laravel validation format to simple field -> message format
  return Object.entries(error.validationErrors).reduce((result, [field, messages]) => {
    result[field] = messages[0] // Use the first error message for each field
    return result
  }, {} as Record<string, string>)
}

/**
 * Toast/notification system for API errors and messages
 */
export const useToast = () => {
  /**
   * Show a toast notification
   * @param message - Message to display
   * @param type - Type of toast (success, error, warning, info)
   * @param timeout - Time in ms before toast disappears (0 for no auto-dismiss)
   * @returns Toast ID for programmatic dismissal
   */
  const showToast = (
    message: string,
    type: 'success' | 'error' | 'warning' | 'info' = 'info',
    timeout: number = defaultToastTimeout
  ): string => {
    // Generate unique ID for the toast
    const id = `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    
    // Create and store the toast
    const toast: Toast = {
      id,
      message,
      type,
      timeout
    }
    
    toasts.set(id, toast)
    
    // Set timeout to dismiss toast if timeout > 0
    if (timeout > 0) {
      setTimeout(() => dismissToast(id), timeout)
    }
    
    return id
  }
  
  /**
   * Show an error toast with formatted error message
   * @param error - The error object
   * @param timeout - Time in ms before toast disappears
   * @returns Toast ID
   */
  const showErrorToast = (error: ApiError | Error | string, timeout = defaultToastTimeout): string => {
    const message = typeof error === 'string' ? error : formatErrorMessage(error as ApiError)
    return showToast(message, 'error', timeout)
  }
  
  /**
   * Show a success toast
   * @param message - Success message
   * @param timeout - Time in ms before toast disappears
   * @returns Toast ID
   */
  const showSuccessToast = (message: string, timeout = defaultToastTimeout): string => {
    return showToast(message, 'success', timeout)
  }
  
  /**
   * Show a warning toast
   * @param message - Warning message
   * @param timeout - Time in ms before toast disappears
   * @returns Toast ID
   */
  const showWarningToast = (message: string, timeout = defaultToastTimeout): string => {
    return showToast(message, 'warning', timeout)
  }
  
  /**
   * Dismiss a specific toast
   * @param id - The toast ID
   */
  const dismissToast = (id: string): void => {
    toasts.delete(id)
  }
  
  /**
   * Dismiss all toasts
   */
  const dismissAllToasts = (): void => {
    toasts.clear()
  }
  
  /**
   * Get all active toasts
   */
  const activeToasts = computed(() => Array.from(toasts.values()))
  
  return {
    showToast,
    showErrorToast,
    showSuccessToast,
    showWarningToast,
    dismissToast,
    dismissAllToasts,
    activeToasts
  }
}

/**
 * Retry functionality for failed API requests
 * @param fn - The API request function to retry
 * @param retryOptions - Retry configuration
 * @returns The result of the API request with retry functionality
 */
export async function withRetry<T>(
  fn: () => Promise<ApiResponse<T>>,
  retryOptions: {
    maxRetries?: number;
    retryDelay?: number;
    shouldRetry?: (error: ApiError | Error) => boolean;
  } = {}
): Promise<ApiResponse<T>> {
  const {
    maxRetries = 3,
    retryDelay = 1000,
    shouldRetry = defaultShouldRetry
  } = retryOptions
  
  let retries = 0
  let lastError: ApiError | Error | null = null
  
  // Keep trying until we succeed or run out of retries
  while (retries <= maxRetries) {
    try {
      const result = await fn()
      
      // If there's an error but we should not retry, return result immediately
      if (result.error && !shouldRetry(result.error)) {
        return result
      }
      
      // If there's no error or we've reached max retries, return the result
      if (!result.error || retries >= maxRetries) {
        return result
      }
      
      // Store the error for potential next retry
      lastError = result.error
      
    } catch (e) {
      // Handle unexpected errors (shouldn't happen with our API service)
      lastError = e as Error
      
      // If we shouldn't retry this error, throw immediately
      if (!shouldRetry(lastError)) {
        return {
          data: null,
          error: lastError,
          loading: false
        }
      }
    }
    
    // Increment retry counter
    retries++
    
    if (retries <= maxRetries) {
      // Wait before retrying with exponential backoff
      await new Promise(resolve => setTimeout(resolve, retryDelay * Math.pow(2, retries - 1)))
    }
  }
  
  // If we got here, all retries failed
  return {
    data: null,
    error: lastError,
    loading: false
  }
}

/**
 * Default function to determine if a request should be retried
 * @param error - The error that occurred
 * @returns Whether the request should be retried
 */
function defaultShouldRetry(error: ApiError | Error): boolean {
  // Don't retry client errors (except 408 Request Timeout)
  if ('statusCode' in error && error.statusCode) {
    // Don't retry validation errors, unauthorized, forbidden, not found
    if ([400, 401, 403, 404, 422].includes(error.statusCode)) {
      return false
    }
    
    // Retry server errors and timeout errors
    return error.statusCode >= 500 || error.statusCode === 408
  }
  
  // Retry network errors
  return error.message.includes('network') || 
         error.message.includes('timeout') || 
         error.message.includes('abort')
}

/**
 * Handle offline scenarios with queuing capabilities
 */
export const useOfflineQueue = () => {
  // Queue of requests to be processed when online
  const requestQueue = ref<Array<{
    id: string;
    execute: () => Promise<any>;
    timestamp: number;
  }>>([])
  
  // Network status
  const isOnline = ref(navigator?.onLine ?? true)
  
  // Process the queue when we go back online
  const processQueue = async () => {
    if (!isOnline.value || requestQueue.value.length === 0) return
    
    console.info(`Processing offline queue: ${requestQueue.value.length} requests`)
    
    const { showSuccessToast, showErrorToast } = useToast()
    
    // Process requests in order (FIFO)
    const queue = [...requestQueue.value]
    requestQueue.value = []
    
    for (const item of queue) {
      try {
        await item.execute()
      } catch (error) {
        // If a request fails, put it back in the queue
        requestQueue.value.push(item)
        showErrorToast('Failed to process some offline requests. Will retry later.')
        return
      }
    }
    
    if (queue.length > 0) {
      showSuccessToast(`Successfully processed ${queue.length} offline requests`)
    }
  }
  
  // Add a request to the queue
  const queueRequest = <T>(
    executeFunction: () => Promise<ApiResponse<T>>
  ): string => {
    const id = `offline-request-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    
    requestQueue.value.push({
      id,
      execute: executeFunction,
      timestamp: Date.now()
    })
    
    return id
  }
  
  // Remove a request from the queue
  const removeFromQueue = (id: string): boolean => {
    const initialLength = requestQueue.value.length
    requestQueue.value = requestQueue.value.filter(item => item.id !== id)
    return requestQueue.value.length !== initialLength
  }
  
  // Update network status and process queue when online
  const updateNetworkStatus = () => {
    const wasOffline = !isOnline.value
    isOnline.value = navigator?.onLine ?? true
    
    if (!wasOffline && isOnline.value && requestQueue.value.length > 0) {
      processQueue()
    }
  }
  
  // Initialize network status listeners (only if running in browser)
  const initializeNetworkStatus = () => {
    if (typeof window !== 'undefined') {
      isOnline.value = navigator.onLine
      window.addEventListener('online', updateNetworkStatus)
      window.addEventListener('offline', updateNetworkStatus)
    }
  }
  
  // Cleanup network status listeners (only if running in browser)
  const cleanupNetworkStatus = () => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('online', updateNetworkStatus)
      window.removeEventListener('offline', updateNetworkStatus)
    }
  }
  
  return {
    isOnline,
    queueRequest,
    removeFromQueue,
    processQueue,
    requestQueue,
    initializeNetworkStatus,
    cleanupNetworkStatus
  }
}

/**
 * Extract validation error message for a specific field from an API error
 * @param error - API error object
 * @param field - Field name to get error for
 * @returns Error message for the field or null if no error
 */
export function getFieldError(error: ApiError | null, field: string): string | null {
  if (!error || !('validationErrors' in error) || !error.validationErrors) {
    return null
  }
  
  const fieldErrors = error.validationErrors[field]
  return fieldErrors && fieldErrors.length > 0 ? fieldErrors[0] : null
}

/**
 * Check if a field has validation errors
 * @param error - API error object
 * @param field - Field name to check
 * @returns Whether the field has errors
 */
export function hasFieldError(error: ApiError | null, field: string): boolean {
  return getFieldError(error, field) !== null
}

/**
 * Parse error response from API
 * @param response - Fetch API Response object
 * @returns Parsed API error
 */
export async function parseErrorResponse(response: Response): Promise<ApiError> {
  let errorData: any = {}
  
  try {
    // Try to parse JSON response
    errorData = await response.json()
  } catch (e) {
    // If it's not JSON, use text or status
    try {
      errorData = { message: await response.text() }
    } catch (e) {
      errorData = { message: response.statusText }
    }
  }
  
  const error = new Error(
    errorData.message || `Request failed with status ${response.status}`
  ) as ApiError
  
  error.name = 'ApiError'
  error.statusCode = response.status
  error.validationErrors = errorData.errors || {}
  
  return error
}

/**
 * Format all validation errors from a Laravel API response into a single string
 * @param error - API error object
 * @returns All validation errors as a single string
 */
export function formatAllValidationErrors(error: ApiError | null): string {
  if (!error || !('validationErrors' in error) || !error.validationErrors) {
    return ''
  }
  
  return Object.entries(error.validationErrors)
    .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
    .join('\n')
}

/**
 * Check if an error is a network error (offline, timeout, etc.)
 * @param error - Error to check
 * @returns Whether it's a network error
 */
export function isNetworkError(error: Error | ApiError): boolean {
  return (
    error.message.includes('network') ||
    error.message.includes('timeout') ||
    error.message.includes('abort') ||
    error.message.includes('offline') ||
    error.message.includes('connection') ||
    (error instanceof TypeError && error.message.includes('fetch'))
  )
}

/**
 * Create a function to handle API errors in components
 * @param options - Configuration options
 * @returns Error handling function
 */
export function createErrorHandler({
  showToast = true,
  logError = true,
  defaultMessage = 'An unexpected error occurred'
} = {}) {
  
  return function handleApiError(error: Error | ApiError | any, customMessage?: string): void {
    // Log error if requested
    if (logError) {
      console.error('API Error:', error)
    }
    
    // Show toast if requested
    if (showToast) {
      const { showErrorToast } = useToast()
      showErrorToast(customMessage || formatErrorMessage(error as ApiError) || defaultMessage)
    }
  }
}
