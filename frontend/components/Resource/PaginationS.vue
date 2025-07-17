<template>
  <div v-if="showPagination && pagination && pagination.totalItems > 0" class="border-top py-3 mt-3">
    <div class="container-fluid px-3">
      <!-- Mobile View -->
      <div class="d-md-none">
        <div class="row g-2">
          <div class="col-12 text-center small text-muted mb-2">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visually-hidden">Loading...</span>
            </span>
            Showing {{ from }} to {{ to }} of {{ pagination.totalItems }} entries
          </div>
          
          <div class="col-6">
            <select 
              :value="pagination.currentPage" 
              @change="handlePageChange(+($event.target as HTMLSelectElement).value)"
              class="form-select form-select-sm"
              :disabled="loading || pagination.totalPages <= 1"
              aria-label="Select page"
            >
              <option v-for="page in pagination.totalPages" :key="page" :value="page">
                Page {{ page }}
              </option>
            </select>
          </div>
          
          <div class="col-6">
            <select 
              :value="pagination.itemsPerPage" 
              @change="handlePerPageChange(+($event.target as HTMLSelectElement).value)"
              class="form-select form-select-sm"
              :disabled="loading"
              aria-label="Items per page"
            >
              <option v-for="option in perPageOptions" :key="option" :value="option">
                {{ option }} per page
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Desktop View -->
      <div class="d-none d-md-flex justify-content-between align-items-center flex-wrap gap-3">
        <!-- Per page selector -->
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small">Show:</span>
          <select 
            :value="pagination.itemsPerPage" 
            @change="handlePerPageChange(+($event.target as HTMLSelectElement).value)"
            class="form-select form-select-sm"
            style="width: 80px;"
            :disabled="loading"
            aria-label="Items per page"
          >
            <option v-for="option in perPageOptions" :key="option" :value="option">
              {{ option }}
            </option>
          </select>
          <span class="text-muted small">per page</span>
          
          <div v-if="loading" class="spinner-border spinner-border-sm text-primary ms-2" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        
        <!-- Pagination controls -->
        <div class="d-flex align-items-center gap-3">
          <div class="text-muted small">
            Showing {{ from }} to {{ to }} of {{ pagination.totalItems }} entries
          </div>
          
          <!-- Jump to page -->
          <div v-if="pagination.totalPages > 10" class="d-flex align-items-center gap-2">
            <span class="text-muted small">Go to:</span>
            <input 
              ref="jumpToPageInput"
              v-model="jumpToPageValue"
              @keyup.enter="handleJumpToPage"
              @blur="handleJumpToPage"
              type="number" 
              :min="1" 
              :max="pagination.totalPages"
              class="form-control form-control-sm text-center"
              style="width: 70px;"
              :disabled="loading"
              :placeholder="`1-${pagination.totalPages}`"
              aria-label="Jump to page number"
            >
          </div>
          
          <!-- Pagination buttons -->
          <nav aria-label="Pagination Navigation">
            <ul class="pagination pagination-sm mb-0">
              <!-- First page -->
              <li v-if="pagination.totalPages > 7" class="page-item" :class="{ disabled: pagination.currentPage <= 1 || loading }">
                <button 
                  class="page-link"
                  @click="handlePageChange(1)"
                  :disabled="pagination.currentPage <= 1 || loading"
                  aria-label="Go to first page"
                  title="First page"
                >
                  ⇤
                </button>
              </li>
              
              <!-- Previous -->
              <li class="page-item" :class="{ disabled: !hasPrevPage || loading }">
                <button 
                  class="page-link"
                  @click="handlePageChange(pagination.currentPage - 1)"
                  :disabled="!hasPrevPage || loading"
                  aria-label="Go to previous page"
                  title="Previous page"
                >
                  ←
                </button>
              </li>
              
              <!-- Page numbers -->
              <li 
                v-for="page in visiblePages" 
                :key="page"
                class="page-item"
                :class="{ 
                  active: page === pagination.currentPage,
                  disabled: page === -1 || loading
                }"
              >
                <button 
                  v-if="page === -1"
                  class="page-link"
                  disabled
                  aria-label="More pages"
                >
                  ...
                </button>
                <button 
                  v-else
                  class="page-link"
                  @click="handlePageChange(page)"
                  :disabled="loading"
                  :aria-label="page === pagination.currentPage ? `Current page ${page}` : `Go to page ${page}`"
                  :aria-current="page === pagination.currentPage ? 'page' : undefined"
                >
                  {{ page }}
                </button>
              </li>
              
              <!-- Next -->
              <li class="page-item" :class="{ disabled: !hasNextPage || loading }">
                <button 
                  class="page-link"
                  @click="handlePageChange(pagination.currentPage + 1)"
                  :disabled="!hasNextPage || loading"
                  aria-label="Go to next page"
                  title="Next page"
                >
                  →
                </button>
              </li>
              
              <!-- Last page -->
              <li v-if="pagination.totalPages > 7" class="page-item" :class="{ disabled: pagination.currentPage >= pagination.totalPages || loading }">
                <button 
                  class="page-link"
                  @click="handlePageChange(pagination.totalPages)"
                  :disabled="pagination.currentPage >= pagination.totalPages || loading"
                  aria-label="Go to last page"
                  title="Last page"
                >
                  ⇥
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

// Props based on design specification
interface Props {
  /** Complete pagination node from API response */
  pagination: {
    totalItems: number;
    currentPage: number;
    itemsPerPage: number;
    totalPages: number;
    urlPath: string;
    urlQuery: string | null;
    nextPage: string | null;
    prevPage: string | null;
  } | null;
  /** Loading state for the component */
  loading?: boolean;
  /** Whether pagination is disabled */
  disabled?: boolean;
  /** Whether to show pagination info text */
  showInfo?: boolean;
  /** Available per-page options */
  perPageOptions?: number[];
}

// Events based on design specification
interface Emits {
  /** Emitted when page is changed */
  (event: 'page-change', payload: { page: number }): void;
  /** Emitted when per-page value is changed */
  (event: 'per-page-change', payload: { perPage: number }): void;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  disabled: false,
  showInfo: true,
  perPageOptions: () => [10, 25, 50, 100]
});

const emit = defineEmits<Emits>();

// Reactive references for component state
const jumpToPageInput = ref<HTMLInputElement>();
const jumpToPageValue = ref<string>('');

// Computed properties based on design specification
const showPagination = computed(() => {
  return props.pagination && props.pagination.totalItems > 0;
});

const hasNextPage = computed(() => {
  return props.pagination?.nextPage !== null;
});

const hasPrevPage = computed(() => {
  return props.pagination?.prevPage !== null;
});

const from = computed(() => {
  if (!props.pagination) return 0;
  return ((props.pagination.currentPage - 1) * props.pagination.itemsPerPage) + 1;
});

const to = computed(() => {
  if (!props.pagination) return 0;
  return Math.min(
    props.pagination.currentPage * props.pagination.itemsPerPage,
    props.pagination.totalItems
  );
});

const visiblePages = computed(() => {
  if (!props.pagination) return [];
  
  const current = props.pagination.currentPage;
  const total = props.pagination.totalPages;
  const pages: (number | -1)[] = [];
  
  if (total <= 7) {
    // Show all pages if 7 or fewer
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    // Complex logic for large page sets
    pages.push(1);
    
    if (current <= 4) {
      // Near beginning: show first 5 pages
      for (let i = 2; i <= Math.min(5, total - 1); i++) {
        pages.push(i);
      }
      if (total > 6) pages.push(-1); // ellipsis
    } else if (current >= total - 3) {
      // Near end: show last 5 pages
      if (total > 6) pages.push(-1); // ellipsis
      for (let i = Math.max(total - 4, 2); i <= total - 1; i++) {
        pages.push(i);
      }
    } else {
      // Middle: show current ± 1 with ellipsis on both sides
      pages.push(-1);
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i);
      }
      pages.push(-1);
    }
    
    if (total > 1) pages.push(total);
  }
  
  return pages;
});

// Methods based on design specification
const handlePageChange = (page: number) => {
  if (props.loading || props.disabled || !props.pagination) return;
  
  if (page < 1 || page > props.pagination.totalPages) return;
  
  emit('page-change', { page });
};

const handlePerPageChange = (perPage: number) => {
  if (props.loading || props.disabled) return;
  
  emit('per-page-change', { perPage });
};

const handleJumpToPage = () => {
  if (!jumpToPageValue.value || !props.pagination) return;
  
  const page = parseInt(jumpToPageValue.value);
  if (page >= 1 && page <= props.pagination.totalPages) {
    handlePageChange(page);
  }
  
  jumpToPageValue.value = '';
};
</script>

<style scoped>
/**
 * Responsive styles for mobile optimization
 */
@media (max-width: 576px) {
  .form-select-sm {
    font-size: 0.875rem;
  }
  
  .pagination-sm .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }
}

/**
 * Improved button spacing and hover states
 */
.page-link {
  transition: all 0.2s ease-in-out;
}

.page-link:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-item.active .page-link {
  background-color: var(--bs-primary);
  border-color: var(--bs-primary);
}

.page-item.disabled .page-link {
  opacity: 0.5;
  cursor: not-allowed;
}

/**
 * Loading state styles
 */
.spinner-border-sm {
  width: 0.875rem;
  height: 0.875rem;
}

/**
 * Jump to page input styles
 */
.form-control-sm {
  border-radius: 0.375rem;
}

.form-control-sm:focus {
  border-color: var(--bs-primary);
  box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

/**
 * Custom styles for pagination buttons to ensure consistent sizing
 */
.page-link {
  min-width: 2.5rem;
  text-align: center;
}
</style>
