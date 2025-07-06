<template>
  <div class="dropdown" ref="dropdownRef">
    <button
      id="filterDropdown"
      class="btn btn-outline-secondary dropdown-toggle"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      aria-haspopup="true"
      :disabled="loading || isLoadingFilters"
      :aria-label="`Filter options${currentFilter ? ': ' + currentFilter : ''}`"
    >
      <i class="bi bi-funnel me-2" aria-hidden="true"></i>
      <span v-if="isLoadingFilters">
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Loading...
      </span>
      <span v-else-if="hasError">
        Filter (Error)
      </span>
      <span v-else-if="currentFilter && currentFilter !== 'all'">
        Filter: {{ formatFilterLabel(currentFilter) }}
      </span>
      <span v-else>
        Filter
      </span>
    </button>

    <ul
      class="dropdown-menu"
      aria-labelledby="filterDropdown"
      role="menu"
    >
      <!-- Loading State -->
      <li v-if="isLoadingFilters" class="dropdown-item-text text-center py-3">
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Loading filters...
      </li>

      <!-- Error State -->
      <li v-else-if="hasError" class="dropdown-item-text text-center py-3 text-muted">
        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
        Failed to load filters
      </li>

      <!-- Filter Options -->
      <template v-else>
        <template v-for="(filterConfig, filterKey) in typedFilters" :key="filterKey">
          <li class="dropdown-header text-uppercase fw-semibold small" v-if="Object.keys(typedFilters).length > 1">
            {{ filterConfig.label }}
          </li>
          <li v-for="(value, index) in filterConfig.values" :key="`${filterKey}-${index}`">
            <button
              type="button"
              class="dropdown-item d-flex align-items-center small"
              role="menuitem"
              :class="{
                'active': isCurrentFilter(filterKey, value),
                'disabled': loading
              }"
              :aria-pressed="isCurrentFilter(filterKey, value)"
              @click="handleFilterChange(filterKey, value)"
              :disabled="loading"
            >
              <i
                class="bi me-2"
                :class="{
                  'bi-check-lg': isCurrentFilter(filterKey, value),
                  'bi-circle': !isCurrentFilter(filterKey, value)
                }"
                aria-hidden="true"
              ></i>
              {{ formatFilterLabel(value) }}
            </button>
          </li>
          <li v-if="Object.keys(typedFilters).length > 1" class="dropdown-divider"></li>
        </template>

        <!-- Clear Filter Option (if not 'all') -->
        <li v-if="currentFilter && currentFilter !== 'all'">
          <hr class="dropdown-divider">
          <button
            type="button"
            class="dropdown-item d-flex align-items-center text-muted small"
            role="menuitem"
            @click="handleFilterClear"
            :disabled="loading"
          >
            <i class="bi bi-x-lg me-2" aria-hidden="true"></i>
            Clear Filter
          </button>
        </li>
      </template>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useApiCrud } from '~/services/api'
import type { ApiResponse, FiltersResponse, FilterConfig, FilterChangeEvent } from '~/types'

// Component Props
interface Props {
  /** Current filter value */
  modelValue?: string
  /** Resource name to fetch filters for */
  resource: string
  /** Loading state from parent */
  loading?: boolean
}

// Component Emits
interface Emits {
  /** Emitted when filter selection changes */
  (event: 'filter-change', payload: FilterChangeEvent): void
  /** Emitted when model value changes */
  (event: 'update:modelValue', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: 'all',
  loading: false
})

const emit = defineEmits<Emits>()

// Reactive refs
const dropdownRef = ref<HTMLElement>()
const availableFilters = ref<FiltersResponse>({
  active: {
    label: 'Status',
    values: ['all', 'active', 'inactive'],
    parameter: 'filter'
  }
})
const isLoadingFilters = ref(false)
const hasError = ref(false)
const currentFilter = ref(props.modelValue)

// API service
const { apiGetFilters } = useApiCrud()

// Computed properties
const formatFilterLabel = (value: string | number): string => {
  const stringValue = String(value)
  if (stringValue === 'all') return 'All'
  if (stringValue === 'active') return 'Active'
  if (stringValue === 'inactive') return 'Inactive'
  
  // Format camelCase/snake_case to Title Case
  return stringValue
    .replace(/([A-Z])/g, ' $1')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
    .trim()
}

const isCurrentFilter = (filterKey: string, value: string | number): boolean => {
  return currentFilter.value === String(value)
}

// Computed property to ensure proper typing for template
const typedFilters = computed(() => {
  const result: Record<string, { label: string; values: string[] }> = {}
  Object.entries(availableFilters.value).forEach(([key, config]) => {
    // Ensure config and config.values exist before processing
    if (config && config.values && Array.isArray(config.values)) {
      result[key] = {
        label: config.label || formatFilterLabel(key),
        values: config.values.map(v => String(v))
      }
    }
  })
  return result
})

// Methods
const fetchFilters = async (): Promise<void> => {
  if (!props.resource) return

  isLoadingFilters.value = true
  hasError.value = false

  try {
    const response: ApiResponse<FiltersResponse> = await apiGetFilters(props.resource)
    
    if (response.error) {
      console.warn('Failed to fetch filters, using fallback:', response.error)
      // Use fallback filters for legacy support
      availableFilters.value = {
        active: {
          label: 'Status',
          values: ['all', 'active', 'inactive'],
          parameter: 'filter'
        }
      }
    } else if (response.data) {
      // Handle nested response structure - check if data contains success/data wrapper
      let filterData: any = response.data
      
      // If the response has success/data structure, extract the inner data
      if (typeof response.data === 'object' && 'success' in response.data && 'data' in response.data) {
        filterData = (response.data as any).data
      }
      
      if (filterData && typeof filterData === 'object' && Object.keys(filterData).length > 0) {
        // Validate the response data structure
        const validatedFilters: FiltersResponse = {}
        Object.entries(filterData).forEach(([key, config]) => {
          const typedConfig = config as any
          if (config && typedConfig.values && Array.isArray(typedConfig.values) && typedConfig.values.length > 0) {
            validatedFilters[key] = {
              label: typedConfig.label || formatFilterLabel(key),
              values: typedConfig.values,
              parameter: typedConfig.parameter
            }
          }
        })
        
        // If we have valid filters, use them, otherwise fallback
        if (Object.keys(validatedFilters).length > 0) {
          availableFilters.value = validatedFilters
        } else {
          console.warn('No valid filters found in API response, using fallback')
          availableFilters.value = {
            active: {
              label: 'Status',
              values: ['all', 'active', 'inactive'],
              parameter: 'filter'
            }
          }
        }
      } else {
        // Fallback if no valid filter data found
        availableFilters.value = {
          active: {
            label: 'Status',
            values: ['all', 'active', 'inactive'],
            parameter: 'filter'
          }
        }
      }
    } else {
      // Fallback if no data
      availableFilters.value = {
        active: {
          label: 'Status',
          values: ['all', 'active', 'inactive'],
          parameter: 'filter'
        }
      }
    }
  } catch (error) {
    console.error('Error fetching filters:', error)
    hasError.value = true
    
    // Use fallback filters on error
    availableFilters.value = {
      active: {
        label: 'Status',
        values: ['all', 'active', 'inactive'],
        parameter: 'filter'
      }
    }
  } finally {
    isLoadingFilters.value = false
  }
}

const handleFilterChange = (filterKey: string, value: string): void => {
  if (props.loading) return

  currentFilter.value = value
  emit('update:modelValue', value)
  
  // Find the filter configuration to get the label
  const filterConfig = availableFilters.value[filterKey]
  const filterLabel = filterConfig?.label || formatFilterLabel(filterKey)
  
  // Emit filter change event with field, value, and label
  emit('filter-change', {
    field: filterKey,
    value: value,
    label: filterLabel
  })

  // Close dropdown
  if (dropdownRef.value) {
    const dropdown = dropdownRef.value.querySelector('.dropdown-toggle') as HTMLElement
    if (dropdown && dropdown.getAttribute('aria-expanded') === 'true') {
      dropdown.click()
    }
  }
}

const handleFilterClear = (): void => {
  handleFilterChange('active', 'all')
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  currentFilter.value = newValue || 'all'
})

watch(() => props.resource, (newResource, oldResource) => {
  if (newResource) {
    fetchFilters()
  }
}, { immediate: false })

// Lifecycle
onMounted(async () => {
  await nextTick()
  if (props.resource) {
    await fetchFilters()
  }
})

onUnmounted(() => {
  // Cleanup if needed
})

// Initialize on mount
if (props.resource) {
  fetchFilters()
}
</script>

<style scoped>
.dropdown-toggle:disabled {
  cursor: not-allowed;
}

.dropdown-item.active {
  background-color: var(--bs-primary);
  color: var(--bs-white);
}

.dropdown-item.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner-border-sm {
  width: 0.875rem;
  height: 0.875rem;
}

/* Smooth transitions for filter changes */
.dropdown-item {
  transition: all 0.2s ease-in-out;
}

.dropdown-item:hover:not(.disabled) {
  background-color: var(--bs-light);
}

.dropdown-item.active:hover {
  background-color: var(--bs-primary);
}
</style>
