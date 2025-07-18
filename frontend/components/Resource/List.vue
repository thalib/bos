<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Column } from '~/types';

// Define interfaces based on design specification
interface SortConfig {
  column: string;
  dir: 'asc' | 'desc';
}

interface Props {
  /** Array of items from API response */
  data: any[];
  /** Complete columns configuration from API response */
  columns: Column[];
  /** Current sort configuration from API response */
  sort: SortConfig | null;
  /** Loading state for the component */
  loading: boolean;
}

interface Emits {
  /** Emitted when an item is clicked */
  (event: 'item-click', payload: { item: any; index: number }): void;
  /** Emitted when column sorting is requested */
  (event: 'sort-change', payload: { column: string; direction: string }): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Reactive variables
const showDetails = ref(false);

// Computed properties
const hasData = computed(() => {
  return props.data && props.data.length > 0;
});

const hasValidColumns = computed(() => {
  return props.columns && Array.isArray(props.columns) && props.columns.length > 0;
});

const showError = computed(() => {
  return !hasValidColumns.value || (props.data === null || props.data === undefined);
});

// Event handlers
const handleItemClick = (item: any, index: number): void => {
  if (!props.loading) {
    emit('item-click', { item, index });
  }
};

const handleSortChange = (column: any): void => {
  if (!column.sortable || props.loading) return;
  
  let direction = 'asc';
  if (props.sort && props.sort.column === column.key) {
    direction = props.sort.dir === 'asc' ? 'desc' : 'asc';
  }
  
  emit('sort-change', { column: column.key, direction });
};

// Utility functions
const getSortIcon = (column: any): string => {
  if (!column.sortable) return '';
  
  if (props.sort && props.sort.column === column.key) {
    return props.sort.dir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
  }
  
  return 'bi-arrow-down-up';
};

const formatCellValue = (value: any): string => {
  if (value === null || value === undefined) return '-';
  return String(value);
};

const getCellClass = (column: any): string => {
  const classes: string[] = [];
  
  if (column.align) {
    classes.push(`text-${column.align}`);
  }
  
  return classes.join(' ');
};
</script>
<template>
  <!-- Error state -->
  <div v-if="showError" class="alert alert-danger" role="alert">
    <h5 class="alert-heading">Unable to load data</h5>
    <p class="mb-3">Please try again later or contact support.</p>
    <button type="button" class="btn btn-sm btn-outline-danger" @click="showDetails = !showDetails">
      {{ showDetails ? 'Hide' : 'Show' }} Details
    </button>
    <div v-if="showDetails" class="mt-2 text-muted small">
      {{ !hasValidColumns ? 'Invalid columns configuration' : 'No data available' }}
    </div>
  </div>

  <!-- Loading state -->
  <div v-else-if="loading" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-2 mb-0 text-muted">Loading data...</p>
  </div>

  <!-- Empty state -->
  <div v-else-if="!hasData" class="text-center py-5">
    <i class="bi bi-table fs-1 text-muted mb-3"></i>
    <h5 class="text-muted">No data available</h5>
    <p class="text-muted mb-0">There are no items to display.</p>
  </div>
  
  <!-- Data table -->
  <div v-else class="table-responsive">
    <table class="table table-hover table-striped table-bordered mb-0">
      <thead class="table-dark">
        <tr>
          <!-- Column headers -->
          <th 
            v-for="column in columns" 
            :key="column.key"
            :class="[
              'user-select-none fw-semibold text-uppercase small position-relative',
              column.sortable ? 'cursor-pointer' : '',
              props.sort?.column === column.key ? 'table-active' : ''
            ]"
            @click="column.sortable ? handleSortChange(column) : null"
          >
            <div class="d-flex align-items-center justify-content-between">
              <span>{{ column.label }}</span>
              <div v-if="column.sortable" class="ms-2">
                <i 
                  :class="[
                    'bi',
                    getSortIcon(column),
                    props.sort?.column === column.key ? 'text-warning' : 'text-muted opacity-50'
                  ]"
                ></i>
              </div>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr 
          v-for="(item, index) in data" 
          :key="item.id || index"
        >
          <!-- Data cells -->
          <td 
            v-for="column in columns" 
            :key="column.key"
            :class="getCellClass(column)"
            @click="column.clickable ? handleItemClick(item, index) : null"
          >
            <!-- Clickable column content -->
            <template v-if="column.clickable">
              <button 
                type="button" 
                class="btn btn-link text-decoration-none p-0 text-primary fw-medium"
                @click.stop="handleItemClick(item, index)"
              >
                {{ formatCellValue(item[column.key]) }}
              </button>
            </template>
            
            <!-- Default column content -->
            <template v-else>
              {{ formatCellValue(item[column.key]) }}
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.table-responsive {
  border-radius: 0.375rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table th {
  border-top: none;
  font-weight: 600;
  font-size: 0.875rem;
  padding: 0.875rem;
  vertical-align: middle;
  position: sticky;
  top: 0;
  background-color: var(--bs-dark);
  z-index: 10;
}

.table td {
  padding: 0.875rem;
  vertical-align: middle;
  border-color: var(--bs-border-color-translucent);
}

.table-hover tbody tr:hover {
  background-color: var(--bs-light);
}

.table-active {
  background-color: var(--bs-primary-bg-subtle);
}

.img-thumbnail {
  border: 1px solid var(--bs-border-color);
  transition: transform 0.2s ease;
}

.img-thumbnail:hover {
  transform: scale(1.1);
}

.btn-link:hover {
  text-decoration: underline !important;
}

/* Responsive styles */
@media (max-width: 768px) {
  .table th,
  .table td {
    font-size: 0.875rem;
    padding: 0.5rem;
  }
  
  .table th span {
    font-size: 0.75rem;
  }
  
  .img-thumbnail {
    width: 30px !important;
    height: 30px !important;
  }
}

@media (max-width: 576px) {
  .table th,
  .table td {
    font-size: 0.8rem;
    padding: 0.375rem;
  }
  
  .table th span {
    font-size: 0.7rem;
  }
  
  .img-thumbnail {
    width: 25px !important;
    height: 25px !important;
  }
}
</style>
