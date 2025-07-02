/**
 * API Helper Utilities
 * Extension utilities for the base API service with error handling integration
 */
import { ref, computed } from 'vue'
import { useApiService } from '~/services/api'
import { withRetry, useToast, useOfflineQueue, formatErrorMessage } from '~/utils/errorHandling'
import type { ApiResponse, ApiError } from '~/types'

/**
 * Creates an API request with error handling, retry capabilities, and offline support
 * @param apiCall - The API call function to execute
 * @param options - Options for error handling, retries, and notifications
 * @returns The API response with data, error, and loading state
 */
export async function createSafeApiRequest<T>(
  apiCall: () => Promise<ApiResponse<T>>,
  options: {
    showErrorToast?: boolean;
    successMessage?: string;
    retry?: {
      maxRetries?: number;
      retryDelay?: number;
      shouldRetry?: (error: ApiError | Error) => boolean;
    };
    offlineSupport?: boolean;
  } = {}
): Promise<ApiResponse<T>> {
  const {
    showErrorToast = true,
    successMessage,
    retry = {},
    offlineSupport = false
  } = options
  
  const { showErrorToast: displayErrorToast, showSuccessToast } = useToast()
  const { isOnline, queueRequest } = useOfflineQueue()
  
  // Handle offline scenario
  if (offlineSupport && !isOnline.value) {
    queueRequest(apiCall)
    displayErrorToast('You are currently offline. This request will be queued and sent when you are back online.')
    
    return {
      data: null,
      error: new Error('Offline. Request queued.') as ApiError,
      loading: false
    }
  }
  
  // Execute the API call with retry logic
  const response = await withRetry(apiCall, retry)
  
  // Handle error toast if needed
  if (response.error && showErrorToast) {
    displayErrorToast(response.error)
  }
  
  // Show success toast if provided and no error
  if (!response.error && successMessage) {
    showSuccessToast(successMessage)
  }
  
  return response
}

/**
 * Higher-order function to wrap API methods with safe handling
 * @param apiMethod - Original API method
 * @param options - Options for error handling
 * @returns Wrapped API method with error handling
 */
export function withErrorHandling<T extends (...args: any[]) => Promise<ApiResponse<any>>>(
  apiMethod: T,
  options: {
    showErrorToast?: boolean;
    successMessage?: string | ((result: any) => string);
    retry?: {
      maxRetries?: number;
      retryDelay?: number;
      shouldRetry?: (error: ApiError | Error) => boolean;
    };
    offlineSupport?: boolean;
  } = {}
): T {
  // Return a function with the same signature as the original
  return (async (...args: Parameters<T>) => {
    // Create options for our safe API request
    const safeOptions = {
      ...options,
      // If successMessage is a function, we'll call it with the result
      successMessage: typeof options.successMessage === 'function' 
        ? undefined 
        : options.successMessage
    }
    
    // Execute the wrapped API call
    const response = await createSafeApiRequest(
      () => apiMethod(...args),
      safeOptions
    )
    
    // Handle dynamic success messages
    if (response.data && typeof options.successMessage === 'function') {
      const { showSuccessToast } = useToast()
      showSuccessToast(options.successMessage(response.data))
    }
    
    return response
  }) as T
}

/**
 * Composable for enhanced API requests with error handling
 * @returns Enhanced API methods with error handling
 */
export function useApiWithErrorHandling() {
  const api = useApiService()
  
  // Wrap the request method with error handling
  const safeRequest = withErrorHandling(api.request)
  
  return {
    ...api,
    safeRequest
  }
}

/**
 * Hook to handle API loading state with feedback
 * @returns Loading state and methods to manage it
 */
export function useApiLoading() {
  const isLoading = ref(false)
  const loadingMessage = ref('Loading...')
  
  /**
   * Execute a function with loading state management
   * @param fn - Function to execute
   * @param message - Loading message to display
   * @returns Result of the function
   */
  const withLoading = async <T>(
    fn: () => Promise<T>,
    message = 'Loading...'
  ): Promise<T> => {
    isLoading.value = true
    loadingMessage.value = message
    
    try {
      return await fn()
    } finally {
      isLoading.value = false
    }
  }
  
  return {
    isLoading,
    loadingMessage,
    withLoading
  }
}

/**
 * Create validation error handling for forms
 * @returns Validation error state and methods
 */
export function useFormValidation() {
  const validationErrors = ref<Record<string, string>>({})
  const hasValidationErrors = computed(() => Object.keys(validationErrors.value).length > 0)
  
  /**
   * Handle API error and extract validation errors
   * @param error - API error object
   */
  const handleValidationError = (error: ApiError | Error | null) => {
    // Clear previous errors
    validationErrors.value = {}
    
    if (!error) return
    
    // Check for validation errors
    if ('validationErrors' in error && error.validationErrors) {
      // Format Laravel validation errors for form display
      Object.entries(error.validationErrors).forEach(([field, messages]) => {
        validationErrors.value[field] = Array.isArray(messages) ? messages[0] : messages
      })
    }
  }
    /**
   * Get error for a specific field
   * @param field - Form field name
   * @returns Error message or empty string
   */
  const getFieldError = (field: string): string => {
    return validationErrors.value[field] || ''
  }
  
  /**
   * Check if a field has an error
   * @param field - Form field name
   * @returns Whether the field has an error
   */
  const hasFieldError = (field: string): boolean => {
    return !!validationErrors.value[field]
  }
  
  /**
   * Clear all validation errors
   */
  const clearValidationErrors = () => {
    validationErrors.value = {}
  }
  
  return {
    validationErrors,
    hasValidationErrors,
    handleValidationError,
    getFieldError,
    hasFieldError,
    clearValidationErrors
  }
}
