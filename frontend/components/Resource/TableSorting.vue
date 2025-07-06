<template>
  <div v-if="hasActiveFilters" class="mb-3">
    <!-- Mobile View: Compact Layout -->
    <div class="d-md-none">
      <div class="card shadow-sm">
        <div class="card-body py-2">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <small class="text-muted fw-semibold">
              <i class="bi bi-funnel-fill me-1"></i>
              {{ filterCount }} filter{{ filterCount !== 1 ? 's' : '' }} active
            </small>
            <button
              type="button"
              class="btn btn-outline-danger btn-sm"
              title="Clear all filters (Ctrl+Shift+X)"
              @click="handleClearFiltersOnly"
              :disabled="loading"
              aria-label="Clear all filters"
            >
              <i class="bi bi-x-circle me-1"></i>
              Clear Filters
            </button>
          </div>
          
          <!-- Mobile Filter Badges - Stacked -->
          <div class="vstack gap-1">
            <transition name="filter-fade" appear>
              <div v-if="hasSearchFilter">
                <span class="badge bg-primary d-flex align-items-center justify-content-between w-100 p-2">
                  <span class="d-flex align-items-center flex-grow-1 text-truncate">
                    <i class="bi bi-search me-1 flex-shrink-0"></i>
                    <span class="text-truncate">Search: "{{ searchQuery }}"</span>
                  </span>
                  <button
                    type="button"
                    class="btn-close btn-close-white ms-2 flex-shrink-0"
                    style="--bs-btn-close-font-size: 0.6em;"
                    @click="handleClearSearch"
                    :disabled="loading"
                    aria-label="Clear search filter"
                    data-bs-toggle="tooltip"
                    title="Clear search filter"
                  ></button>
                </span>
              </div>
            </transition>
            
            <transition name="filter-fade" appear>
              <div v-if="hasFilters">
                <span class="badge bg-info d-flex align-items-center justify-content-between w-100 p-2">
                  <span class="d-flex align-items-center">
                    <i class="bi bi-funnel-fill me-1"></i>
                    <span>Filters ({{ filterCount }})</span>
                  </span>
                  <button
                    type="button"
                    class="btn-close btn-close-white ms-2"
                    style="--bs-btn-close-font-size: 0.6em;"
                    @click="handleClearFiltersOnly"
                    :disabled="loading"
                    aria-label="Clear filters only"
                    data-bs-toggle="tooltip"
                    title="Clear filters only"
                  ></button>
                </span>
              </div>
            </transition>
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop View: Horizontal Layout -->
    <div class="d-none d-md-flex align-items-center flex-wrap gap-2">            <small class="text-muted fw-semibold me-2">
              <i class="bi bi-funnel-fill me-1"></i>
              Active filters ({{ filterCount }}):
            </small>
      
      <!-- Search Filter Badge -->
      <transition name="filter-slide" appear>
        <span 
          v-if="hasSearchFilter" 
          class="badge bg-primary d-flex align-items-center p-2 rounded-pill cursor-pointer position-relative shadow-sm"
          role="button"
          tabindex="0"
          style="max-width: 300px; transition: all 0.15s ease-in-out;"
          @keydown.enter="handleClearSearch"
          @keydown.space="handleClearSearch"
        >
          <i class="bi bi-search me-1" aria-hidden="true"></i>
          <span class="text-truncate" style="max-width: 200px;">Search: "{{ searchQuery }}"</span>
          <button
            type="button"
            class="btn-close btn-close-white ms-2"
            style="--bs-btn-close-font-size: 0.75em;"
            @click="handleClearSearch"
            :disabled="loading"
            aria-label="Clear search filter"
            data-bs-toggle="tooltip"
            title="Clear search filter"
          ></button>
        </span>
      </transition>
      
      <!-- Filter Badge -->
      <transition name="filter-slide" appear>
        <span 
          v-if="hasFilters" 
          class="badge bg-info d-flex align-items-center p-2 rounded-pill cursor-pointer position-relative shadow-sm"
          role="button"
          tabindex="0"
          style="max-width: 300px; transition: all 0.15s ease-in-out;"
          @keydown.enter="handleClearFiltersOnly"
          @keydown.space="handleClearFiltersOnly"  
        >
          <i class="bi bi-funnel-fill me-1" aria-hidden="true"></i>
          <span class="text-truncate" style="max-width: 200px;">Filters ({{ filterCount }})</span>
          <button
            type="button"
            class="btn-close btn-close-white ms-2"
            style="--bs-btn-close-font-size: 0.75em;"
            @click="handleClearFiltersOnly"
            :disabled="loading"
            aria-label="Clear filters only"
            data-bs-toggle="tooltip"
            title="Clear filters only"
          ></button>
        </span>
      </transition>
      
      <!-- Clear All Filters Button -->
      <transition name="filter-slide" appear>
        <button
          v-if="hasFilters"
          type="button"
          class="btn btn-outline-danger btn-sm shadow-sm"
          style="transition: all 0.15s ease-in-out;"
          title="Clear filters (Ctrl+Shift+X)"
          @click="handleClearFiltersOnly"
          :disabled="loading"
          aria-label="Clear filters"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
          <i v-else class="bi bi-x-circle me-1" aria-hidden="true"></i>
          Clear Filters
        </button>
      </transition>
    </div>

    <!-- Filter Summary for Screen Readers -->
    <div class="visually-hidden" aria-live="polite" aria-atomic="true">
      {{ filterSummaryText }}
    </div>
  </div>

  <!-- Empty State -->
  <div v-else class="visually-hidden" aria-live="polite">
    No active filters
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'

// Define TypeScript interfaces
interface Props {
  searchQuery: string
  filterCount?: number
  loading?: boolean
}

interface Emits {
  (e: 'clear-search'): void
  (e: 'clear-filters-only'): void
}

// Export types for use in other components
export type ResourceSortingProps = Props
export type ResourceSortingEmits = Emits

// Define props and emits
const props = withDefaults(defineProps<Props>(), {
  loading: false,
  filterCount: 0
})

const emit = defineEmits<Emits>()

// Computed properties
const hasSearchFilter = computed(() => props.searchQuery.trim().length > 0)
const hasFilters = computed(() => props.filterCount > 0)
const hasActiveFilters = computed(() => hasSearchFilter.value || hasFilters.value)

const filterSummaryText = computed(() => {
  if (!hasActiveFilters.value) return 'No active filters'
  
  const filters = []
  if (hasSearchFilter.value) filters.push(`Search for "${props.searchQuery}"`)
  
  return `Active filters: ${filters.join(', ')}`
})

// Event handlers
const handleClearSearch = () => emit('clear-search')
const handleClearFiltersOnly = () => emit('clear-filters-only')

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key === 'X') {
    event.preventDefault()
    if (hasActiveFilters.value) {
      handleClearFiltersOnly()
    }
  }
}

// Lifecycle
onMounted(() => document.addEventListener('keydown', handleKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', handleKeydown))
</script>

<style scoped>
/* Custom cursor class for interactive elements */
.cursor-pointer {
  cursor: pointer;
}

/* Hover effects using Bootstrap shadow utilities */
.badge:hover,
.btn:hover {
  transform: translateY(-1px);
}

/* Focus styles for accessibility */
.badge:focus-visible,
.btn:focus-visible {
  outline: 2px solid var(--bs-primary);
  outline-offset: 2px;
}

/* Animation transitions */
.filter-fade-enter-active,
.filter-fade-leave-active,
.filter-slide-enter-active,
.filter-slide-leave-active {
  transition: all 0.2s ease;
}

.filter-fade-enter-from,
.filter-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.filter-slide-enter-from,
.filter-slide-leave-to {
  opacity: 0;
  transform: translateX(-16px);
}

/* Responsive text truncation for smaller screens */
@media (max-width: 576px) {
  .text-truncate {
    max-width: 150px;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .filter-fade-enter-active,
  .filter-fade-leave-active,
  .filter-slide-enter-active,
  .filter-slide-leave-active,
  .badge,
  .btn {
    transition: none;
  }
  
  .badge:hover,
  .btn:hover {
    transform: none;
  }
}
</style>
