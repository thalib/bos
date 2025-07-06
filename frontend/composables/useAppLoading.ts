/**
 * Global Loading State Management Composable
 * Provides consistent loading states across the entire application
 */
import { ref, computed, watch, nextTick, readonly } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { useToast } from '~/utils/errorHandling'

// Global loading state store
interface LoadingState {
  id: string
  message: string
  type: 'auth' | 'route' | 'component' | 'page' | 'global'
  priority: number // Higher priority shows over lower priority
  timestamp: number
}

// Loading state priorities (higher number = higher priority)
const LOADING_PRIORITIES = {
  auth: 100,      // Authentication loading (highest priority)
  global: 90,     // Global app loading
  route: 80,      // Route transitions
  page: 70,       // Page loading
  component: 60   // Component loading (lowest priority)
} as const

// Global loading states store
const loadingStates = ref<Map<string, LoadingState>>(new Map())
const isInitialized = ref(false)

/**
 * Global App Loading State Management
 */
export const useAppLoading = () => {
  const { isAuthenticated, isInitialized: authInitialized } = useAuth()
  const { showErrorToast } = useToast()

  // Compute the current active loading state (highest priority)
  const currentLoadingState = computed(() => {
    if (loadingStates.value.size === 0) return null
    
    let highestPriority = -1
    let activeState: LoadingState | null = null
    
    for (const state of loadingStates.value.values()) {
      if (state.priority > highestPriority) {
        highestPriority = state.priority
        activeState = state
      }
    }
    
    return activeState
  })

  // Check if any loading is active
  const isLoading = computed(() => loadingStates.value.size > 0)

  // Check if authentication loading is active
  const isAuthLoading = computed(() => {
    return Array.from(loadingStates.value.values()).some(state => state.type === 'auth')
  })

  // Check if route loading is active
  const isRouteLoading = computed(() => {
    return Array.from(loadingStates.value.values()).some(state => state.type === 'route')
  })

  // Check if page loading is active
  const isPageLoading = computed(() => {
    return Array.from(loadingStates.value.values()).some(state => state.type === 'page')
  })

  // Check if component loading is active
  const isComponentLoading = computed(() => {
    return Array.from(loadingStates.value.values()).some(state => state.type === 'component')
  })

  // Global app ready state (authentication + initialization complete)
  const isAppReady = computed(() => {
    return authInitialized.value && !isAuthLoading.value && isInitialized.value
  })

  // Pages can show content (auth done, no blocking loading)
  const canShowPageContent = computed(() => {
    return isAppReady.value && !isRouteLoading.value
  })

  // Components can show content (page ready, no page loading)
  const canShowComponentContent = computed(() => {
    return canShowPageContent.value && !isPageLoading.value
  })

  /**
   * Start a loading state
   * @param type - The type of loading
   * @param message - Loading message to display
   * @param id - Optional custom ID (auto-generated if not provided)
   * @returns Loading state ID for later reference
   */
  const startLoading = (
    type: LoadingState['type'], 
    message: string, 
    id?: string
  ): string => {
    const stateId = id || `${type}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    
    const loadingState: LoadingState = {
      id: stateId,
      message,
      type,
      priority: LOADING_PRIORITIES[type],
      timestamp: Date.now()
    }
    
    loadingStates.value.set(stateId, loadingState)
    
    return stateId
  }

  /**
   * Stop a specific loading state
   * @param id - The loading state ID to stop
   */
  const stopLoading = (id: string): void => {
    loadingStates.value.delete(id)
  }

  /**
   * Stop all loading states of a specific type
   * @param type - The type of loading to stop
   */
  const stopLoadingByType = (type: LoadingState['type']): void => {
    for (const [id, state] of loadingStates.value.entries()) {
      if (state.type === type) {
        loadingStates.value.delete(id)
      }
    }
  }

  /**
   * Clear all loading states
   */
  const clearAllLoading = (): void => {
    loadingStates.value.clear()
  }

  /**
   * Authentication loading helpers
   */
  const startAuthLoading = (message = 'Checking authentication...'): string => {
    return startLoading('auth', message, 'auth-check')
  }

  const stopAuthLoading = (): void => {
    stopLoading('auth-check')
  }

  /**
   * Route transition loading helpers
   */
  const startRouteLoading = (message = 'Loading page...'): string => {
    return startLoading('route', message, 'route-transition')
  }

  const stopRouteLoading = (): void => {
    stopLoading('route-transition')
  }

  /**
   * Page loading helpers
   */
  const startPageLoading = (message = 'Loading content...'): string => {
    return startLoading('page', message)
  }

  const stopPageLoading = (id: string): void => {
    stopLoading(id)
  }

  /**
   * Component loading helpers
   */
  const startComponentLoading = (message = 'Loading...'): string => {
    return startLoading('component', message)
  }

  const stopComponentLoading = (id: string): void => {
    stopLoading(id)
  }

  /**
   * Handle loading errors with Toast notifications
   * @param error - The error that occurred
   * @param context - Context where the error occurred
   */
  const handleLoadingError = (error: Error | string, context = 'loading'): void => {
    const errorMessage = typeof error === 'string' ? error : error.message
    showErrorToast(`Failed ${context}: ${errorMessage}`)
    
    // Clear loading states on error
    clearAllLoading()
  }

  /**
   * Initialize global loading system
   */
  const initializeLoading = (): void => {
    if (isInitialized.value) return
    
    // Start auth loading if auth is not initialized
    if (!authInitialized.value) {
      startAuthLoading('Initializing authentication...')
    }
    
    isInitialized.value = true
  }

  // Watch auth initialization and clear auth loading when done
  watch(authInitialized, (initialized) => {
    if (initialized) {
      stopAuthLoading()
    }
  })

  // Auto-cleanup old loading states (prevent memory leaks)
  const cleanupOldStates = (): void => {
    const now = Date.now()
    const maxAge = 30000 // 30 seconds
    
    for (const [id, state] of loadingStates.value.entries()) {
      if (now - state.timestamp > maxAge) {
        loadingStates.value.delete(id)
      }
    }
  }

  // Cleanup old states every 10 seconds
  if (process.client) {
    setInterval(cleanupOldStates, 10000)
  }

  // Initialize on first use
  nextTick(() => {
    initializeLoading()
  })

  return {
    // State
    isLoading: readonly(isLoading),
    isAuthLoading: readonly(isAuthLoading),
    isRouteLoading: readonly(isRouteLoading),
    isPageLoading: readonly(isPageLoading),
    isComponentLoading: readonly(isComponentLoading),
    isAppReady: readonly(isAppReady),
    canShowPageContent: readonly(canShowPageContent),
    canShowComponentContent: readonly(canShowComponentContent),
    currentLoadingState: readonly(currentLoadingState),
    
    // General loading controls
    startLoading,
    stopLoading,
    stopLoadingByType,
    clearAllLoading,
    
    // Specific loading helpers
    startAuthLoading,
    stopAuthLoading,
    startRouteLoading,
    stopRouteLoading,
    startPageLoading,
    stopPageLoading,
    startComponentLoading,
    stopComponentLoading,
    
    // Error handling
    handleLoadingError,
    
    // Initialization
    initializeLoading
  }
}
