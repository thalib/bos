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
              <!-- Simple cell content - can be enhanced with CellRenderer later -->
              {{ getFieldValue(item, column.field) }}
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
      <h5 class="text-muted">No items found</h5>
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
      <h5 class="text-danger">Failed to load data</h5>
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
  'item-selected': [{ item: any; index: number }]
  'sort-changed': [{ column: string; direction: 'asc' | 'desc' }]
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
  
  if (sortColumn && sortDir && ['asc', 'desc'].includes(sortDir)) {
    sortConfig.value = {
      column: sortColumn,
      direction: sortDir as 'asc' | 'desc'
    }
  }
}

const fetchData = async (preserveSort = false) => {
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
    
    if (response.data) {
      // Handle paginated response format
      if (Array.isArray(response.data.data)) {
        tableData.value = response.data.data
      } else if (Array.isArray(response.data)) {
        tableData.value = response.data
      } else {
        tableData.value = []
      }
    } else {
      tableData.value = []
    }
    
    // Set columns from response or create basic columns from data
    if (response.columns) {
      columns.value = response.columns
    } else if (tableData.value.length > 0) {
      // Create basic columns from first data item
      const firstItem = tableData.value[0]
      columns.value = Object.keys(firstItem).map(key => ({
        field: key,
        label: key.charAt(0).toUpperCase() + key.slice(1),
        sortable: true,
        clickable: key === 'name' || key === 'title'
      }))
    }
    
    // Only update sort config from response if not preserving user sort
    if (response.sort && !preserveSort) {
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
  fetchData(true) // Preserve user's sort choice
  
  emit('sort-changed', {
    column: columnField,
    direction: newDirection
  })
}

const handleRowClick = (item: any, index: number) => {
  if (hasClickableColumns.value) {
    emit('item-selected', { item, index })
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

const getEmptyStateMessage = () => {
  if (hasActiveFilters.value) {
    return 'Try adjusting your filters or search terms.'
  }
  return 'Get started by adding your first item.'
}

const getSingularResource = () => {
  return props.resource.endsWith('s') ? props.resource.slice(0, -1) : props.resource
}

const handleCreateNew = () => {
  // Emit event or navigate to create page
  router.push(`/list/${props.resource}/create`)
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

.cursor-pointer {
  cursor: pointer;
}
</style>