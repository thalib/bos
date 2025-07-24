# List Component Design Specification

The `List` component is a **self-contained** data table that integrates directly with the API service to display resources. It handles its own data fetching, sorting, and interaction logic while maintaining URL synchronization for sorting state.

**File Location:** `frontend/app/components/Resource/List.vue`

## TDD Requirements

**Test First Approach - Write these tests BEFORE implementation:**

```javascript
// frontend/tests/components/Resource/List.spec.ts
describe('List Component', () => {
  it('should render responsive table with Bootstrap 5.3 styling')
  it('should fetch and display data from API service')
  it('should handle column sorting with visual indicators')
  it('should update URL parameters when sort changes')
  it('should display loading states during data fetch')
  it('should handle API errors gracefully with notify service')
  it('should show empty state when no data available')
  it('should be accessible (ARIA labels, keyboard navigation)')
})
```

## Component Structure (Self-Contained)

The component requires **minimal props** and handles all data logic internally:

```html
<List
  resource="products"
  :filters="currentFilters"
  :search="currentSearch"
  :page="currentPage"
  @item-selected="onItemSelection"
  @sort-changed="onSortUpdate"
/>
```

- **Props:**
  - `resource` (string, required): The API resource to display
  - `filters` (object, optional): Applied filters to include in data fetch
  - `search` (string, optional): Search query to include in data fetch
  - `page` (number, optional): Current page for data fetch
- **Events:**
  - `item-selected`: Emitted when an item is clicked/selected. Payload: `{ item: object, index: number }`
  - `sort-changed`: Emitted when sorting changes. Payload: `{ column: string, direction: 'asc'|'desc' }`

## Internal Architecture

```txt
List Component (Self-Contained)
├── Internal State Management
│   ├── tableData (reactive)
│   ├── columns (reactive)
│   ├── sortConfig (reactive)
│   ├── isLoading (reactive)
│   ├── hasError (reactive)
│   └── selectedItems (reactive)
├── API Integration (useApiService)
│   ├── Fetch paginated data
│   ├── Handle sorting requests
│   └── Automatic error handling
├── URL State Sync
│   ├── Read sort from route.query
│   ├── Update URL when sort changes
│   └── Browser back/forward support
├── Table Logic
│   ├── Dynamic column rendering
│   ├── Row click handling
│   ├── Sort indicator display
│   └── Responsive table behavior
└── Notification Integration (useNotifyService)
    ├── Data loading feedback
    ├── Error notifications
    └── Success confirmations
```

## Features

- **Self-Contained Logic**: Manages its own data fetching, sorting, and table state
- **Dynamic Columns**: Renders columns based on API response configuration
- **Responsive Table**: Bootstrap 5.3 responsive table with mobile-friendly design
- **Sorting Integration**: Visual sort indicators with API-backed sorting
- **URL State Management**: Syncs sort state with browser URL for bookmarking
- **Row Interactions**: Clickable rows with selection states
- **Loading States**: Skeleton loading and shimmer effects
- **Empty States**: User-friendly empty state with action suggestions
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support

## UI Design & Bootstrap 5.3 Implementation

```html
<!-- Responsive Table Container -->
<div class="list-container">
  <!-- Loading State -->
  <div v-if="isLoading" class="table-loading">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th v-for="n in 5" :key="n" class="placeholder-glow">
              <span class="placeholder col-8"></span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="n in 10" :key="n">
            <td v-for="m in 5" :key="m" class="placeholder-glow">
              <span class="placeholder col-12"></span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Data Table -->
  <div v-else-if="tableData.length > 0" class="table-responsive">
    <table class="table table-hover table-striped">
      <thead class="table-light sticky-top">
        <tr>
          <th 
            v-for="column in columns" 
            :key="column.field"
            :class="[
              column.align ? `text-${column.align}` : 'text-start',
              column.sortable ? 'sortable-header' : '',
              getSortClass(column.field)
            ]"
            role="columnheader"
            :aria-sort="getAriaSortValue(column.field)"
            @click="column.sortable ? handleSort(column.field) : null"
            @keydown.enter="column.sortable ? handleSort(column.field) : null"
            :tabindex="column.sortable ? 0 : -1"
          >
            <div class="d-flex align-items-center justify-content-between">
              <span>{{ column.label }}</span>
              <i 
                v-if="column.sortable"
                class="sort-icon"
                :class="getSortIcon(column.field)"
              ></i>
            </div>
          </th>
        </tr>
      </thead>
      
      <tbody>
        <tr 
          v-for="(item, index) in tableData" 
          :key="item.id || index"
          :class="{ 
            'table-active': selectedItems.includes(item.id),
            'clickable-row': hasClickableColumns 
          }"
          @click="handleRowClick(item, index)"
          @keydown.enter="handleRowClick(item, index)"
          :tabindex="hasClickableColumns ? 0 : -1"
          role="row"
        >
          <td 
            v-for="column in columns" 
            :key="column.field"
            :class="[
              column.align ? `text-${column.align}` : 'text-start',
              column.clickable ? 'clickable-cell' : ''
            ]"
            role="gridcell"
          >
            <!-- Formatted Cell Content -->
            <component 
              :is="getCellComponent(column.type)"
              :value="getFieldValue(item, column.field)"
              :format="column.format"
              :column="column"
              :item="item"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  
  <!-- Empty State -->
  <div v-else-if="!isLoading && !hasError" class="empty-state text-center py-5">
    <div class="mb-4">
      <i class="bi bi-inbox display-1 text-muted"></i>
    </div>
    <h5 class="text-muted">No items found</h5>
    <p class="text-muted mb-4">
      {{ hasActiveFilters ? 'Try adjusting your filters or search terms.' : 'Get started by adding your first item.' }}
    </p>
    <button v-if="!hasActiveFilters" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i>
      Add First Item
    </button>
  </div>
  
  <!-- Error State -->
  <div v-else-if="hasError" class="error-state text-center py-5">
    <div class="mb-4">
      <i class="bi bi-exclamation-triangle display-1 text-danger"></i>
    </div>
    <h5 class="text-danger">Failed to load data</h5>
    <p class="text-muted mb-4">Something went wrong while loading the data.</p>
    <button class="btn btn-outline-primary" @click="retryDataLoad">
      <i class="bi bi-arrow-clockwise me-1"></i>
      Try Again
    </button>
  </div>
</div>
```

**Bootstrap Classes Used:**
- `table table-hover table-striped`: Modern table styling
- `table-responsive`: Horizontal scrolling on mobile
- `table-light sticky-top`: Sticky header with light background
- `placeholder-glow`, `placeholder`: Loading skeleton animation
- `table-active`: Selected row highlighting
- `clickable-row`, `clickable-cell`: Interactive row/cell styling
- `empty-state`, `error-state`: State-specific layouts
- `display-1`: Large icon display
- `btn btn-primary`, `btn btn-outline-primary`: Action buttons

## Implementation Rules & DDD Principles

### Domain-Driven Design (DDD)
- **List Domain**: Component encapsulates all table/list-related business logic
- **Data Presentation**: Clear separation between data fetching and presentation
- **Sort Management**: Self-contained sorting logic with URL state persistence

### Technical Requirements
- **API Service**: MUST use `useApiService()` from `frontend/app/utils/api.ts`
- **Notifications**: MUST use `useNotifyService()` from `frontend/app/utils/notify.ts`
- **TypeScript**: Strict typing with proper interfaces and type guards
- **Accessibility**: WCAG 2.1 AA compliance (ARIA labels, keyboard navigation)
- **Performance**: Virtual scrolling for large datasets, efficient re-rendering

### Error Handling Strategy

```typescript
// Error Handling Pattern
const handleDataError = (error: ApiError) => {
  console.error('[List] API error:', error)
  
  hasError.value = true
  isLoading.value = false
  
  notifyService.error(
    'Failed to load data. Please try again.',
    'Data Loading Error'
  )
  
  // Offer retry option
  notifyService.info('Click "Try Again" to reload the data')
}
```

## Testing Requirements (TDD)

### Unit Tests
```typescript
describe('List Component TDD Tests', () => {
  beforeEach(() => {
    vi.mocked(useApiService).mockReturnValue(mockApiService)
    vi.mocked(useNotifyService).mockReturnValue(mockNotifyService)
  })

  describe('Data Loading', () => {
    it('should fetch data on component mount')
    it('should refetch data when props change')
    it('should handle loading state during data fetch')
    it('should display data in table format')
  })

  describe('Sorting', () => {
    it('should handle column sort when header clicked')
    it('should toggle sort direction on repeated clicks')
    it('should update URL with sort parameters')
    it('should display sort indicators correctly')
  })

  describe('Table Rendering', () => {
    it('should render columns based on API configuration')
    it('should handle different column types (text, number, date)')
    it('should apply column formatting correctly')
    it('should make specified columns clickable')
  })

  describe('States', () => {
    it('should show loading skeleton during data fetch')
    it('should display empty state when no data')
    it('should show error state on API failure')
    it('should handle row selection correctly')
  })

  describe('Accessibility', () => {
    it('should have proper ARIA labels for table')
    it('should support keyboard navigation')
    it('should announce sort changes to screen readers')
  })
})
```

## Implementation Example

```typescript
// frontend/app/components/Resource/List.vue
<template>
  <div class="list-container">
    <!-- Loading State with Skeleton -->
    <div v-if="isLoading" class="table-loading">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th v-for="n in 5" :key="n" class="placeholder-glow">
                <span class="placeholder col-8"></span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="n in 10" :key="n">
              <td v-for="m in 5" :key="m" class="placeholder-glow">
                <span class="placeholder col-12"></span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Data Table -->
    <div v-else-if="tableData.length > 0" class="table-responsive">
      <table 
        class="table table-hover table-striped"
        role="table"
        :aria-label="`${resource} data table`"
      >
        <thead class="table-light sticky-top">
          <tr role="row">
            <th 
              v-for="column in columns" 
              :key="column.field"
              :class="getHeaderClass(column)"
              role="columnheader"
              :aria-sort="getAriaSortValue(column.field)"
              @click="column.sortable ? handleSort(column.field) : null"
              @keydown.enter="column.sortable ? handleSort(column.field) : null"
              :tabindex="column.sortable ? 0 : -1"
            >
              <div class="d-flex align-items-center justify-content-between">
                <span>{{ column.label }}</span>
                <i 
                  v-if="column.sortable"
                  class="sort-icon"
                  :class="getSortIcon(column.field)"
                ></i>
              </div>
            </th>
          </tr>
        </thead>
        
        <tbody>
          <tr 
            v-for="(item, index) in tableData" 
            :key="item.id || index"
            :class="getRowClass(item)"
            @click="handleRowClick(item, index)"
            @keydown.enter="handleRowClick(item, index)"
            :tabindex="hasClickableColumns ? 0 : -1"
            role="row"
          >
            <td 
              v-for="column in columns" 
              :key="column.field"
              :class="getCellClass(column)"
              role="gridcell"
            >
              <CellRenderer 
                :value="getFieldValue(item, column.field)"
                :column="column"
                :item="item"
                @cell-click="handleCellClick"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Empty State -->
    <div v-else-if="!hasError" class="empty-state text-center py-5">
      <div class="mb-4">
        <i class="bi bi-inbox display-1 text-muted"></i>
      </div>
      <h5 class="text-muted">No {{ resource }} found</h5>
      <p class="text-muted mb-4">
        {{ getEmptyStateMessage() }}
      </p>
      <button 
        v-if="!hasActiveFilters" 
        class="btn btn-primary"
        @click="handleCreateNew"
      >
        <i class="bi bi-plus-lg me-1"></i>
        Add First {{ getSingularResource() }}
      </button>
    </div>
    
    <!-- Error State -->
    <div v-else class="error-state text-center py-5">
      <div class="mb-4">
        <i class="bi bi-exclamation-triangle display-1 text-danger"></i>
      </div>
      <h5 class="text-danger">Failed to load {{ resource }}</h5>
      <p class="text-muted mb-4">Something went wrong while loading the data.</p>
      <button class="btn btn-outline-primary" @click="retryDataLoad">
        <i class="bi bi-arrow-clockwise me-1"></i>
        Try Again
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'
import CellRenderer from './CellRenderer.vue'

interface Column {
  field: string
  label: string
  sortable?: boolean
  clickable?: boolean
  type?: string
  format?: string
  align?: 'left' | 'center' | 'right'
}

interface Props {
  resource: string
  filters?: Record<string, any>
  search?: string
  page?: number
}

const props = withDefaults(defineProps<Props>(), {
  filters: () => ({}),
  search: '',
  page: 1
})

const emit = defineEmits<{
  itemSelected: [{ item: any; index: number }]
  sortChanged: [{ column: string; direction: 'asc' | 'desc' }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()
const route = useRoute()
const router = useRouter()

// Reactive state
const tableData = ref<any[]>([])
const columns = ref<Column[]>([])
const sortConfig = ref<{ column: string; direction: 'asc' | 'desc' } | null>(null)
const isLoading = ref(false)
const hasError = ref(false)
const selectedItems = ref<any[]>([])

// Computed properties
const hasClickableColumns = computed(() => 
  columns.value.some(col => col.clickable)
)

const hasActiveFilters = computed(() => 
  Object.values(props.filters).some(value => value) || props.search
)

// Initialize component
onMounted(() => {
  initializeSort()
  fetchData()
})

const initializeSort = () => {
  const sortColumn = route.query.sort as string
  const sortDir = route.query.dir as string
  
  if (sortColumn && sortDir) {
    sortConfig.value = {
      column: sortColumn,
      direction: sortDir as 'asc' | 'desc'
    }
  }
}

const fetchData = async () => {
  try {
    isLoading.value = true
    hasError.value = false
    
    const params: any = {
      page: props.page,
      ...props.filters
    }
    
    if (props.search) {
      params.search = props.search
    }
    
    if (sortConfig.value) {
      params.sort = sortConfig.value.column
      params.dir = sortConfig.value.direction
    }
    
    const response = await apiService.fetch(props.resource, params)
    
    tableData.value = response.data || []
    columns.value = response.columns || []
    
    if (response.sort) {
      sortConfig.value = response.sort
    }
    
  } catch (error) {
    handleDataError(error)
  } finally {
    isLoading.value = false
  }
}

const handleSort = (columnField: string) => {
  const currentSort = sortConfig.value
  let newDirection: 'asc' | 'desc' = 'asc'
  
  if (currentSort?.column === columnField) {
    newDirection = currentSort.direction === 'asc' ? 'desc' : 'asc'
  }
  
  sortConfig.value = {
    column: columnField,
    direction: newDirection
  }
  
  updateSortUrl()
  fetchData()
  
  emit('sortChanged', {
    column: columnField,
    direction: newDirection
  })
}

const handleRowClick = (item: any, index: number) => {
  if (hasClickableColumns.value) {
    emit('itemSelected', { item, index })
  }
}

const updateSortUrl = () => {
  if (sortConfig.value) {
    const query = { 
      ...route.query,
      sort: sortConfig.value.column,
      dir: sortConfig.value.direction
    }
    router.replace({ query })
  }
}

// Helper methods
const getHeaderClass = (column: Column) => [
  column.align ? `text-${column.align}` : 'text-start',
  column.sortable ? 'sortable-header cursor-pointer' : '',
  getSortClass(column.field)
]

const getRowClass = (item: any) => ({
  'table-active': selectedItems.value.includes(item.id),
  'clickable-row cursor-pointer': hasClickableColumns.value
})

const getCellClass = (column: Column) => [
  column.align ? `text-${column.align}` : 'text-start',
  column.clickable ? 'clickable-cell' : ''
]

const getSortClass = (field: string) => {
  if (sortConfig.value?.column === field) {
    return sortConfig.value.direction === 'asc' ? 'sorted-asc' : 'sorted-desc'
  }
  return ''
}

const getSortIcon = (field: string) => {
  if (sortConfig.value?.column === field) {
    return sortConfig.value.direction === 'asc' 
      ? 'bi bi-sort-up text-primary' 
      : 'bi bi-sort-down text-primary'
  }
  return 'bi bi-sort-numeric-down text-muted'
}

const getAriaSortValue = (field: string) => {
  if (sortConfig.value?.column === field) {
    return sortConfig.value.direction === 'asc' ? 'ascending' : 'descending'
  }
  return 'none'
}

const getFieldValue = (item: any, field: string) => {
  return field.split('.').reduce((obj, key) => obj?.[key], item)
}

const retryDataLoad = () => {
  fetchData()
}

// Watch for prop changes
watch(() => [props.filters, props.search, props.page], fetchData, { deep: true })

// Error handling
const handleDataError = (error: any) => {
  console.error('[List] API error:', error)
  hasError.value = true
  notifyService.error('Failed to load data. Please try again.', 'Data Loading Error')
}
</script>

<style scoped>
.sortable-header {
  user-select: none;
}

.clickable-row:hover {
  background-color: var(--bs-light);
}

.sort-icon {
  font-size: 0.8rem;
}

.empty-state,
.error-state {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
</style>
```

---

## API Integration Reference

**Endpoint Used:** `GET /api/v1/{resource}?page={page}&sort={column}&dir={direction}`

**Response Structure:** Uses `data`, `columns`, and `sort` from `design/api/index.md`

**Error Handling:** All errors handled via `frontend/app/utils/notify.ts`

**URL State:** Automatically syncs sort parameters with route query for bookmarking
