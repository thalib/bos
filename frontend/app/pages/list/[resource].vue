<template>
  <div class="resource-page">
    <!-- Global Loading State -->
    <div v-if="isInitializing" class="initialization-loading">
      <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="text-center">
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="text-muted">Initializing {{ resourceName }}...</p>
        </div>
      </div>
    </div>
    
    <!-- Main Content -->
    <div v-else class="resource-content">
      <!-- Page Header -->
      <Header
        :title="resourceTitle"
        :resource="resourceName"
        :show-create="canCreate"
        :show-export="canExport"
        :show-import="canImport"
        @action-triggered="handleHeaderAction"
      >
        <template #search>
          <Search
            :resource="resourceName"
            :initial-search="$route.query.search"
            @search-applied="handleSearchUpdate"
          />
        </template>
        
        <template #filters>
          <Filter
            :resource="resourceName"
            :initial-filters="getInitialFilters()"
            @filters-applied="handleFiltersUpdate"
          />
        </template>
      </Header>
      
      <!-- Main Content Area -->
      <div class="main-content">
        <!-- Simple List View for now -->
        <div class="row">
          <div class="col-12">
            <List
              :resource="resourceName"
              :filters="appliedFilters"
              :search="appliedSearch"
              :page="currentPage"
              @item-selected="handleItemSelected"
              @sort-changed="handleSortChanged"
            />
          </div>
        </div>
      </div>
      
      <!-- Pagination -->
      <div class="pagination-container">
        <Pagination
          :resource="resourceName"
          :initial-page="$route.query.page"
          :initial-per-page="$route.query.per_page"
          @page-changed="handlePageChanged"
        />
      </div>
    </div>
    
    <!-- Error Boundary -->
    <div v-if="hasGlobalError" class="error-boundary">
      <div class="alert alert-danger text-center">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Failed to load {{ resourceName }}. 
        <button class="btn btn-link p-0" @click="retryInitialization">
          Try again
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

// Route and services
const route = useRoute()
const router = useRouter()
const apiService = useApiService()
const notifyService = useNotifyService()

// Reactive state
const isInitializing = ref(true)
const hasGlobalError = ref(false)
const menuConfiguration = ref<any>(null)
const resourcePermissions = ref<any>({})
const appliedFilters = ref<Record<string, any>>({})
const appliedSearch = ref('')
const currentPage = ref(1)

// Computed properties
const resourceName = computed(() => route.params.resource as string)

const resourceTitle = computed(() => 
  resourceName.value.charAt(0).toUpperCase() + resourceName.value.slice(1)
)

const componentMode = computed(() => {
  return menuConfiguration.value?.mode === 'doc' ? 'document' : 'form'
})

const canCreate = computed(() => 
  resourcePermissions.value.create !== false
)

const canExport = computed(() => 
  resourcePermissions.value.export !== false
)

const canImport = computed(() => 
  resourcePermissions.value.import !== false
)

// Initialize page
onMounted(() => {
  initializePage()
})

const initializePage = async () => {
  try {
    isInitializing.value = true
    hasGlobalError.value = false
    
    // Fetch menu configuration to determine mode
    try {
      const menuResponse = await apiService.get('menu/configuration', resourceName.value)
      menuConfiguration.value = menuResponse.data
    } catch (error) {
      // Fallback to default configuration
      menuConfiguration.value = { mode: 'form' }
    }
    
    // Fetch resource permissions
    try {
      const permissionsResponse = await apiService.get('permissions', resourceName.value)
      resourcePermissions.value = permissionsResponse.data || {}
    } catch (error) {
      // Fallback to default permissions
      resourcePermissions.value = { create: true, export: true, import: true }
    }
    
    // Initialize state from URL
    initializeFromUrl()
    
    // Page is ready
    isInitializing.value = false
    
  } catch (error) {
    handleGlobalError('Failed to initialize page', error)
  }
}

const initializeFromUrl = () => {
  appliedSearch.value = route.query.search as string || ''
  currentPage.value = parseInt(route.query.page as string) || 1
  
  // Extract filters from URL
  const filters: Record<string, any> = {}
  Object.entries(route.query).forEach(([key, value]) => {
    if (!['page', 'per_page', 'search', 'sort', 'dir'].includes(key)) {
      filters[key] = value
    }
  })
  appliedFilters.value = filters
}

// Event handlers
const handleHeaderAction = (payload: { action: string; data?: any }) => {
  switch (payload.action) {
    case 'create':
      navigateToCreate()
      break
    case 'export':
      notifyService.info('Export initiated')
      break
    case 'import':
      notifyService.info('Import initiated')
      break
    case 'refresh':
      refreshPageData()
      break
  }
}

const handleSearchUpdate = (payload: { search: string; hasResults: boolean }) => {
  appliedSearch.value = payload.search
  currentPage.value = 1 // Reset to first page on search
  
  if (payload.search && !payload.hasResults) {
    notifyService.info(`No results found for "${payload.search}"`)
  }
}

const handleFiltersUpdate = (payload: { filters: object; hasActiveFilters: boolean }) => {
  appliedFilters.value = payload.filters
  currentPage.value = 1 // Reset to first page on filter change
  
  if (payload.hasActiveFilters) {
    notifyService.success('Filters applied')
  }
}

const handleItemSelected = (payload: { item: any; index: number }) => {
  // For now, just show a notification
  notifyService.info(`Selected item: ${payload.item.name || payload.item.id}`)
}

const handleSortChanged = (payload: { column: string; direction: 'asc' | 'desc' }) => {
  notifyService.info(`Sorted by ${payload.column} ${payload.direction}`)
}

const handlePageChanged = (payload: { page: number; perPage: number; totalItems: number }) => {
  currentPage.value = payload.page
}

// Navigation helpers
const navigateToCreate = () => {
  router.push({
    path: `/list/${resourceName.value}/create`
  })
}

// Utility functions
const getInitialFilters = () => {
  const filters: Record<string, any> = {}
  
  // Extract filter parameters from URL
  Object.entries(route.query).forEach(([key, value]) => {
    if (!['page', 'per_page', 'search', 'sort', 'dir'].includes(key)) {
      filters[key] = value
    }
  })
  
  return filters
}

const refreshPageData = () => {
  // Child components will handle their own refresh
  notifyService.info('Refreshing data...')
}

const retryInitialization = () => {
  initializePage()
}

// Error handling
const handleGlobalError = (message: string, error: any) => {
  console.error('[ResourcePage]', message, error)
  hasGlobalError.value = true
  isInitializing.value = false
  notifyService.error(message)
}

// Watch for route changes
watch(() => route.params.resource, (newResource) => {
  if (newResource !== resourceName.value) {
    initializePage()
  }
})

// Meta information for the page
useHead({
  title: computed(() => `${resourceTitle.value} - BOS`),
  meta: [
    {
      name: 'description',
      content: computed(() => `Manage ${resourceTitle.value} in BOS application`)
    }
  ]
})
</script>

<style scoped>
.resource-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  overflow: hidden;
}

.pagination-container {
  border-top: 1px solid var(--bs-border-color);
  background-color: var(--bs-light);
  padding: 1rem;
}

.initialization-loading {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.9);
  z-index: 9999;
}

.error-boundary {
  position: sticky;
  bottom: 0;
  z-index: 1000;
}
</style>