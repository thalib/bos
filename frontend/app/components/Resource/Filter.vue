<template>
  <div class="filter-container">
    <!-- Filter Dropdowns -->
    <div class="row g-2 mb-3">
      <div 
        class="col-md-3" 
        v-for="filter in availableFilters" 
        :key="filter.field"
      >
        <select 
          class="form-select"
          :class="{ 'is-invalid': hasError }"
          :aria-label="`Filter by ${filter.label}`"
          v-model="appliedFilters[filter.field]"
          :disabled="isLoading"
        >
          <option value="">All {{ filter.label }}</option>
          <option 
            v-for="option in filter.values" 
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>
      
      <div class="col-md-3 d-flex align-items-end">
        <button 
          v-if="hasActiveFilters"
          class="btn btn-outline-secondary w-100"
          :disabled="isLoading"
          @click="clearAllFilters"
        >
          <i class="bi bi-x-circle me-1"></i>
          Clear Filters
        </button>
      </div>
    </div>
    
    <!-- Applied Filter Badges -->
    <div v-if="hasActiveFilters" class="d-flex flex-wrap gap-2 mb-3">
      <span class="text-muted small">Active filters:</span>
      <span
        v-for="(value, field) in activeFilters"
        :key="field"
        class="badge bg-primary d-flex align-items-center gap-1"
      >
        {{ getFilterLabel(field) }}: {{ value }}
        <button 
          class="btn-close btn-close-white btn-sm"
          @click="removeFilter(field)"
          :aria-label="`Remove ${field} filter`"
        ></button>
      </span>
    </div>
    
    <!-- Status Display -->
    <div class="text-muted small">
      <span v-if="isLoading">
        <div class="spinner-border spinner-border-sm me-1" role="status"></div>
        Applying filters...
      </span>
      <span v-else-if="hasActiveFilters">
        {{ filteredCount }} items match current filters
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface FilterOption {
  value: string
  label: string
}

interface AvailableFilter {
  field: string
  label: string
  values: FilterOption[]
}

interface Props {
  resource: string
  initialFilters?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  initialFilters: () => ({})
})

const emit = defineEmits<{
  filtersApplied: [{ filters: Record<string, string>; hasActiveFilters: boolean }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()
const route = useRoute()
const router = useRouter()

// Reactive state
const availableFilters = ref<AvailableFilter[]>([])
const appliedFilters = ref<Record<string, string>>({})
const isLoading = ref(false)
const hasError = ref(false)
const filteredCount = ref(0)

// Computed properties
const hasActiveFilters = computed(() => 
  Object.values(appliedFilters.value).some(value => value !== '')
)

const activeFilters = computed(() => 
  Object.fromEntries(
    Object.entries(appliedFilters.value).filter(([_, value]) => value !== '')
  )
)

// Initialize filters from URL
onMounted(() => {
  initializeFilters()
  fetchAvailableFilters()
})

const initializeFilters = () => {
  const routeFilters = { ...route.query } as Record<string, string>
  delete routeFilters.page // Don't include pagination
  delete routeFilters.search // Don't include search
  delete routeFilters.sort // Don't include sorting
  delete routeFilters.dir // Don't include sort direction
  delete routeFilters.per_page // Don't include per page
  
  appliedFilters.value = { 
    ...props.initialFilters, 
    ...routeFilters 
  }
}

const fetchAvailableFilters = async () => {
  try {
    isLoading.value = true
    hasError.value = false
    
    const response = await apiService.fetch(props.resource, { page: 1, per_page: 1 })
    
    if (response?.filters?.available) {
      availableFilters.value = response.filters.available
    } else if (response?.data?.filters?.available) {
      availableFilters.value = response.data.filters.available
    }
  } catch (error) {
    handleFilterError(error)
  } finally {
    isLoading.value = false
  }
}

const applyFilters = async () => {
  try {
    isLoading.value = true
    
    const filterParams = { ...activeFilters.value }
    const response = await apiService.fetch(props.resource, filterParams)
    
    filteredCount.value = response?.pagination?.totalItems || response?.data?.pagination?.totalItems || 0
    updateUrl(filterParams)
    
    emit('filtersApplied', { 
      filters: filterParams, 
      hasActiveFilters: hasActiveFilters.value 
    })
    
  } catch (error) {
    handleFilterError(error)
  } finally {
    isLoading.value = false
  }
}

const removeFilter = (field: string) => {
  appliedFilters.value[field] = ''
  applyFilters()
}

const clearAllFilters = () => {
  appliedFilters.value = {}
  updateUrl({})
  emit('filtersApplied', { filters: {}, hasActiveFilters: false })
}

const updateUrl = (filters: Record<string, string>) => {
  const query = { ...route.query }
  
  // Remove existing filter parameters
  Object.keys(query).forEach(key => {
    if (!['page', 'search', 'sort', 'dir'].includes(key)) {
      delete query[key]
    }
  })
  
  // Add new filter parameters
  Object.entries(filters).forEach(([key, value]) => {
    if (value) {
      query[key] = value
    }
  })
  
  router.replace({ query })
}

const getFilterLabel = (field: string): string => {
  return availableFilters.value.find(f => f.field === field)?.label || field
}

// Watch for filter changes - but avoid infinite loops
watch(appliedFilters, () => {
  // Only apply filters if we have available filters loaded and there are active filters
  if (availableFilters.value.length > 0 && hasActiveFilters.value) {
    applyFilters()
  }
}, { deep: true })

// Error handling
const handleFilterError = (error: any) => {
  console.error('[Filter] API error:', error)
  hasError.value = true
  notifyService.error('Filter options temporarily unavailable.', 'Filter Error')
}
</script>