<template>
  <div
    class="offcanvas offcanvas-start"
    tabindex="-1"
    id="sidebar"
    aria-labelledby="sidebarLabel"
  >
    <!-- Offcanvas Header -->
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="sidebarLabel" data-testid="user-name">
        {{ currentUser?.name || 'Guest' }}
      </h5>
      <button
        type="button"
        class="btn-close"
        data-bs-dismiss="offcanvas"
        data-testid="sidebar-close"
        aria-label="Close"
      ></button>
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
        Failed to load menu. Please try again.
      </div>

      <!-- Empty State -->
      <div v-else-if="menuItems.length === 0" class="text-center text-muted" data-testid="empty-state">
        <p>No menu items available</p>
      </div>

      <!-- Menu Items -->
      <nav v-else class="nav flex-column">
        <template v-for="item in sortedMenuItems" :key="`${item.type}-${item.id || item.order}`">
          <!-- Regular Menu Item -->
          <template v-if="item.type === 'item'">
            <NuxtLink
              :to="item.path"
              class="nav-link"
              :data-testid="`menu-item-${item.id}`"
            >
              <i v-if="item.icon" :class="[item.icon, 'me-2']"></i>
              {{ item.name }}
            </NuxtLink>
          </template>

          <!-- Menu Section (Collapsible) -->
          <template v-else-if="item.type === 'section'">
            <button
              class="nav-link btn btn-link text-start"
              type="button"
              data-bs-toggle="collapse"
              :data-bs-target="`#section-${item.title}`"
              :aria-expanded="false"
              :data-testid="`menu-section-${item.title}`"
            >
              <i class="bi bi-chevron-right me-2"></i>
              {{ item.title }}
            </button>
            <div class="collapse" :id="`section-${item.title}`">
              <div class="ms-3">
                <NuxtLink
                  v-for="subItem in item.items"
                  :key="subItem.id"
                  :to="subItem.path"
                  class="nav-link"
                  :data-testid="`menu-item-${subItem.id}`"
                >
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
        
        <!-- Dark/Light Mode Toggle -->
        <button
          type="button"
          class="btn btn-outline-secondary w-100 mb-2"
          data-testid="mode-toggle"
          @click="toggleMode"
        >
          <i class="bi bi-moon me-2"></i>
          Toggle Theme
        </button>

        <!-- Logout Button -->
        <button
          type="button"
          class="btn btn-outline-danger w-100"
          data-testid="logout-btn"
          @click="handleLogout"
        >
          <i class="bi bi-box-arrow-right me-2"></i>
          Logout
        </button>
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

// Emits
const emit = defineEmits<{
  logout: []
}>()

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
const fetchMenuItems = async () => {
  try {
    isLoading.value = true
    hasError.value = false

    const response = await apiService.request<MenuItem[]>('/api/v1/app/menu', {
      method: 'GET'
    })

    console.log('Menu items response:', response.data) // Log the response data

    if (response.success && Array.isArray(response.data)) {
      menuItems.value = response.data
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

const toggleMode = () => {
  // Toggle dark/light mode logic would go here
  // For now, just a placeholder
  notifyService.info('Theme toggle not implemented yet', 'Theme')
}

const handleLogout = async () => {
    emit('logout')
}

// Lifecycle
onMounted(() => {
  fetchMenuItems()
})
</script>