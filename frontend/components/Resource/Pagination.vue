<template>
  <div v-if="showPagination && !loading && total > 0" class="border-top py-3 mt-3 sticky-bottom bg-body">
    <div class="container-fluid px-3">
      <!-- Mobile View -->
      <div class="d-md-none">
        <div class="row g-2">
          <div class="col-12 text-center small text-muted mb-2">
            Showing {{ from }} to {{ to }} of {{ total }} entries
          </div>
          
          <div class="col-6">
            <select 
              :value="currentPage" 
              @change="handlePageChange(+($event.target as HTMLSelectElement).value)"
              class="form-select form-select-sm"
              :disabled="loading || totalPages <= 1"
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
                  <i class="bi bi-chevron-double-left"></i>
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
                  <i class="bi bi-chevron-left"></i>
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
                  <i class="bi bi-chevron-right"></i>
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
                  <i class="bi bi-chevron-double-right"></i>
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

interface Props {
  currentPage: number;
  totalPages: number;
  perPage: number;
  total: number;
  loading?: boolean;
  perPageOptions?: number[];
  from?: number;
  to?: number;
  hasNextPage?: boolean;
  hasPrevPage?: boolean;
  showPagination?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  perPageOptions: () => [20, 50, 100],
  from: 0,
  to: 0,
  hasNextPage: false,
  hasPrevPage: false,
  showPagination: true
});

interface Emits {
  (e: 'page-change', page: number): void;
  (e: 'per-page-change', perPage: number): void;
}

const emit = defineEmits<Emits>();

const jumpToPageInput = ref<HTMLInputElement>();
const jumpToPageValue = ref<string>('');

const visiblePages = computed(() => {
  const current = props.currentPage;
  const total = props.totalPages;
  const pages: (number | -1)[] = [];
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    pages.push(1);
    
    if (current <= 4) {
      for (let i = 2; i <= Math.min(5, total - 1); i++) {
        pages.push(i);
      }
      if (total > 6) pages.push(-1);
    } else if (current >= total - 3) {
      if (total > 6) pages.push(-1);
      for (let i = Math.max(total - 4, 2); i <= total - 1; i++) {
        pages.push(i);
      }
    } else {
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

const handlePageChange = (page: number) => {
  if (page < 1 || page > props.totalPages || page === props.currentPage || props.loading) return;
  emit('page-change', page);
};

const handlePerPageChange = (newPerPage: number) => {
  if (newPerPage === props.perPage || props.loading) return;
  emit('per-page-change', newPerPage);
};

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
@media (max-width: 576px) {
  .form-select-sm {
    font-size: 0.875rem;
  }
}
</style>
