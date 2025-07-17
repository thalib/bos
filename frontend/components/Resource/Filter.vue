<template>
  <div class="dropdown" ref="dropdownRef">
    <button
      id="filterDropdown"
      class="btn dropdown-toggle"
      :class="{
        'btn-outline-secondary': !hasActiveFilters,
        'btn-primary': hasActiveFilters
      }"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      aria-haspopup="true"
      :disabled="loading || disabled"
      :aria-label="`Filter options${hasActiveFilters ? ': ' + activeFiltersDescription : ''}`"
    >
      <i class="bi bi-funnel me-2" aria-hidden="true"></i>
      <span v-if="loading">
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Loading...
      </span>
      <span v-else-if="hasActiveFilters">
        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
        {{ activeFiltersDescription }}
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
      <li v-if="loading" class="dropdown-item-text text-center py-3">
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Loading filters...
      </li>

      <!-- Error State -->
      <li v-else-if="!availableFilters || availableFilters.length === 0" class="dropdown-item-text text-center py-3 text-muted">
        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
        No filters available
      </li>

      <!-- Filter Options -->
      <template v-else>
        <template v-for="(filterConfig, index) in availableFilters" :key="`${filterConfig.field}-${index}`">
          <li class="dropdown-header text-uppercase fw-semibold small" v-if="availableFilters.length > 1">
            {{ filterConfig.label }}
          </li>
          <li v-for="(value, valueIndex) in filterConfig.values" :key="`${filterConfig.field}-${valueIndex}`">
            <button
              type="button"
              class="dropdown-item d-flex align-items-center small"
              role="menuitem"
              :class="{
                'active': isCurrentFilter(filterConfig.field, value),
                'disabled': loading
              }"
              :aria-pressed="isCurrentFilter(filterConfig.field, value)"
              @click="handleFilterChange(filterConfig.field, value)"
              :disabled="loading"
            >
              <i
                class="bi me-2"
                :class="{
                  'bi-check-lg': isCurrentFilter(filterConfig.field, value),
                  'bi-circle': !isCurrentFilter(filterConfig.field, value)
                }"
                aria-hidden="true"
              ></i>
              {{ formatFilterLabel(value) }}
            </button>
          </li>
          <li v-if="availableFilters.length > 1 && index < availableFilters.length - 1" class="dropdown-divider"></li>
        </template>

        <!-- Clear All Filters Button (if any filter is active) -->
        <li v-if="hasActiveFilters">
          <li class="dropdown-divider"></li>
          <button
            type="button"
            class="dropdown-item d-flex align-items-center text-danger fw-semibold"
            role="menuitem"
            @click="handleClearAllFilters"
            :disabled="loading"
          >
            <i class="bi bi-x-circle-fill me-2" aria-hidden="true"></i>
            Clear Filters
          </button>
        </li>
      </template>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import type { FilterChangeEvent } from '~/types'

// Component Props based on design specification
interface Props {
  /** Complete filters node from API response */
  filters: {
    applied: { field: string; value: string } | null
    available: Array<{
      field: string
      label: string
      values: string[]
    }> | null
  } | null
  /** Loading state for the component */
  loading?: boolean
  /** Whether the filter is disabled */
  disabled?: boolean
}

// Component Emits based on design specification
interface Emits {
  /** Emitted when a filter is selected/changed */
  (event: 'filter-change', payload: FilterChangeEvent): void
  /** Emitted when filters are cleared */
  (event: 'filter-clear', payload: { field: string }): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  disabled: false
})

const emit = defineEmits<Emits>()

// Reactive refs
const dropdownRef = ref<HTMLElement>()

// Computed properties based on props
const availableFilters = computed(() => {
  return props.filters?.available || []
})

const appliedFilter = computed(() => {
  return props.filters?.applied || null
})

const hasActiveFilters = computed(() => {
  return appliedFilter.value !== null
})

const activeFiltersDescription = computed(() => {
  if (!appliedFilter.value) return ''
  
  const filterConfig = availableFilters.value.find(f => f.field === appliedFilter.value?.field)
  const label = filterConfig?.label || formatFilterLabel(appliedFilter.value.field)
  const value = formatFilterLabel(appliedFilter.value.value)
  
  return `${label}: ${value}`
})

const formatFilterLabel = (value: string | number): string => {
  // Convert to string and ensure it's clean
  const stringValue = String(value).trim()
  
  // Handle special cases first
  if (stringValue === 'all') return 'All'
  if (stringValue === 'active') return 'Active'
  if (stringValue === 'inactive') return 'Inactive'
  
  // Return the value as-is for display, but ensure proper formatting
  // For uppercase values like DRAFT, SENT, ACCEPTED, etc.
  if (stringValue === stringValue.toUpperCase() && stringValue.length > 1 && /^[A-Z]+$/.test(stringValue)) {
    // Return as Title Case for better readability
    return stringValue.charAt(0).toUpperCase() + stringValue.slice(1).toLowerCase()
  }
  
  // Handle snake_case
  if (stringValue.includes('_')) {
    return stringValue
      .split('_')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
      .join(' ')
  }
  
  // Handle camelCase
  if (/[a-z][A-Z]/.test(stringValue)) {
    return stringValue
      .replace(/([A-Z])/g, ' $1')
      .replace(/^./, l => l.toUpperCase())
      .trim()
  }
  
  // Default: return with first letter capitalized, rest lowercase
  return stringValue.charAt(0).toUpperCase() + stringValue.slice(1).toLowerCase()
}

const isCurrentFilter = (filterField: string, value: string | number): boolean => {
  return appliedFilter.value?.field === filterField && appliedFilter.value?.value === String(value)
}

// Methods based on design specification
const handleFilterChange = (field: string, value: string): void => {
  if (props.loading) return

  // Find the filter configuration to get the label
  const filterConfig = availableFilters.value.find(f => f.field === field)
  const filterLabel = filterConfig?.label || formatFilterLabel(field)
  
  // Emit filter change event with field, value, and label
  emit('filter-change', {
    field,
    value,
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

const handleClearAllFilters = (): void => {
  if (!appliedFilter.value) return

  // Emit clear event for the applied filter
  emit('filter-clear', {
    field: appliedFilter.value.field
  })

  // Close dropdown
  if (dropdownRef.value) {
    const dropdown = dropdownRef.value.querySelector('.dropdown-toggle') as HTMLElement
    if (dropdown && dropdown.getAttribute('aria-expanded') === 'true') {
      dropdown.click()
    }
  }
}
</script>

<style scoped>
.dropdown-toggle:disabled {
  cursor: not-allowed;
}

.dropdown-toggle.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  border-color: var(--bs-primary);
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

/* Clear all filters button styling */
.dropdown-item.text-danger {
  color: var(--bs-danger) !important;
}

.dropdown-item.text-danger:hover {
  background-color: var(--bs-danger);
  color: var(--bs-white) !important;
}

/* Active filter visual feedback */
.btn-primary .bi-check-circle-fill {
  color: var(--bs-white);
}

/* Fix text formatting issues - prevent letter spacing and ensure normal display */
.dropdown-item {
  letter-spacing: normal !important;
  text-transform: none !important;
  font-variant: normal !important;
  white-space: nowrap !important;
}

/* Ensure dropdown button text is also normal */
.dropdown-toggle span {
  letter-spacing: normal !important;
  text-transform: none !important;
  font-variant: normal !important;
  white-space: nowrap !important;
}

/* Prevent any inherited transformations */
.dropdown-menu {
  font-family: inherit !important;
  letter-spacing: normal !important;
}

/* Ensure text displays normally without spacing issues */
.dropdown-item,
.dropdown-toggle {
  font-feature-settings: normal !important;
  font-kerning: auto !important;
}
</style>
