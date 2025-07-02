import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

// Types
export type FilterValue = 'all' | 'active' | 'inactive'

export interface UseResourceFilterOptions {
  defaultFilter?: FilterValue
  paramName?: string
}

export interface UseResourceFilterReturn {
  // State
  currentFilter: Ref<FilterValue>
  
  // Computed
  isActive: ComputedRef<boolean>
  isInactive: ComputedRef<boolean>
  isAll: ComputedRef<boolean>
  filterLabel: ComputedRef<string>
  
  // Methods
  setFilter: (filter: FilterValue) => Promise<void>
  updateUrl: (filter: FilterValue) => Promise<void>
  resetFilter: () => Promise<void>
  
  // Utilities
  getFilterFromUrl: () => FilterValue
  isValidFilter: (value: string) => value is FilterValue
}

/**
 * Composable for managing resource filter state with URL synchronization
 * 
 * @param options Configuration options
 * @returns Filter state management utilities
 */
export const useResourceFilter = (options: UseResourceFilterOptions = {}): UseResourceFilterReturn => {
  const {
    defaultFilter = 'active',
    paramName = 'filter'
  } = options

  const route = useRoute()
  const router = useRouter()

  // Validate filter value
  const isValidFilter = (value: string): value is FilterValue => {
    return ['all', 'active', 'inactive'].includes(value)
  }

  // Get filter from URL or use default
  const getFilterFromUrl = (): FilterValue => {
    const urlFilter = route.query[paramName] as string
    return isValidFilter(urlFilter) ? urlFilter : defaultFilter
  }

  // Reactive state
  const currentFilter = ref<FilterValue>(getFilterFromUrl())

  // Computed properties
  const isActive = computed(() => currentFilter.value === 'active')
  const isInactive = computed(() => currentFilter.value === 'inactive')
  const isAll = computed(() => currentFilter.value === 'all')

  const filterLabel = computed(() => {
    switch (currentFilter.value) {
      case 'active':
        return 'Active'
      case 'inactive':
        return 'Inactive'
      case 'all':
        return 'All'
      default:
        return 'Active'
    }
  })

  // Update URL with new filter
  const updateUrl = async (filter: FilterValue): Promise<void> => {
    try {
      const query = { ...route.query }
      
      if (filter === defaultFilter) {
        // Remove filter parameter if it's the default value
        delete query[paramName]
      } else {
        query[paramName] = filter
      }

      await router.push({
        path: route.path,
        query
      })
    } catch (error) {
      console.error('Error updating URL with filter:', error)
    }
  }

  // Set filter and update URL
  const setFilter = async (filter: FilterValue): Promise<void> => {
    if (!isValidFilter(filter)) {
      console.warn(`Invalid filter value: ${filter}. Using default: ${defaultFilter}`)
      filter = defaultFilter
    }

    currentFilter.value = filter
    await updateUrl(filter)
  }

  // Reset to default filter
  const resetFilter = async (): Promise<void> => {
    await setFilter(defaultFilter)
  }

  // Watch for URL changes and sync state
  watch(
    () => route.query[paramName],
    (newFilter) => {
      const filterValue = isValidFilter(newFilter as string) 
        ? (newFilter as FilterValue)
        : defaultFilter
      
      if (currentFilter.value !== filterValue) {
        currentFilter.value = filterValue
      }
    },
    { immediate: true }
  )

  // Watch for route path changes and reset filter if needed
  watch(
    () => route.path,
    () => {
      // Sync state with URL when navigating to a new page
      const urlFilter = getFilterFromUrl()
      if (currentFilter.value !== urlFilter) {
        currentFilter.value = urlFilter
      }
    }
  )
  return {
    // State
    currentFilter: readonly(currentFilter),
    
    // Computed
    isActive,
    isInactive,
    isAll,
    filterLabel,
    
    // Methods
    setFilter,
    updateUrl,
    resetFilter,
    
    // Utilities
    getFilterFromUrl,
    isValidFilter
  }
}
