<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useRouter } from '#imports'
import type { MenuItemType, MenuSection } from '~/types'
import { useAuth } from '~/composables/useAuth'

// Use auth composable to get user data and logout functionality
const { user, logout: baseLogout } = useAuth()
const router = useRouter()

// Define props to receive menu items from parent component
const props = defineProps<{
  menuItems: readonly MenuItemType[]
  isDarkMode: boolean
  isLoading?: boolean
  error?: string | null
}>()

// Define emits to send events to the parent (Navbar.vue)
const emit = defineEmits(['toggle-dark-mode'])

// Track expanded/collapsed state of sections
const expandedSections = reactive<Record<string, boolean>>({})

// Initialize all sections as expanded by default
const initializeSections = () => {
  props.menuItems.forEach(item => {
    if (item.type === 'section') {
      const sectionKey = `section-${item.id || item.order}`
      if (!(sectionKey in expandedSections)) {
        expandedSections[sectionKey] = true // Default to expanded
      }
    }
  })
}

// Watch for menu items changes and reinitialize sections
watch(() => props.menuItems, () => {
  initializeSections()
}, { immediate: true })

// Toggle section expanded state
const toggleSection = (section: MenuSection) => {
  const sectionKey = `section-${section.id || section.order}`
  expandedSections[sectionKey] = !expandedSections[sectionKey]
}

// Check if section is expanded
const isSectionExpanded = (section: MenuSection): boolean => {
  const sectionKey = `section-${section.id || section.order}`
  return expandedSections[sectionKey] ?? true // Default to expanded
}

// Handle menu item click - close offcanvas
const handleMenuItemClick = (event: MouseEvent) => {
  const offcanvas = (event.target as HTMLElement).closest('.offcanvas')
  if (offcanvas) {
    const bsOffcanvas = window.bootstrap?.Offcanvas?.getInstance(offcanvas)
    if (bsOffcanvas) {
      bsOffcanvas.hide()
    }
  }
}

// Handle dark mode toggle
const handleToggle = () => {
  emit('toggle-dark-mode')
}

// Handle logout and redirect
const logoutAndRedirect = () => {
  baseLogout()
  router.push('/')
  
  // Close offcanvas after logout
  const offcanvas = document.getElementById('mainMenuOffcanvas')
  if (offcanvas) {
    const bsOffcanvas = window.bootstrap?.Offcanvas?.getInstance(offcanvas)
    if (bsOffcanvas) {
      bsOffcanvas.hide()
    }
  }
}
</script>

<template>
  <!-- Offcanvas Menu Component -->
  <div 
    class="offcanvas offcanvas-start" 
    :class="props.isDarkMode ? 'text-bg-dark' : 'text-bg-light'" 
    tabindex="-1" 
    id="mainMenuOffcanvas" 
    aria-labelledby="mainMenuOffcanvasLabel"> 
    <div class="offcanvas-header">
      <div class="d-flex flex-column">
        <h5 class="offcanvas-title mb-0 fw-semibold fs-5" id="mainMenuOffcanvasLabel">
          <i class="bi bi-person-circle me-2"></i>
          {{ user?.name || 'Thanzil' }}
        </h5>
      </div>
      <button        
        type="button" 
        class="btn-close" 
        :class="{'btn-close-white': props.isDarkMode}" 
        data-bs-dismiss="offcanvas" 
        aria-label="Close"
      ></button>
    </div>    <div class="offcanvas-body d-flex flex-column">
      <!-- Loading state -->
      <div v-if="props.isLoading" class="d-flex justify-content-center py-3">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Loading menu...</span>
        </div>
      </div>
      
      <!-- Error state -->
      <div v-else-if="props.error" class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ props.error }}
      </div>
      
      <!-- Menu items - flex-grow-1 to push footer items to bottom -->
      <nav v-else class="nav flex-column flex-grow-1">
        <template v-for="item in props.menuItems" :key="item.id || `${item.type}-${item.order}`">
          <!-- Skip logout item as we'll render it separately -->
          <template v-if="!(item.type === 'item' && item.name === 'Logout')">
            <!-- Regular Menu Item -->
            <div v-if="item.type === 'item'" class="nav-item mb-1">
              <NuxtLink 
                class="nav-link d-flex align-items-center px-3 py-2 rounded text-decoration-none small transition-colors"
                :class="{
                  'active bg-outline-primary bg-opacity-10 fw-medium border-start border-primary border-2': $route.path === item.path,
                  'text-body-secondary': $route.path !== item.path
                }"
                :to="item.path"
                @click="handleMenuItemClick"
              >
                <i v-if="item.icon" :class="item.icon" class="me-2"></i>
                {{ item.name }}
              </NuxtLink>
            </div>
            
            <!-- Menu Section -->
            <div v-else-if="item.type === 'section'" class="nav-section mb-2">
              <!-- Section Header (Clickable) -->
              <button 
                class="w-100 d-flex align-items-center justify-content-between px-2 py-1 border-0 bg-transparent text-start rounded"
                :class="props.isDarkMode ? 'text-light' : 'text-dark'"
                @click="toggleSection(item as MenuSection)"
                type="button"
              >
                <h6 class="text-muted text-uppercase fw-bold mb-0 small">
                  {{ item.title }}
                </h6>
                <i 
                  class="bi fs-6 transition-transform"
                  :class="isSectionExpanded(item as MenuSection) ? 'bi-chevron-down' : 'bi-chevron-right'"
                ></i>
              </button>
              
              <!-- Section Items (Collapsible) with background -->
              <div 
                class="collapse"
                :class="{ 
                  'show': isSectionExpanded(item as MenuSection),
                  'bg-body-secondary bg-opacity-25 rounded p-2 my-1 border-start border-primary border-opacity-50 border-2': true
                }"
              >
                <div 
                  v-for="sectionItem in item.items" 
                  :key="sectionItem.id" 
                  class="nav-item mb-1"
                >
                  <NuxtLink 
                    class="nav-link d-flex align-items-center px-3 py-2 rounded text-decoration-none small transition-colors ms-2"
                    :class="{
                      'active bg-outline-primary bg-opacity-15 fw-medium border-start border-primary border-2': $route.path === sectionItem.path,
                      'text-body-secondary': $route.path !== sectionItem.path
                    }"
                    :to="sectionItem.path"
                    @click="handleMenuItemClick"
                  >
                    <i v-if="sectionItem.icon" :class="sectionItem.icon" class="me-2"></i>
                    {{ sectionItem.name }}
                  </NuxtLink>
                </div>
              </div>
            </div>
            
            <!-- Menu Divider -->
            <hr v-else-if="item.type === 'divider'" class="my-3 opacity-25" />
          </template>
        </template>
      </nav>
      
      <!-- Empty state -->
      <div v-if="!props.isLoading && !props.error && props.menuItems.length === 0" class="text-center py-3 text-muted flex-grow-1">
        <i class="bi bi-list-ul display-4 mb-2"></i>
        <p class="mb-0">No menu items available</p>
      </div>
      
      <!-- Footer section with dark mode toggle and logout -->
      <div class="mt-auto pt-3 border-top" :class="props.isDarkMode ? 'border-secondary' : 'border-light'">
        <!-- Dark/Light Mode Toggle -->
        <div class="nav-item mb-2">
          <button 
            class="nav-link d-flex align-items-center px-3 py-2 rounded text-decoration-none small transition-colors w-100 border-0 bg-transparent text-start"
            :class="props.isDarkMode ? 'text-light' : 'text-dark'"
            @click="handleToggle"
          >
            <i :class="props.isDarkMode ? 'bi bi-sun-fill me-2' : 'bi bi-moon-stars-fill me-2'"></i>
            {{ props.isDarkMode ? 'Light Mode' : 'Dark Mode' }}
          </button>
        </div>
        
        <!-- Logout Button -->
        <div class="nav-item">
          <button 
            class="nav-link d-flex align-items-center px-3 py-2 rounded text-decoration-none small transition-colors w-100 border-0 bg-transparent text-start text-danger"
            @click="logoutAndRedirect"
          >
            <i class="bi bi-box-arrow-right me-2"></i>
            Logout
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Only keep essential custom styles that Bootstrap doesn't provide */
.transition-colors {
  transition: all 0.2s ease-in-out;
}

.transition-transform {
  transition: transform 0.2s ease-in-out;
}

/* Hover effects for nav links */
.nav-link:hover:not(.active) {
  background-color: rgba(var(--bs-body-color-rgb), 0.05) !important;
}

/* Section header hover */
button.bg-transparent:hover {
  background-color: rgba(var(--bs-body-color-rgb), 0.03) !important;
}

/* Active state border adjustment for section items */
.nav-link.active.border-start {
  padding-left: calc(0.75rem - 2px);
}

/* Footer button hover effects */
.nav-link.w-100:hover {
  background-color: rgba(var(--bs-body-color-rgb), 0.05) !important;
}

.nav-link.text-danger:hover {
  background-color: rgba(var(--bs-danger-rgb), 0.1) !important;
}
</style>
