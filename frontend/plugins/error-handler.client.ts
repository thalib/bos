/**
 * Global Error Handler Plugin
 * Handles 404 errors, API errors, and route errors while preserving authentication state
 */
import { ref, computed } from 'vue';
import type { ApiError, ApiResponse } from '~/types';
import { formatErrorMessage, useToast } from '~/utils/errorHandling';
import { useAuth } from '~/composables/useAuth';

// Error handling state
const isErrorHandlerInitialized = ref(false);
const errorHandlerDebug = ref(false);

// Error logging utilities
interface ErrorLogEntry {
  timestamp: string;
  type: 'route' | 'api' | 'network' | 'javascript';
  status?: number;
  url?: string;
  method?: string;
  message: string;
  userAgent: string;
  authenticated: boolean;
  userId?: string | number;
}

const errorLogs = ref<ErrorLogEntry[]>([]);
const maxErrorLogs = 100; // Keep only last 100 errors in memory

/**
 * Safely log errors without exposing sensitive information
 */
const logError = (entry: Omit<ErrorLogEntry, 'timestamp' | 'userAgent' | 'authenticated' | 'userId'>): void => {
  try {
    const { isAuthenticated, user } = useAuth();
    
    const logEntry: ErrorLogEntry = {
      ...entry,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent.substring(0, 100), // Truncate user agent
      authenticated: isAuthenticated.value,
      userId: isAuthenticated.value && user.value ? user.value.id : undefined
    };
    
    // Add to in-memory log (for debugging)
    errorLogs.value.unshift(logEntry);
    if (errorLogs.value.length > maxErrorLogs) {
      errorLogs.value = errorLogs.value.slice(0, maxErrorLogs);
    }
    
    // Console log in development (sanitized)
    if (process.dev || errorHandlerDebug.value) {
      console.group(`🚨 Error Handler: ${entry.type.toUpperCase()}`);
      console.log('Type:', entry.type);
      console.log('Status:', entry.status || 'N/A');
      console.log('URL:', entry.url || 'N/A');
      console.log('Method:', entry.method || 'N/A');
      console.log('Message:', entry.message);
      console.log('Authenticated:', logEntry.authenticated);
      console.log('Timestamp:', logEntry.timestamp);
      console.groupEnd();
    }
    
    // In production, you might want to send this to an error tracking service
    // Example: Send to error tracking service (commented out)
    // if (process.env.NODE_ENV === 'production') {
    //   sendToErrorTracking(logEntry);
    // }
    
  } catch (loggingError) {
    // Prevent infinite error loops from logging errors
    console.error('Error logging failed:', loggingError);
  }
};

/**
 * Check if current route is the 404 page to prevent redirect loops
 */
const isOn404Page = (): boolean => {
  if (typeof window === 'undefined') return false;
  return window.location.pathname === '/404';
};

/**
 * Safe navigation to 404 page
 */
const navigateTo404 = async (reason: string): Promise<void> => {
  if (isOn404Page()) {
    logError({
      type: 'route',
      status: 404,
      url: window.location.href,
      message: `404 redirect loop prevented: ${reason}`
    });
    return;
  }
  
  try {
    const router = useRouter();
    await router.push('/404');
    
    logError({
      type: 'route',
      status: 404,
      url: window.location.href,
      message: `Redirected to 404: ${reason}`
    });
  } catch (navigationError) {
    logError({
      type: 'route',
      status: 500,
      url: window.location.href,
      message: `Failed to navigate to 404 page: ${navigationError}`
    });
    
    // Fallback: Use window.location if router fails
    if (typeof window !== 'undefined') {
      window.location.href = '/404';
    }
  }
};

/**
 * Handle API response errors
 */
const handleApiError = async (error: any, context?: { url?: string; method?: string }): Promise<void> => {
  const { showErrorToast } = useToast();
  
  try {
    // Extract error information
    const status = error.status || error.statusCode || 0;
    const message = error.message || error.data?.message || 'Unknown API error';
    const url = context?.url || error.url || 'Unknown URL';
    const method = context?.method || error.method || 'Unknown Method';
    
    // Log the error
    logError({
      type: 'api',
      status,
      url,
      method,
      message: `API Error: ${message}`
    });
    
    // Handle specific status codes
    switch (status) {
      case 404:
        // For API 404s, show toast but don't redirect unless it's a route request
        if (url.includes('/api/')) {
          showErrorToast('The requested resource was not found');
        } else {
          // If it's a route/page request, redirect to 404
          await navigateTo404(`API 404 for non-API route: ${url}`);
        }
        break;
        
      case 401:
        // Authentication error - let the auth service handle it
        logError({
          type: 'api',
          status: 401,
          url,
          method,
          message: 'Authentication error - handled by auth service'
        });
        break;
        
      case 403:
        showErrorToast('You do not have permission to access this resource');
        break;
        
      case 500:
      case 502:
      case 503:
      case 504:
        showErrorToast('Server error. Please try again later.');
        break;
        
      default:
        if (status >= 400) {
          const userMessage = formatErrorMessage(error as ApiError);
          showErrorToast(userMessage);
        }
    }
  } catch (handlingError) {
    logError({
      type: 'javascript',
      message: `Error handler failed: ${handlingError}`
    });
  }
};

/**
 * Handle network errors
 */
const handleNetworkError = (error: any): void => {
  const { showErrorToast } = useToast();
  
  logError({
    type: 'network',
    message: `Network error: ${error.message || 'Connection failed'}`
  });
  
  // Show user-friendly message for network errors
  showErrorToast('Network connection error. Please check your internet connection.');
};

/**
 * Handle JavaScript runtime errors
 */
const handleJavaScriptError = (error: ErrorEvent | Error): void => {
  const message = error instanceof ErrorEvent 
    ? `${error.message} at ${error.filename}:${error.lineno}:${error.colno}`
    : error.message;
    
  logError({
    type: 'javascript',
    message: `JavaScript error: ${message}`
  });
  
  // In development, show more details
  if (process.dev) {
    console.error('JavaScript Error:', error);
  }
};

/**
 * Handle route errors and 404s
 */
const handleRouteError = async (error: any): Promise<void> => {
  const status = error.statusCode || error.status || 404;
  const url = error.url || (typeof window !== 'undefined' ? window.location.href : 'Unknown');
  
  logError({
    type: 'route',
    status,
    url,
    message: `Route error: ${error.message || 'Page not found'}`
  });
  
  if (status === 404) {
    await navigateTo404(`Route not found: ${url}`);
  }
};

/**
 * Setup global error handlers
 */
const setupErrorHandlers = (): void => {
  if (typeof window === 'undefined' || isErrorHandlerInitialized.value) {
    return;
  }
  
  // Handle unhandled JavaScript errors
  window.addEventListener('error', (event: ErrorEvent) => {
    handleJavaScriptError(event);
  });
  
  // Handle unhandled promise rejections
  window.addEventListener('unhandledrejection', (event: PromiseRejectionEvent) => {
    const error = event.reason;
    
    // Check if this is a network error
    if (error instanceof TypeError && error.message.includes('fetch')) {
      handleNetworkError(error);
      event.preventDefault(); // Prevent console log
      return;
    }
    
    // Check if this is an API error
    if (error && typeof error === 'object' && 'status' in error) {
      handleApiError(error);
      event.preventDefault(); // Prevent console log
      return;
    }
    
    // Handle as general JavaScript error
    handleJavaScriptError(error instanceof Error ? error : new Error(String(error)));
    
    // Prevent the default behavior (console.error) in production
    if (!process.dev) {
      event.preventDefault();
    }
  });
  
  isErrorHandlerInitialized.value = true;
  
  logError({
    type: 'javascript',
    message: 'Global error handlers initialized'
  });
};

/**
 * Provide error handler utilities for composables
 */
export const useErrorHandler = () => {
  return {
    logError,
    handleApiError,
    handleNetworkError,
    handleRouteError,
    navigateTo404,
    errorLogs: computed(() => errorLogs.value),
    isInitialized: computed(() => isErrorHandlerInitialized.value),
    
    // Debug utilities
    enableDebug: () => { errorHandlerDebug.value = true; },
    disableDebug: () => { errorHandlerDebug.value = false; },
    clearLogs: () => { errorLogs.value = []; }
  };
};

/**
 * Nuxt plugin definition
 */
export default defineNuxtPlugin({
  name: 'error-handler',
  parallel: false, // Ensure this runs in sequence
  setup() {
    // Only run on client side
    if (import.meta.client) {
      // Setup global error handlers
      setupErrorHandlers();
      
      // Handle router errors
      const router = useRouter();
      router.onError((error) => {
        handleRouteError(error);
      });
      
      // Provide global error handler instance
      return {
        provide: {
          errorHandler: useErrorHandler()
        }
      };
    }
  }
});
