<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Column } from '~/types';

// Define interfaces based on design specification
interface SortConfig {
  column: string;
  dir: 'asc' | 'desc';
}

// Props based on design specification
interface Props {
  /** Array of items from API response */
  data: any[];
  /** Complete columns configuration from API response */
  columns: Column[];
  /** Current sort configuration from API response */
  sort: SortConfig | null;
  /** Loading state for the component */
  loading: boolean;
  /** Error object from API response */
  error: any | null;
  /** Whether items are clickable */
  clickable?: boolean;
  /** Whether items can be selected */
  selectable?: boolean;
}

// Events based on design specification
interface Emits {
  /** Emitted when an item is clicked */
  (event: 'item-click', payload: { item: any; index: number }): void;
  /** Emitted when items are selected */
  (event: 'item-select', payload: { selectedItems: any[] }): void;
  /** Emitted when column sorting is requested */
  (event: 'sort-change', payload: { column: string; direction: string }): void;
}

const props = withDefaults(defineProps<Props>(), {
  clickable: true,
  selectable: false
});

const emit = defineEmits<Emits>();

// Component state
const selectedItems = ref<any[]>([]);
const selectAll = ref(false);

// Computed properties
const visibleColumns = computed(() => {
  return props.columns.filter(column => !column.hidden);
});

const hasData = computed(() => {
  return props.data && props.data.length > 0;
});

const isAllSelected = computed(() => {
  return props.data.length > 0 && selectedItems.value.length === props.data.length;
});

const isSomeSelected = computed(() => {
  return selectedItems.value.length > 0 && selectedItems.value.length < props.data.length;
});

// Methods
const handleItemClick = (item: any, index: number) => {
  if (props.clickable && !props.loading) {
    emit('item-click', { item, index });
  }
};

const handleItemSelect = (item: any, selected: boolean) => {
  if (selected) {
    selectedItems.value.push(item);
  } else {
    const index = selectedItems.value.findIndex(i => i.id === item.id);
    if (index > -1) {
      selectedItems.value.splice(index, 1);
    }
  }
  
  emit('item-select', { selectedItems: selectedItems.value });
};

const handleSelectAll = (selected: boolean) => {
  if (selected) {
    selectedItems.value = [...props.data];
  } else {
    selectedItems.value = [];
  }
  
  emit('item-select', { selectedItems: selectedItems.value });
};

const handleSortChange = (column: Column) => {
  if (!column.sortable || props.loading) return;
  
  let direction = 'asc';
  if (props.sort?.column === column.key) {
    direction = props.sort.dir === 'asc' ? 'desc' : 'asc';
  }
  
  emit('sort-change', { column: column.key, direction });
};

const isSelected = (item: any) => {
  return selectedItems.value.some(i => i.id === item.id);
};

const getSortIcon = (column: Column) => {
  if (!column.sortable) return '';
  
  if (props.sort?.column === column.key) {
    return props.sort.dir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
  }
  
  return 'bi-arrow-down-up';
};

const formatCellValue = (value: any, column: Column) => {
  if (value === null || value === undefined) return '-';
  
  // If column has a formatter, use it
  if (column.formatter) {
    return column.formatter(value, props.data);
  }
  
  // Use built-in formatters based on column type
  switch (column.type) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(value);
    case 'date':
      return new Date(value).toLocaleDateString();
    case 'datetime':
      return new Date(value).toLocaleString();
    case 'number':
      return new Intl.NumberFormat('en-US').format(value);
    case 'boolean':
      return value ? 'Yes' : 'No';
    case 'email':
      return value;
    case 'url':
      return value;
    default:
      return String(value);
  }
};

const getCellClass = (column: Column) => {
  const classes = [];
  
  if (column.cellClass) {
    classes.push(column.cellClass);
  }
  
  if (column.align) {
    classes.push(`text-${column.align}`);
  }
  
  if (column.clickable && props.clickable) {
    classes.push('cursor-pointer');
  }
  
  return classes.join(' ');
};
</script>
<template>
  <!-- Error state -->
  <div v-if="error" class="alert alert-danger" role="alert">
    <h5 class="alert-heading">Error loading data</h5>
    <p class="mb-0">{{ error.message || 'An error occurred while loading data' }}</p>
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
    <i class="bi bi-table fs-1 text-muted"></i>
    <h5 class="text-muted mt-2">No data available</h5>
    <p class="text-muted mb-0">There are no items to display.</p>
  </div>
  
  <!-- Data table -->
  <div v-else class="table-responsive">
    <table class="table table-hover table-striped mb-0">
      <thead class="table-light">
        <tr>
          <!-- Selection checkbox column -->
          <th v-if="selectable" class="ps-3" style="width: 50px;">
            <div class="form-check">
              <input 
                class="form-check-input" 
                type="checkbox" 
                :checked="isAllSelected"
                :indeterminate="isSomeSelected"
                @change="handleSelectAll(!isAllSelected)"
                :disabled="loading"
                aria-label="Select all items"
              >
            </div>
          </th>
          
          <!-- Column headers -->
          <th 
            v-for="column in visibleColumns" 
            :key="column.key"
            :class="[
              'user-select-none fw-semibold text-uppercase small',
              column.sortable ? 'cursor-pointer' : '',
              props.sort?.column === column.key ? 'table-active' : ''
            ]"
            :style="{ width: column.width }"
            @click="column.sortable ? handleSortChange(column) : null"
          >
            <div class="d-flex align-items-center justify-content-between">
              <span>{{ column.label }}</span>
              <div v-if="column.sortable" class="ms-2">
                <i 
                  :class="[
                    'bi',
                    getSortIcon(column),
                    props.sort?.column === column.key ? 'text-primary' : 'text-muted opacity-50'
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
          :class="{ 'table-primary': selectable && isSelected(item) }"
        >
          <!-- Selection checkbox -->
          <td v-if="selectable" class="ps-3">
            <div class="form-check">
              <input 
                class="form-check-input" 
                type="checkbox" 
                :checked="isSelected(item)"
                @change="handleItemSelect(item, !isSelected(item))"
                :disabled="loading"
                :aria-label="`Select item ${index + 1}`"
              >
            </div>
          </td>
          
          <!-- Data cells -->
          <td 
            v-for="column in visibleColumns" 
            :key="column.key"
            :class="getCellClass(column)"
            @click="column.clickable ? handleItemClick(item, index) : null"
          >
            <span v-if="column.type === 'boolean'">
              <i 
                :class="[
                  'bi',
                  item[column.key] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'
                ]"
              ></i>
              {{ formatCellValue(item[column.key], column) }}
            </span>
            <span v-else-if="column.type === 'email'">
              <a :href="`mailto:${item[column.key]}`" class="text-decoration-none">
                {{ formatCellValue(item[column.key], column) }}
              </a>
            </span>
            <span v-else-if="column.type === 'url'">
              <a :href="item[column.key]" target="_blank" class="text-decoration-none">
                {{ formatCellValue(item[column.key], column) }}
              </a>
            </span>
            <span v-else-if="column.type === 'image'">
              <img 
                :src="item[column.key]" 
                :alt="column.label"
                class="img-thumbnail" 
                style="width: 40px; height: 40px; object-fit: cover;"
              >
            </span>
            <span v-else>
              {{ formatCellValue(item[column.key], column) }}
            </span>
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
}

.table th {
  border-top: none;
  font-weight: 600;
  font-size: 0.875rem;
  padding: 0.75rem;
  vertical-align: middle;
}

.table td {
  padding: 0.75rem;
  vertical-align: middle;
}

.table-hover tbody tr:hover {
  background-color: var(--bs-light);
}

.table-active {
  background-color: var(--bs-primary-bg-subtle);
}

.img-thumbnail {
  border: 1px solid var(--bs-border-color);
}

@media (max-width: 576px) {
  .table th,
  .table td {
    font-size: 0.875rem;
    padding: 0.5rem;
  }
  
  .table th span {
    font-size: 0.75rem;
  }
}
</style>
