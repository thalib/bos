/**
 * Navigation composable for the Thanzil project
 * Handles dynamic menu items from the backend API
 */
import { ref, computed, watch, readonly } from 'vue'
import type { MenuItemType, MenuResponse, MenuItem } from '~/types'
import { useAuth } from '~/composables/useAuth'
import { useApiService } from '~/services/api'

/**
 * Navigation composable for managing menu items
 */
export const useNavigation = () => {
  const { isAuthenticated, getTokens } = useAuth()
  const { request } = useApiService()
  
  // Reactive state
  const rawMenuItems = ref<MenuItemType[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // Cache key for session storage
  const CACHE_KEY = 'navigation-menu-items'
  const CACHE_TIMESTAMP_KEY = 'navigation-menu-timestamp'
  const CACHE_DURATION = 30 * 60 * 1000 // 30 minutes in milliseconds
  
  /**
   * Get all navigation items as flat list (for backward compatibility)
   */
  const getFlatMenuItems = (items: MenuItemType[]): MenuItem[] => {
    const flatItems: MenuItem[] = []
    
    items.forEach(item => {
      if (item.type === 'item') {
        flatItems.push(item as MenuItem)
      } else if (item.type === 'section') {
        const section = item as any
        section.items?.forEach((sectionItem: any) => {
          flatItems.push({
            id: sectionItem.id,
            type: 'item',
            order: item.order, // Use section's order
            name: sectionItem.name,
            path: sectionItem.path,
            icon: sectionItem.icon
          } as MenuItem)
        })
      }
    })
    
    return flatItems.sort((a, b) => a.order - b.order)
  }
  
  /**
   * Get cached menu items from session storage
   */
  const getCachedMenuItems = (): MenuItemType[] | null => {
    if (!process.client) return null
    
    try {
      const cachedData = sessionStorage.getItem(CACHE_KEY)
      const cachedTimestamp = sessionStorage.getItem(CACHE_TIMESTAMP_KEY)
      
      if (cachedData && cachedTimestamp) {
        const timestamp = parseInt(cachedTimestamp, 10)
        const now = Date.now()
        
        // Check if cache is still valid
        if (now - timestamp < CACHE_DURATION) {
          return JSON.parse(cachedData)
        }
      }
    } catch (err) {
      console.warn('Failed to get cached menu items:', err)
    }
    
    return null
  }
  
  /**
   * Cache menu items to session storage
   */
  const setCachedMenuItems = (items: MenuItemType[]): void => {
    if (!process.client) return
    
    try {
      sessionStorage.setItem(CACHE_KEY, JSON.stringify(items))
      sessionStorage.setItem(CACHE_TIMESTAMP_KEY, Date.now().toString())
    } catch (err) {
      console.warn('Failed to cache menu items:', err)
    }
  }
  
  /**
   * Clear cached menu items
   */
  const clearCachedMenuItems = (): void => {
    if (!process.client) return
    
    try {
      sessionStorage.removeItem(CACHE_KEY)
      sessionStorage.removeItem(CACHE_TIMESTAMP_KEY)
    } catch (err) {
      console.warn('Failed to clear cached menu items:', err)
    }
  }
  
  /**
   * Fetch menu items from the API
   */
  const fetchMenuItems = async (forceRefresh = false): Promise<void> => {
    // Don't fetch if not authenticated
    if (!isAuthenticated.value) {
      rawMenuItems.value = []
      error.value = null
      return
    }
    
    // Check cache first if not forcing refresh
    if (!forceRefresh) {
      const cachedItems = getCachedMenuItems()
      if (cachedItems) {
        rawMenuItems.value = cachedItems
        error.value = null
        return
      }
    }
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await request<MenuResponse>('menu', {
        method: 'GET',
        version: 'v1'
      })
      
      if (response.data) {
        // The API service wraps the response, so we need to access the actual menu data
        const menuData = response.data as MenuResponse
        if (menuData.data) {
          // Sort menu items by order
          const sortedItems = menuData.data.sort((a: MenuItemType, b: MenuItemType) => a.order - b.order)
          rawMenuItems.value = sortedItems
          
          // Cache the menu items
          setCachedMenuItems(sortedItems)
          error.value = null
        } else {
          throw new Error('No menu data received from server')
        }
      } else if (response.error) {
        throw response.error
      } else {
        throw new Error('No menu data received from server')
      }    } catch (err: any) {
      console.error('Failed to fetch menu items:', err)
      
      // Check if it's an authentication error
      if (err.message?.includes('Unauthenticated') || err.status === 401) {
        error.value = 'Authentication required to load menu'
        rawMenuItems.value = []
        return
      }
      
      error.value = err.message || 'Failed to load navigation menu'
      
      // Try to use cached items as fallback
      const cachedItems = getCachedMenuItems()
      if (cachedItems) {
        rawMenuItems.value = cachedItems
      } else {
        // Fallback to empty array
        rawMenuItems.value = []
      }
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Refresh menu items (force refresh from API)
   */
  const refreshMenuItems = async (): Promise<void> => {
    clearCachedMenuItems()
    await fetchMenuItems(true)
  }
  
  /**
   * Get menu item by path
   */
  const getMenuItemByPath = (path: string): MenuItem | undefined => {
    const flatItems = getFlatMenuItems(rawMenuItems.value)
    return flatItems.find(item => item.path === path)
  }
  
  /**
   * Computed property for sorted menu items (by order)
   */
  const menuItems = computed(() => {
    return [...rawMenuItems.value].sort((a, b) => a.order - b.order)
  })
  
  /**
   * Computed property for backward compatibility - flat menu items
   */
  const flatMenuItems = computed(() => {
    return getFlatMenuItems(rawMenuItems.value)
  })
    /**
   * Watch authentication state changes
   */
  watch(isAuthenticated, (newValue, oldValue) => {
    if (newValue && !oldValue) {
      // User just logged in - fetch menu items
      fetchMenuItems()
    } else if (!newValue && oldValue) {
      // User just logged out - clear menu items and cache
      rawMenuItems.value = []
      clearCachedMenuItems()
      error.value = null
    }
  }, { immediate: false })
  
  // Initialize menu items on composable creation - but only if we're authenticated
  // Add a small delay to ensure auth state is properly initialized
  if (process.client) {
    setTimeout(() => {
      if (isAuthenticated.value) {
        fetchMenuItems()
      }
    }, 100)
  }
  
  return {
    // State
    menuItems: readonly(menuItems),
    flatMenuItems: readonly(flatMenuItems),
    rawMenuItems: readonly(rawMenuItems),
    isLoading: readonly(isLoading),
    error: readonly(error),
    
    // Methods
    fetchMenuItems,
    refreshMenuItems,
    getMenuItemByPath,
    clearCachedMenuItems
  }
}
