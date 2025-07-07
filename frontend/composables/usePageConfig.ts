/**
 * Page Configuration composable for dynamic pages
 * Manages page configurations and route validation based on menu API data
 */
import { computed } from 'vue'
import type { PageConfig, MenuItem, MenuSection } from '~/types'
import { useNavigation } from '~/composables/useNavigation'

/**
 * Transform menu item to page configuration
 */
const transformMenuItemToPageConfig = (item: MenuItem): PageConfig => {
  // Extract slug from path (remove leading slash)
  const slug = item.path.replace(/^\//, '') || 'home'
  
  // Generate component name from slug (PascalCase + Content)
  const componentName = slug
    .split('-')
    .filter(word => word && word.length > 0) // Filter out empty strings
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join('') + 'Content'
    return {
    slug,
    title: item.name,
    description: `${item.name} page`,
    component: componentName,
    icon: item.icon,
    middleware: ['auth'] // All dynamic pages require authentication
  }
}

/**
 * Get page configurations from menu API data
 */
const getPageConfigsFromMenu = () => {
  const { flatMenuItems } = useNavigation()
  
  return computed(() => {
    const configs: Record<string, PageConfig> = {}
    
    flatMenuItems.value.forEach(item => {
      // Skip certain paths that shouldn't be dynamic pages
      if (item.path === '/' || item.path === '/logout' || item.path === '/help') {
        return
      }
      
      const config = transformMenuItemToPageConfig(item)
      configs[config.slug] = config
    })
    
    return configs
  })
}

/**
 * Page configuration composable
 */
export const usePageConfig = () => {
  // Get dynamic page configurations from menu data
  const pageConfigs = getPageConfigsFromMenu()
  
  /**
   * Get page configuration by slug
   */
  const getPageConfig = (slug: string): PageConfig | null => {
    if (!slug || typeof slug !== 'string') {
      return null
    }
    return pageConfigs.value[slug] || null
  }

  /**
   * Check if page slug is valid
   */
  const isValidPage = (slug: string): boolean => {
    if (!slug || typeof slug !== 'string') {
      return false
    }
    return slug in pageConfigs.value
  }

  /**
   * Get all available page configurations
   */
  const getAllPageConfigs = computed(() => {
    return Object.values(pageConfigs.value)
  })

  /**
   * Get all available page slugs
   */
  const getAvailableSlugs = computed(() => {
    return Object.keys(pageConfigs.value)
  })

  return {
    getPageConfig,
    isValidPage,
    getAllPageConfigs,
    getAvailableSlugs
  }
}
