<template>
  <div class="pagination-container">
    <!-- Per-page selector and info -->
    <div class="row align-items-center mb-3">
      <div class="col-md-6">
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small">Show</span>
          <select 
            class="form-select form-select-sm"
            style="width: auto;"
            v-model="itemsPerPage"
            :disabled="isLoading"
            @change="changePerPage"
          >
            <option value="15">15</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="text-muted small">items per page</span>
        </div>
      </div>
      
      <div class="col-md-6 text-md-end">
        <span class="text-muted small">
          Showing {{ startItem }}-{{ endItem }} of {{ totalItems }} items
        </span>
      </div>
    </div>
    
    <!-- Pagination controls -->
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center mb-0">
        <!-- First page -->
        <li class="page-item" :class="{ disabled: isFirstPage || isLoading }">
          <button 
            class="page-link"
            @click="goToPage(1)"
            :disabled="isFirstPage || isLoading"
            aria-label="First page"
          >
            <i class="bi bi-chevron-double-left"></i>
          </button>
        </li>
        
        <!-- Previous page -->
        <li class="page-item" :class="{ disabled: isFirstPage || isLoading }">
          <button 
            class="page-link"
            @click="goToPage(currentPage - 1)"
            :disabled="isFirstPage || isLoading"
            aria-label="Previous page"
          >
            <i class="bi bi-chevron-left"></i>
          </button>
        </li>
        
        <!-- Page numbers -->
        <li 
          v-for="page in visiblePages" 
          :key="page"
          class="page-item"
          :class="{ active: page === currentPage, disabled: isLoading }"
        >
          <button 
            class="page-link"
            @click="goToPage(page)"
            :disabled="isLoading"
            :aria-label="`Page ${page}`"
            :aria-current="page === currentPage ? 'page' : undefined"
          >
            {{ page }}
          </button>
        </li>
        
        <!-- Next page -->
        <li class="page-item" :class="{ disabled: isLastPage || isLoading }">
          <button 
            class="page-link"
            @click="goToPage(currentPage + 1)"
            :disabled="isLastPage || isLoading"
            aria-label="Next page"
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </li>
        
        <!-- Last page -->
        <li class="page-item" :class="{ disabled: isLastPage || isLoading }">
          <button 
            class="page-link"
            @click="goToPage(totalPages)"
            :disabled="isLastPage || isLoading"
            aria-label="Last page"
          >
            <i class="bi bi-chevron-double-right"></i>
          </button>
        </li>
      </ul>
    </nav>
    
    <!-- Loading indicator -->
    <div v-if="isLoading" class="text-center mt-2">
      <div class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="visually-hidden">Loading page...</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface Props {
  resource: string
  initialPage?: number
  initialPerPage?: number
}

const props = withDefaults(defineProps<Props>(), {
  initialPage: 1,
  initialPerPage: 15
})

const emit = defineEmits<{
  pageChanged: [{ page: number; perPage: number; totalItems: number }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()
const route = useRoute()
const router = useRouter()

// Reactive state
const currentPage = ref(props.initialPage)
const itemsPerPage = ref(props.initialPerPage)
const totalItems = ref(0)
const isLoading = ref(false)
const hasError = ref(false)
const previousPage = ref(1)

// Computed properties
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value))
const isFirstPage = computed(() => currentPage.value === 1)
const isLastPage = computed(() => currentPage.value === totalPages.value)
const startItem = computed(() => (currentPage.value - 1) * itemsPerPage.value + 1)
const endItem = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalItems.value))

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, currentPage.value + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Initialize from URL
onMounted(() => {
  initializeFromUrl()
  fetchPageData()
})

const initializeFromUrl = () => {
  const page = parseInt(route.query.page as string) || props.initialPage
  const perPage = parseInt(route.query.per_page as string) || props.initialPerPage
  
  currentPage.value = Math.max(1, page)
  itemsPerPage.value = [15, 50, 100].includes(perPage) ? perPage : 15
}

const fetchPageData = async () => {
  try {
    isLoading.value = true
    hasError.value = false
    previousPage.value = currentPage.value
    
    const params = {
      page: currentPage.value,
      per_page: itemsPerPage.value,
      ...getAdditionalParams()
    }
    
    const response = await apiService.fetch(props.resource, params)
    
    if (response?.pagination) {
      totalItems.value = response.pagination.totalItems
      
      // Validate current page is within bounds
      if (currentPage.value > totalPages.value && totalPages.value > 0) {
        currentPage.value = totalPages.value
        return fetchPageData() // Retry with corrected page
      }
    } else if (response?.data?.pagination) {
      totalItems.value = response.data.pagination.totalItems
    }
    
    updateUrl()
    emit('pageChanged', {
      page: currentPage.value,
      perPage: itemsPerPage.value,
      totalItems: totalItems.value
    })
    
  } catch (error) {
    handlePaginationError(error)
  } finally {
    isLoading.value = false
  }
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
    currentPage.value = page
    fetchPageData()
  }
}

const changePerPage = () => {
  currentPage.value = 1 // Reset to first page when changing per-page
  fetchPageData()
}

const updateUrl = () => {
  const query = { ...route.query }
  
  query.page = currentPage.value.toString()
  query.per_page = itemsPerPage.value.toString()
  
  router.replace({ query })
}

const getAdditionalParams = () => {
  const params: Record<string, any> = {}
  
  // Include other query parameters (search, filters, etc.)
  Object.entries(route.query).forEach(([key, value]) => {
    if (!['page', 'per_page'].includes(key) && value) {
      params[key] = value
    }
  })
  
  return params
}

// Error handling
const handlePaginationError = (error: any) => {
  console.error('[Pagination] API error:', error)
  hasError.value = true
  
  notifyService.error('Page navigation failed. Please try again.', 'Pagination Error')
  
  // Revert to previous page
  if (previousPage.value !== currentPage.value) {
    currentPage.value = previousPage.value
  }
}

// Watch for external page changes
watch(() => route.query.page, (newPage) => {
  const page = parseInt(newPage as string) || 1
  if (page !== currentPage.value) {
    currentPage.value = page
    fetchPageData()
  }
})
</script>