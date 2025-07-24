<template>
  <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <!-- Offcanvas Header -->
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="sidebarLabel" data-testid="user-name">
        <ClientOnly fallback="Guest">
          {{ currentUser?.name || 'Guest' }}
        </ClientOnly>
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-testid="sidebar-close"
        aria-label="Close"></button>
    </div>

    <!-- Offcanvas Body -->
    <div class="offcanvas-body">
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center" data-testid="loading-spinner">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="hasError" class="alert alert-warning" data-testid="error-alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Failed to load menu. Please try again or contact support if the issue persists.
      </div>

      <!-- Empty State -->
      <div v-else-if="menuItems.length === 0" class="text-center text-muted" data-testid="empty-state">
        <p>No menu items available. Please refresh the page or try again later.</p>
      </div>

      <!-- Menu Items -->
      <nav v-else class="nav flex-column">
        <template v-for="item in sortedMenuItems" :key="`${item.type}-${item.id || item.order}`">
          <!-- Regular Menu Item -->
          <template v-if="item.type === 'item'">
            <NuxtLink :to="item.path" class="nav-link" :data-testid="`menu-item-${item.id}`">
              <i v-if="item.icon" :class="[item.icon, 'me-2']"></i>
              {{ item.name }}
            </NuxtLink>
          </template>

          <!-- Menu Section (Collapsible) -->
          <template v-else-if="item.type === 'section'">
            <button class="nav-link btn btn-link text-start" type="button" data-bs-toggle="collapse"
              :data-bs-target="`#section-${item.title}`" :aria-expanded="false"
              :data-testid="`menu-section-${item.title}`">
              <i class="bi bi-chevron-right me-2"></i>
              {{ item.title }}
            </button>
            <div class="collapse" :id="`section-${item.title}`">
              <div class="ms-3">
                <NuxtLink v-for="subItem in item.items" :key="subItem.id" :to="subItem.path" class="nav-link"
                  :data-testid="`menu-item-${subItem.id}`">
                  <i v-if="subItem.icon" :class="[subItem.icon, 'me-2']"></i>
                  {{ subItem.name }}
                </NuxtLink>
              </div>
            </div>
          </template>

          <!-- Menu Divider -->
          <template v-else-if="item.type === 'divider'">
            <hr class="dropdown-divider" data-testid="menu-divider" />
          </template>
        </template>
      </nav>

      <!-- Bottom Section -->
      <div class="mt-auto">
        <hr />
        <p><a href="https://github.com/thalib/bos" target="_blank">BOS - Business OS</a></p>
      </div>
    </div>
  </div>

</template>

<script setup lang="ts">
// Types for menu items
interface MenuItem {
  type: 'item' | 'section' | 'divider'
  id?: number
  name?: string
  path?: string
  icon?: string
  order: number
  title?: string // for sections
  items?: MenuItem[] // for sections
}

// Services
const authService = useAuthService()
const apiService = useApiService()
const notifyService = useNotifyService()

// Reactive state
const menuItems = ref<MenuItem[]>([])
const isLoading = ref(true)
const hasError = ref(false)

// Computed properties
const currentUser = computed(() => authService.getCurrentUser())
const sortedMenuItems = computed(() =>
  [...menuItems.value].sort((a, b) => a.order - b.order)
)

// Methods
const getCachedMenuItems = () => {
  try {
    if (!process.client || typeof window === 'undefined') {
      return null
    }

    const cached = localStorage.getItem('menu_cache')
    if (!cached) {
      return null
    }

    const cacheData = JSON.parse(cached)
    const oneDay = 24 * 60 * 60 * 1000 // 1 day in milliseconds
    const isExpired = (Date.now() - cacheData.timestamp) > oneDay

    if (isExpired) {
      localStorage.removeItem('menu_cache')
      return null
    }

    return cacheData.menuItems
  } catch (error) {
    notifyService.warning('Load menu items from  cache ', 'Cache Warning')
    return null
  }
}

const fetchMenuItems = async () => {
  // First check for cached menu items
  const cachedItems = getCachedMenuItems()
  if (cachedItems && Array.isArray(cachedItems)) {
    menuItems.value = cachedItems
    isLoading.value = false
    return
  }

  try {
    isLoading.value = true
    hasError.value = false

    const response = await apiService.request('/api/v1/app/menu', {
      method: 'GET'
    })

    // Handle the API service wrapped response: response.data contains the backend response
    if (response.success && response.data) {
      // The actual menu items are in response.data.data (backend's data field)
      const menuData = response.data.data || response.data
      if (Array.isArray(menuData)) {
        menuItems.value = menuData

        // Cache the menu items with 1-day expiry
        const cacheData = {
          menuItems: menuData,
          timestamp: Date.now()
        }
        try {
          if (process.client && typeof window !== 'undefined') {
            localStorage.setItem('menu_cache', JSON.stringify(cacheData))
          }
        } catch (error) {
          notifyService.warning('Failed to save menu items to cache', 'Warning')
        }
      } else {
        throw new Error(response.message || 'Failed to load menu')
      }
    } else {
      throw new Error(response.message || 'Failed to load menu')
    }
  } catch (error) {
    hasError.value = true
    notifyService.error('Failed to load menu items', 'Menu Error')
  } finally {
    isLoading.value = false
  }
}

let observer: IntersectionObserver | null = null;

// Lifecycle
onMounted(() => {
  const sidebar = document.getElementById('sidebar');
  if (sidebar) {
    observer = new IntersectionObserver((entries) => {
      const entry = entries[0];
      if (entry?.isIntersecting && authService.isInitialized.value && authService.isAuthenticated.value) {
        fetchMenuItems();
      }
    });
    observer.observe(sidebar);
  }
});

onUnmounted(() => {
  if (observer) {
    observer.disconnect();
  }
});
</script>