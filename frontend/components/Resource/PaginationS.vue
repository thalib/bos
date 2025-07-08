<template>
  <div v-if="showPagination && total > 0" class="border-top py-3 mt-3">
    <div class="container-fluid px-3">
      <!-- Mobile View -->
      <div class="d-md-none">
        <div class="row g-2">
          <div class="col-12 text-center small text-muted mb-2">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visually-hidden">Loading...</span>
            </span>
            Showing {{ from }} to {{ to }} of {{ total }} entries
          </div>
          
          <div class="col-6">
            <select 
              :value="currentPage" 
              @change="handlePageChange(+($event.target as HTMLSelectElement).value)"
              class="form-select form-select-sm"
              :disabled="loading || totalPages <= 1"
              aria-label="Select page"
            >
              <option v-for="page in totalPages" :key="page" :value="page">
                Page {{ page }}
              </option>
            </select>
          </div>
          
          <div class="col-6">
            <select 
              :value="perPage" 
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
            :value="perPage" 
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
            Showing {{ from }} to {{ to }} of {{ total }} entries
          </div>
          
          <!-- Jump to page -->
          <div v-if="totalPages > 10" class="d-flex align-items-center gap-2">
            <span class="text-muted small">Go to:</span>
            <input 
              ref="jumpToPageInput"
              v-model="jumpToPageValue"
              @keyup.enter="handleJumpToPage"
              @blur="handleJumpToPage"
              type="number" 
              :min="1" 
              :max="totalPages"
              class="form-control form-control-sm text-center"
              style="width: 70px;"
              :disabled="loading"
              :placeholder="`1-${totalPages}`"
              aria-label="Jump to page number"
            >
          </div>
          
          <!-- Pagination buttons -->
          <nav aria-label="Pagination Navigation">
            <ul class="pagination pagination-sm mb-0">
              <!-- First page -->
              <li v-if="totalPages > 7" class="page-item" :class="{ disabled: currentPage <= 1 || loading }">
                <button 
                  class="page-link"
                  @click="handlePageChange(1)"
                  :disabled="currentPage <= 1 || loading"
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
                  @click="handlePageChange(currentPage - 1)"
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
                  active: page === currentPage,
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
                  :aria-label="page === currentPage ? `Current page ${page}` : `Go to page ${page}`"
                  :aria-current="page === currentPage ? 'page' : undefined"
                >
                  {{ page }}
                </button>
              </li>
              
              <!-- Next -->
              <li class="page-item" :class="{ disabled: !hasNextPage || loading }">
                <button 
                  class="page-link"
                  @click="handlePageChange(currentPage + 1)"
                  :disabled="!hasNextPage || loading"
                  aria-label="Go to next page"
                  title="Next page"
                >
                  →
                </button>
              </li>
              
              <!-- Last page -->
              <li v-if="totalPages > 7" class="page-item" :class="{ disabled: currentPage >= totalPages || loading }">
                <button 
                  class="page-link"
                  @click="handlePageChange(totalPages)"
                  :disabled="currentPage >= totalPages || loading"
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
import { ref, computed, watch, onBeforeUnmount } from 'vue';

/**
 * Props interface for the PaginationS component
 */
interface PaginationProps {
  /** Current active page number (1-based) */
  currentPage: number;
  /** Total number of pages available */
  totalPages: number;
  /** Number of items displayed per page */
  perPage: number;
  /** Total number of items across all pages */
  total: number;
  /** Whether the component is in loading state */
  loading?: boolean;
  /** Array of available items per page options */
  perPageOptions?: number[];
  /** Starting item number for current page (1-based) */
  from?: number;
  /** Ending item number for current page */
  to?: number;
  /** Whether there is a next page available */
  hasNextPage?: boolean;
  /** Whether there is a previous page available */
  hasPrevPage?: boolean;
  /** Whether to show the pagination component */
  showPagination?: boolean;
}

/**
 * Emits interface for the PaginationS component events
 */
interface PaginationEmits {
  /** Emitted when user changes the current page */
  (e: 'page-change', page: number): void;
  /** Emitted when user changes the items per page */
  (e: 'per-page-change', perPage: number): void;
}

/**
 * Component props with default values and JSDoc documentation
 */
const props = withDefaults(defineProps<PaginationProps>(), {
  /** @default false - Component is not loading by default */
  loading: false,
  /** @default [20, 50, 100] - Standard per page options */
  perPageOptions: () => [20, 50, 100],
  /** @default 0 - No items shown from start */
  from: 0,
  /** @default 0 - No items shown to end */
  to: 0,
  /** @default false - No next page available by default */
  hasNextPage: false,
  /** @default false - No previous page available by default */
  hasPrevPage: false,
  /** @default true - Show pagination by default */
  showPagination: true
});

/**
 * Component event emitters
 */
const emit = defineEmits<PaginationEmits>();

// Reactive references for component state
const jumpToPageInput = ref<HTMLInputElement>();
const jumpToPageValue = ref<string>('');

/**
 * Computed property to calculate visible page numbers with ellipsis logic
 * @returns Array of page numbers or -1 for ellipsis
 */
const visiblePages = computed(() => {
  const current = props.currentPage;
  const total = props.totalPages;
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

/**
 * Handles page change events with validation
 * @param page - Target page number
 */
const handlePageChange = (page: number) => {
  if (page < 1 || page > props.totalPages || page === props.currentPage || props.loading) return;
  emit('page-change', page);
};

/**
 * Handles per-page change events with validation
 * @param newPerPage - New items per page value
 */
const handlePerPageChange = (newPerPage: number) => {
  if (newPerPage === props.perPage || props.loading) return;
  emit('per-page-change', newPerPage);
};

/**
 * Handles jump-to-page functionality with input validation
 */
const handleJumpToPage = () => {
  const pageValue = +jumpToPageValue.value;
  
  if (pageValue >= 1 && pageValue <= props.totalPages) {
    handlePageChange(pageValue);
    jumpToPageValue.value = '';
    jumpToPageInput.value?.blur();
  } else {
    jumpToPageValue.value = '';
  }
};

/**
 * Handles keyboard navigation for accessibility
 * @param event - Keyboard event
 */
const handleKeyboardNavigation = (event: KeyboardEvent) => {
  const activeElement = document.activeElement;
  if (activeElement?.matches('input, select, textarea') || props.loading) return;
  
  const keyActions: Record<string, () => void> = {
    ArrowLeft: () => props.hasPrevPage && handlePageChange(props.currentPage - 1),
    ArrowRight: () => props.hasNextPage && handlePageChange(props.currentPage + 1),
    Home: () => props.currentPage > 1 && handlePageChange(1),
    End: () => props.currentPage < props.totalPages && handlePageChange(props.totalPages)
  };
  
  const action = keyActions[event.key];
  if (action) {
    event.preventDefault();
    action();
  }
};

// Setup and cleanup keyboard event listeners
watch(() => props.showPagination, (show) => {
  if (show) {
    document.addEventListener('keydown', handleKeyboardNavigation);
  } else {
    document.removeEventListener('keydown', handleKeyboardNavigation);
  }
}, { immediate: true });

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyboardNavigation);
});
</script>

<style scoped>
/**
 * Responsive styles for mobile optimization
 */
@media (max-width: 576px) {
  .form-select-sm {
    font-size: 0.875rem;
  }
}

/**
 * Custom styles for pagination buttons to ensure consistent sizing
 */
.page-link {
  min-width: 2.5rem;
  text-align: center;
}
</style>

<!--
USAGE EXAMPLE:

<template>
  <div>
    <PaginationS
      :current-page="currentPage"
      :total-pages="totalPages"
      :per-page="perPage"
      :total="totalItems"
      :from="fromItem"
      :to="toItem"
      :has-next-page="hasNext"
      :has-prev-page="hasPrev"
      :loading="isLoading"
      :per-page-options="[10, 25, 50, 100]"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />
  </div>
</template>

<script setup lang="ts">
const currentPage = ref(1);
const totalPages = ref(10);
const perPage = ref(20);
const totalItems = ref(200);
const fromItem = ref(1);
const toItem = ref(20);
const hasNext = ref(true);
const hasPrev = ref(false);
const isLoading = ref(false);

const onPageChange = (page: number) => {
  currentPage.value = page;
  // Implement your page change logic
};

const onPerPageChange = (newPerPage: number) => {
  perPage.value = newPerPage;
  // Implement your per-page change logic
};
</script>
-->
