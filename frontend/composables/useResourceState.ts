import { ref, computed, watch, nextTick, readonly, type ComputedRef, type Ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { PaginationMeta } from '~/types'

// Simple debounce implementation to avoid external dependencies
function debounce<T extends (...args: any[]) => any>(func: T, delay: number): T {
  let timeoutId: ReturnType<typeof setTimeout>
  return ((...args: any[]) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => func(...args), delay)
  }) as T
}

// TypeScript interfaces for state management
export interface ResourceStateConfig {
  resource: string
  defaults?: {
    perPage?: number
    sortDirection?: 'asc' | 'desc'
  }
  persistence?: {
    enabled?: boolean
    key?: string
  }
  debounce?: {
    enabled?: boolean
    delay?: number
  }
}

export interface ResourceState {
  // Search state  
  searchQuery: Ref<string>
  isSearching: Ref<boolean>
  
  // Sort state
  sortField: Ref<string>
  sortDirection: Ref<'asc' | 'desc'>
  isSorting: Ref<boolean>
  
  // Pagination state
  pagination: Ref<PaginationMeta & {
    from: number
    to: number
  }>
  
  // Loading states
  loading: Ref<boolean>
}

export interface ResourceStateActions {
  // State update methods
  updateSearch: (query: string) => Promise<void>
  updateSort: (field: string, direction?: 'asc' | 'desc') => Promise<void>
  updatePagination: (page: number, perPage?: number) => Promise<void>
  clearFilters: () => Promise<void>
  initializeFromURL: () => void
  
  // URL synchronization
  syncToURL: () => void
  
  // Persistence
  saveState: () => void
  loadState: () => void
  clearPersistedState: () => void
  
  // Validation
  validateState: () => boolean
}

export interface ResourceStateReturns extends ResourceState, ResourceStateActions {
  // Computed properties
  hasSearchQuery: Readonly<ComputedRef<boolean>>
  hasSearchResults: Readonly<ComputedRef<boolean>>
  hasNoSearchResults: Readonly<ComputedRef<boolean>>
  hasSortApplied: Readonly<ComputedRef<boolean>>
  hasActiveFilters: Readonly<ComputedRef<boolean>>
  activeFiltersCount: Readonly<ComputedRef<number>>
  currentPage: Readonly<ComputedRef<number>>
  currentPerPage: Readonly<ComputedRef<number>>
}

/**
 * Resource State Management Composable
 * 
 * Manages URL state synchronization, persistence, and validation
 * for resource pages including search, sort, and pagination parameters.
 * 
 * @param config - Configuration object for the composable
 * @returns Resource state and actions
 */
export function useResourceState(config: ResourceStateConfig): ResourceStateReturns {
  const route = useRoute()
  const router = useRouter()
  
  // Configuration with defaults
  const settings = {
    defaults: {
      perPage: 20,
      sortDirection: 'asc' as const,
      ...config.defaults
    },
    persistence: {
      enabled: true,
      key: `resource-state-${config.resource}`,
      ...config.persistence
    },
    debounce: {
      enabled: true,
      delay: 300,
      ...config.debounce
    }
  }
  
  // Reactive state for search, sort, pagination parameters
  const searchQuery = ref<string>('')
  const isSearching = ref<boolean>(false)
  
  const sortField = ref<string>('')
  const sortDirection = ref<'asc' | 'desc'>(settings.defaults.sortDirection)
  const isSorting = ref<boolean>(false)
  
  const paginationData = ref<PaginationMeta & { from: number; to: number }>({
    currentPage: 1,
    totalPages: 1,
    perPage: settings.defaults.perPage,
    total: 0,
    hasNextPage: false,
    hasPrevPage: false,
    nextPage: null,
    prevPage: null,
    from: 0,
    to: 0
  })
  
  const loading = ref<boolean>(false)
  
  // Computed properties
  const hasSearchQuery = computed(() => searchQuery.value.trim().length > 0)
  const hasSearchResults = computed(() => hasSearchQuery.value && paginationData.value.total > 0)
  const hasNoSearchResults = computed(() => hasSearchQuery.value && paginationData.value.total === 0 && !loading.value)
  const hasSortApplied = computed(() => sortField.value.length > 0)
  const hasActiveFilters = computed(() => hasSearchQuery.value || hasSortApplied.value)
  const activeFiltersCount = computed(() => {
    let count = 0
    if (hasSearchQuery.value) count++
    if (hasSortApplied.value) count++
    return count
  })
  const currentPage = computed(() => paginationData.value.currentPage)
  const currentPerPage = computed(() => paginationData.value.perPage)
    // URL synchronization with debouncing
  const syncToURL = debounce(() => {
    const query: Record<string, string> = {}
    
    // Copy existing query parameters safely
    Object.keys(route.query).forEach(key => {
      const value = route.query[key]
      if (typeof value === 'string') {
        query[key] = value
      } else if (Array.isArray(value) && value.length > 0 && typeof value[0] === 'string') {
        query[key] = value[0]
      }
    })
    
    // Handle search parameter
    if (searchQuery.value.trim()) {
      query.search = searchQuery.value.trim()
    } else {
      delete query.search
    }
    
    // Handle sort parameters
    if (sortField.value.trim()) {
      query.sort = sortField.value.trim()
      query.direction = sortDirection.value
    } else {
      delete query.sort
      delete query.direction
    }
    
    // Handle pagination parameters
    if (paginationData.value.currentPage > 1) {
      query.page = paginationData.value.currentPage.toString()
    } else {
      delete query.page
    }
    
    if (paginationData.value.perPage !== settings.defaults.perPage) {
      query.perPage = paginationData.value.perPage.toString()
    } else {
      delete query.perPage
    }
    
    // Use router.replace to prevent history pollution
    router.replace({ query })
  }, settings.debounce.enabled ? settings.debounce.delay : 0)
  
  // State persistence in localStorage
  const saveState = () => {
    if (!settings.persistence.enabled || typeof window === 'undefined') return
    
    try {
      const state = {
        searchQuery: searchQuery.value,
        sortField: sortField.value,
        sortDirection: sortDirection.value,
        perPage: paginationData.value.perPage,
        timestamp: Date.now()
      }
      
      localStorage.setItem(settings.persistence.key, JSON.stringify(state))
    } catch (error) {
      console.warn('Failed to save resource state to localStorage:', error)
    }
  }
  
  const loadState = () => {
    if (!settings.persistence.enabled || typeof window === 'undefined') return
    
    try {
      const savedState = localStorage.getItem(settings.persistence.key)
      if (!savedState) return
      
      const state = JSON.parse(savedState)
      
      // Check if state is not too old (24 hours)
      const isStale = Date.now() - (state.timestamp || 0) > 24 * 60 * 60 * 1000
      if (isStale) {
        clearPersistedState()
        return
      }
      
      // Only restore non-URL parameters (like perPage preference)
      if (state.perPage && state.perPage !== settings.defaults.perPage) {
        paginationData.value.perPage = state.perPage
      }
    } catch (error) {
      console.warn('Failed to load resource state from localStorage:', error)
      clearPersistedState()
    }
  }
  
  const clearPersistedState = () => {
    if (!settings.persistence.enabled || typeof window === 'undefined') return
    
    try {
      localStorage.removeItem(settings.persistence.key)
    } catch (error) {
      console.warn('Failed to clear persisted resource state:', error)
    }
  }
  
  // URL parameter validation
  const validateState = (): boolean => {
    try {
      // Validate search query
      if (typeof searchQuery.value !== 'string') return false
      
      // Validate sort parameters
      if (sortField.value && typeof sortField.value !== 'string') return false
      if (sortDirection.value && !['asc', 'desc'].includes(sortDirection.value)) return false
      
      // Validate pagination parameters
      if (paginationData.value.currentPage < 1) return false
      if (paginationData.value.perPage < 1) return false
      
      return true
    } catch (error) {
      console.warn('State validation failed:', error)
      return false
    }
  }
  
  // Initialize state from current URL on page load
  const initializeFromURL = () => {
    try {
      // Set search query from URL if present
      if (route.query.search && typeof route.query.search === 'string') {
        searchQuery.value = route.query.search
      }
      
      // Set sort parameters from URL if present
      if (route.query.sort && typeof route.query.sort === 'string') {
        sortField.value = route.query.sort
      }
      
      if (route.query.direction && typeof route.query.direction === 'string') {
        const direction = route.query.direction as 'asc' | 'desc'
        if (['asc', 'desc'].includes(direction)) {
          sortDirection.value = direction
        }
      }
      
      // Set page from URL if present
      if (route.query.page && typeof route.query.page === 'string') {
        const pageNum = parseInt(route.query.page, 10)
        if (!isNaN(pageNum) && pageNum > 0) {
          paginationData.value.currentPage = pageNum
        }
      }
      
      // Set perPage from URL if present
      if (route.query.perPage && typeof route.query.perPage === 'string') {
        const perPageNum = parseInt(route.query.perPage, 10)
        if (!isNaN(perPageNum) && perPageNum > 0) {
          paginationData.value.perPage = perPageNum
        }
      }
      
      // Validate the initialized state
      if (!validateState()) {
        console.warn('Invalid state detected, resetting to defaults')
        clearFilters()
      }
    } catch (error) {
      console.error('Failed to initialize state from URL:', error)
      clearFilters()
    }
  }
  
  // State update methods
  const updateSearch = async (query: string): Promise<void> => {
    if (!query.trim()) {
      return clearFilters()
    }
    
    isSearching.value = true
    searchQuery.value = query.trim()
    
    // Reset pagination to first page when searching
    paginationData.value.currentPage = 1
    
    // Sync to URL and save state
    syncToURL()
    saveState()
    
    await nextTick()
    isSearching.value = false
  }
  
  const updateSort = async (field: string, direction?: 'asc' | 'desc'): Promise<void> => {
    isSorting.value = true
    
    // Toggle direction if same column, otherwise start with 'asc'
    if (sortField.value === field) {
      sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortField.value = field
      sortDirection.value = direction || 'asc'
    }
    
    // Reset pagination to first page when sorting
    paginationData.value.currentPage = 1
    
    // Sync to URL and save state
    syncToURL()
    saveState()
    
    await nextTick()
    isSorting.value = false
  }
  
  const updatePagination = async (page: number, perPage?: number): Promise<void> => {
    // Update pagination data
    paginationData.value.currentPage = page
    
    if (perPage && perPage !== paginationData.value.perPage) {
      paginationData.value.perPage = perPage
      // Reset to first page when changing perPage
      paginationData.value.currentPage = 1
    }
    
    // Sync to URL and save state
    syncToURL()
    saveState()
    
    await nextTick()
  }
  
  const clearFilters = async (): Promise<void> => {
    searchQuery.value = ''
    sortField.value = ''
    sortDirection.value = settings.defaults.sortDirection
    paginationData.value.currentPage = 1
    
    isSearching.value = false
    isSorting.value = false
    
    // Sync to URL (will remove all filter parameters)
    syncToURL()
    saveState()
    
    await nextTick()
  }
  
  // Route change watchers with debouncing
  watch(() => route.query, (newQuery) => {
    // Only sync if the query actually changed to avoid infinite loops
    const currentParams = {
      search: searchQuery.value,
      sort: sortField.value,
      direction: sortDirection.value,
      page: paginationData.value.currentPage.toString(),
      perPage: paginationData.value.perPage.toString()
    }
    
    const urlParams = {
      search: newQuery.search as string || '',
      sort: newQuery.sort as string || '',
      direction: newQuery.direction as string || settings.defaults.sortDirection,
      page: newQuery.page as string || '1',
      perPage: newQuery.perPage as string || settings.defaults.perPage.toString()
    }
    
    // Check if we need to sync from URL
    const needsSync = Object.keys(currentParams).some(key => {
      return currentParams[key as keyof typeof currentParams] !== urlParams[key as keyof typeof urlParams]
    })
    
    if (needsSync) {
      initializeFromURL()
    }
  }, { immediate: false })
  
  // Auto-save state changes
  watch([searchQuery, sortField, sortDirection, () => paginationData.value.perPage], () => {
    saveState()
  })
  
  // Initialize from localStorage on mount
  if (typeof window !== 'undefined') {
    loadState()
  }
  return {
    // State
    searchQuery,
    isSearching,
    sortField,
    sortDirection,
    isSorting,
    pagination: paginationData,
    loading,
    
    // Computed properties
    hasSearchQuery,
    hasSearchResults,
    hasNoSearchResults,
    hasSortApplied,
    hasActiveFilters,
    activeFiltersCount,
    currentPage,
    currentPerPage,
    
    // Actions
    updateSearch,
    updateSort,
    updatePagination,
    clearFilters,
    initializeFromURL,
    syncToURL,
    saveState,
    loadState,
    clearPersistedState,
    validateState
  }
}
