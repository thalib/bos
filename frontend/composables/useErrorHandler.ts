/**
 * Error Handler Composable
 * Provides access to global error handling functionality
 */
import type { ComputedRef } from 'vue';

// Re-export the error handler utilities from the plugin
export { useErrorHandler } from '~/plugins/error-handler.client';

/**
 * Quick access to error handler instance
 * Automatically handles the case where error handler might not be available
 */
export const useGlobalErrorHandler = () => {
  const nuxtApp = useNuxtApp();
  
  // Return the error handler if available, otherwise return safe defaults
  if (nuxtApp?.$errorHandler) {
    return nuxtApp.$errorHandler;
  }
  
  // Fallback implementation when error handler is not available
  const fallbackHandler = {
    logError: (entry: any) => {
      console.warn('Error handler not available, logging to console:', entry);
    },
    handleApiError: (error: any, context?: any) => {
      console.error('API Error (fallback):', error, context);
    },
    handleNetworkError: (error: any) => {
      console.error('Network Error (fallback):', error);
    },
    handleRouteError: (error: any) => {
      console.error('Route Error (fallback):', error);
    },
    navigateTo404: async (reason: string) => {
      console.warn('Navigate to 404 (fallback):', reason);
      await navigateTo('/404');
    },
    errorLogs: computed(() => []),
    isInitialized: computed(() => false),
    enableDebug: () => {},
    disableDebug: () => {},
    clearLogs: () => {}
  };
  
  return fallbackHandler;
};
