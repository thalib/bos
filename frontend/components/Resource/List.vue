<script setup lang="ts">
import { ref, computed } from 'vue';

interface Column {
  key: string;
  label?: string;
  sortable?: boolean;
  formatter?: (value: any, item?: any) => string;
  cellClass?: string;
}

interface ColumnsConfig {
  cols?: string[];
  sortable?: string[];
}

interface PaginationMeta {
  currentPage: number;
  totalPages: number;
  perPage: number;
  total: number;
  hasNextPage: boolean;
  hasPrevPage: boolean;
  from: number;
  to: number;
  nextPage?: number | null;
  prevPage?: number | null;
}

// Export the Column interface for other components
export type { Column }

const props = withDefaults(defineProps<{
  columns?: Column[] | ColumnsConfig;
  items: any[];
  loading?: boolean;
  error?: string | null;
  selectable?: boolean;
  showCheckbox?: boolean;
  modelValue?: any[];
  pagination?: PaginationMeta;
  showPagination?: boolean;
  sortField?: string;
  sortDirection?: 'asc' | 'desc';
}>(), {
  columns: () => ({ cols: [], sortable: [] }),
  loading: false,
  error: null,
  selectable: false,
  showCheckbox: false,
  modelValue: () => [],
  pagination: () => ({
    currentPage: 1,
    totalPages: 1,
    perPage: 20,
    total: 0,
    hasNextPage: false,
    hasPrevPage: false,
    from: 0,
    to: 0
  }),
  showPagination: true,
  sortField: '',
  sortDirection: 'asc'
});

const emit = defineEmits<{
  (e: 'sort', column: Column): void;
  (e: 'selectAll', selected: boolean): void;
  (e: 'selectItem', item: any): void;
  (e: 'update:error', value: string | null): void;
  (e: 'update:modelValue', value: any[]): void;
  (e: 'userClick', item: any): void;
}>();

// Optimized column processing
const processedColumns = computed<Column[]>(() => {
  if (Array.isArray(props.columns)) {
    return props.columns;
  }

  const config = props.columns as ColumnsConfig;
  const defaultColumns: Column[] = [
    { key: 'id', label: 'ID', sortable: true },
    { 
      key: 'name', 
      label: 'NAME', 
      sortable: true, 
      formatter: (value: string, item: any) => value ? `<button type="button" class="btn btn-link text-decoration-none p-0 text-primary fw-medium" data-item-id="${item.id}" data-field-key="name">${value}</button>` : '-',
      cellClass: 'text-primary fw-medium'
    }
  ];

  const additionalColumns = (config.cols || []).map(key => ({
    key,
    label: key.charAt(0).toUpperCase() + key.slice(1),
    sortable: config.sortable?.includes(key) || false
  }));

  return [...defaultColumns, ...additionalColumns];
});

const selectAll = ref(false);

// Check if a column is clickable
const isColumnClickable = (column: Column) => {
  return column.formatter && column.cellClass?.includes('text-primary');
};

// Toggle selection of all items
const toggleSelectAll = () => {
  selectAll.value = !selectAll.value;
  emit('selectAll', selectAll.value);
};

// Toggle selection of a single item
const toggleSelect = (item: any) => {
  emit('selectItem', item);
};

// Sort column
const sortColumn = (column: Column) => {
  emit('sort', column);
};

// Simplified user click handler
const handleUserClick = (event: Event) => {
  const target = event.target as HTMLElement;
  const itemId = target.getAttribute('data-item-id');
  const fieldKey = target.getAttribute('data-field-key');
  if (itemId && fieldKey) {
    const item = props.items.find(item => item.id == itemId);
    if (item) {
      emit('userClick', item);
    }
  }
};
</script>

<template>
  <!-- Error alert -->
  <div v-if="error" class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ error }}
    <button @click="emit('update:error', null)" type="button" class="btn-close" aria-label="Close"></button>
  </div>

  <!-- Loading state -->
  <div v-if="loading" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-2 mb-0">Loading...</p>
  </div>

  <!-- Empty state -->
  <div v-else-if="items.length === 0" class="text-center py-5">
    <i class="bi bi-table fs-1 text-muted"></i>
    <p class="mt-2 mb-0">No data found.</p>
  </div>
  
  <!-- Data table -->
  <div v-else class="table-responsive my-3">
    <table class="table table-hover table-bordered mb-0">
      <thead>
        <tr>
          <th v-if="selectable && showCheckbox" class="ps-3" style="width: 50px;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" :checked="selectAll" @change="toggleSelectAll">
            </div>
          </th>
          <th v-for="column in processedColumns" :key="column.key" 
            @click="column.sortable ? sortColumn(column) : null"
            :class="['user-select-none fw-semibold text-uppercase small', column.sortable ? 'cursor-pointer' : '', sortField === column.key ? 'table-active' : '']">
            <div class="d-flex align-items-center justify-content-between">
              <span>{{ (column.label || column.key).toUpperCase() }}</span>
              <div v-if="column.sortable" class="ms-2">
                <i v-if="sortField === column.key && sortDirection === 'asc'" class="bi bi-arrow-up text-primary"></i>
                <i v-else-if="sortField === column.key && sortDirection === 'desc'" class="bi bi-arrow-down text-primary"></i>
                <i v-else class="bi bi-arrow-down-up text-muted opacity-50"></i>
              </div>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in items" :key="index">
          <td v-if="selectable && showCheckbox" class="ps-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" :checked="item.selected" @change="toggleSelect(item)">
            </div>
          </td>
          <td v-for="column in processedColumns" :key="column.key" 
            :class="column.cellClass"
            @click="isColumnClickable(column) ? handleUserClick($event) : null">
            <span v-if="column.formatter" v-html="column.formatter(item[column.key], item)"></span>
            <template v-else>
              {{ item[column.key] !== undefined ? item[column.key] : '-' }}
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
@media (max-width: 576px) {
  .table th,
  .table td {
    font-size: 0.875rem;
  }
}
</style>
