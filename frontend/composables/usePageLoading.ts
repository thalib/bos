/**
 * Page Loading Helper Composable
 * Provides easy-to-use loading helpers for pages and components
 */
import { ref, onUnmounted } from 'vue'
import { useAppLoading } from '~/composables/useAppLoading'
import { useToast } from '~/utils/errorHandling'

export const usePageLoading = () => {
  const { 
    startPageLoading, 
    stopPageLoading, 
    startComponentLoading, 
    stopComponentLoading,
    handleLoadingError 
  } = useAppLoading()
  
  const { showErrorToast } = useToast()

  // Track active loading states for automatic cleanup
  const activeLoadingStates = ref<Set<string>>(new Set())

  /**
   * Start page-level loading
   * @param message - Loading message to display
   * @returns Loading state ID
   */
  const startPageLoad = (message = 'Loading content...'): string => {
    const id = startPageLoading(message)
    activeLoadingStates.value.add(id)
    return id
  }

  /**
   * Stop page-level loading
   * @param id - Loading state ID to stop
   */
  const stopPageLoad = (id: string): void => {
    stopPageLoading(id)
    activeLoadingStates.value.delete(id)
  }

  /**
   * Start component-level loading
   * @param message - Loading message to display
   * @returns Loading state ID
   */
  const startComponentLoad = (message = 'Loading...'): string => {
    const id = startComponentLoading(message)
    activeLoadingStates.value.add(id)
    return id
  }

  /**
   * Stop component-level loading
   * @param id - Loading state ID to stop
   */
  const stopComponentLoad = (id: string): void => {
    stopComponentLoading(id)
    activeLoadingStates.value.delete(id)
  }

  /**
   * Wrapper for async operations with automatic loading management
   * @param operation - Async operation to execute
   * @param options - Loading options
   */
  const withLoading = async <T>(
    operation: () => Promise<T>,
    options: {
      message?: string
      type?: 'page' | 'component'
      onError?: (error: Error) => void
    } = {}
  ): Promise<T | null> => {
    const { 
      message = 'Loading...', 
      type = 'component',
      onError 
    } = options

    const loadingId = type === 'page' 
      ? startPageLoad(message)
      : startComponentLoad(message)

    try {
      const result = await operation()
      return result
    } catch (error) {
      const errorObj = error instanceof Error ? error : new Error(String(error))
      
      if (onError) {
        onError(errorObj)
      } else {
        handleLoadingError(errorObj, type === 'page' ? 'loading page' : 'loading component')
      }
      
      return null
    } finally {
      if (type === 'page') {
        stopPageLoad(loadingId)
      } else {
        stopComponentLoad(loadingId)
      }
    }
  }

  /**
   * Wrapper for API calls with loading management
   * @param apiCall - API function to execute
   * @param options - Loading options
   */
  const withApiLoading = async <T>(
    apiCall: () => Promise<{ data?: T; error?: any }>,
    options: {
      message?: string
      type?: 'page' | 'component'
      successMessage?: string
      onSuccess?: (data: T) => void
      onError?: (error: any) => void
    } = {}
  ): Promise<T | null> => {
    const { 
      message = 'Loading data...', 
      type = 'component',
      successMessage,
      onSuccess,
      onError 
    } = options

    return withLoading(async () => {
      const response = await apiCall()
      
      if (response.error) {
        const errorMessage = response.error.message || 'An error occurred'
        showErrorToast(errorMessage)
        if (onError) {
          onError(response.error)
        }
        throw new Error(errorMessage)
      }

      if (response.data) {
        if (successMessage) {
          const { showSuccessToast } = useToast()
          showSuccessToast(successMessage)
        }
        if (onSuccess) {
          onSuccess(response.data)
        }
        return response.data
      }

      throw new Error('No data received')
    }, { message, type, onError })
  }

  /**
   * Clean up all active loading states
   */
  const cleanup = (): void => {
    for (const id of activeLoadingStates.value) {
      if (id.includes('page-')) {
        stopPageLoading(id)
      } else {
        stopComponentLoading(id)
      }
    }
    activeLoadingStates.value.clear()
  }

  // Auto-cleanup on component unmount
  onUnmounted(() => {
    cleanup()
  })

  return {
    // Basic loading controls
    startPageLoad,
    stopPageLoad,
    startComponentLoad,
    stopComponentLoad,
    
    // High-level helpers
    withLoading,
    withApiLoading,
    
    // Cleanup
    cleanup
  }
}
