<template>
  <div class="search-container">
    <div class="input-group mb-3">
      <input
        v-model="searchQuery"
        type="search"
        class="form-control"
        :class="{ 'is-invalid': hasError }"
        placeholder="Search resources..."
        aria-label="Search"
        aria-describedby="search-help"
        :disabled="isLoading"
        @keyup.enter="performSearch"
        @input="handleSearchInput"
      >
      <button
        v-if="searchQuery && !isLoading"
        class="btn btn-outline-secondary"
        type="button"
        aria-label="Clear search"
        @click="clearSearch"
      >
        <i class="bi bi-x-lg"></i>
      </button>
      <button
        v-if="isLoading"
        class="btn btn-outline-secondary"
        type="button"
        disabled
      >
        <div class="spinner-border spinner-border-sm" role="status">
          <span class="visually-hidden">Searching...</span>
        </div>
      </button>
    </div>
    
    <div class="d-flex justify-content-between align-items-center text-muted small">
      <span id="search-help">Search results update as you type</span>
      <span v-if="searchQuery && !isLoading" class="badge bg-light text-dark">
        {{ resultCount }} results found
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'
import { debounce } from 'lodash-es'

// Props with minimal interface
interface Props {
  resource: string
  initialSearch?: string
}

const props = withDefaults(defineProps<Props>(), {
  initialSearch: ''
})

// Events
const emit = defineEmits<{
  'search-applied': [{ search: string; hasResults: boolean }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()
const route = useRoute()
const router = useRouter()

// Reactive state
const searchQuery = ref('')
const isLoading = ref(false)
const hasError = ref(false)
const resultCount = ref(0)

// Initialize search value immediately
const initializeSearchValue = () => {
  // Priority: route query > initialSearch prop
  const routeSearch = route.query.search as string
  searchQuery.value = routeSearch || props.initialSearch || ''
}

// Initialize component
onMounted(() => {
  initializeSearchValue()
})

// Also initialize immediately in case onMounted hasn't run yet (for tests)
initializeSearchValue()

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
  if (query.length > 0 && query.length < 2) {
    notifyService.warning('Search term must be at least 2 characters')
    return
  }
  
  if (!query) {
    updateUrl('')
    emit('search-applied', { search: '', hasResults: false })
    resultCount.value = 0
    return
  }
  
  try {
    isLoading.value = true
    hasError.value = false
    
    const response = await apiService.fetch(props.resource, { search: query })
    
    // Handle both direct array and paginated response
    let totalItems = 0
    if (response.pagination?.totalItems !== undefined) {
      totalItems = response.pagination.totalItems
    } else if (response.data && Array.isArray(response.data)) {
      totalItems = response.data.length
    }
    
    resultCount.value = totalItems
    
    updateUrl(query)
    emit('search-applied', { search: query, hasResults: totalItems > 0 })
    
  } catch (error) {
    hasError.value = true
    handleSearchError(error)
  } finally {
    isLoading.value = false
  }
}, 300)

// URL synchronization
const updateUrl = (search: string) => {
  const query = { ...route.query }
  if (search) {
    query.search = search
  } else {
    delete query.search
  }
  
  router.replace({ query })
}

// Clear search
const clearSearch = () => {
  searchQuery.value = ''
  updateUrl('')
  emit('search-applied', { search: '', hasResults: false })
  resultCount.value = 0
}

// Handle search input
const handleSearchInput = () => {
  debouncedSearch(searchQuery.value)
}

// Handle enter key press
const performSearch = () => {
  // Cancel any pending debounced calls and search immediately
  debouncedSearch.cancel()
  debouncedSearch(searchQuery.value)
}

// Error handling
const handleSearchError = (error: any) => {
  console.error('[Search] API error:', error)
  notifyService.error('Search temporarily unavailable. Please try again.', 'Search Error')
}
</script>

<style scoped>
/* No custom styles needed - using Bootstrap 5.3 classes */
</style>