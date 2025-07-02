<template>
  <MasterDetailContainer
    ref="containerRef"
    :selected-item="selectedItem"
    :show-detail-panel="selectedItem !== null"
    :panel-title="panelTitle"
    :resource-title="resourceTitle"
    @item-click="handleItemClick"
    @create="handleCreate"
    @close-panel="closePanel"
  >
    <!-- Results Summary Slot -->
    <template #results-summary>
      <slot name="results-summary"></slot>
    </template>

    <!-- Master Content Slot -->
    <template #master-content="{ handleItemClick, handleCreate }">
      <div v-if="shouldShowEmptyState">
        <slot name="empty-state">
          <div class="d-flex flex-column align-items-center justify-content-center p-4 text-center">
            <i :class="emptyStateIcon" class="display-4 text-muted mb-3"></i>
            <h5 class="text-muted mb-2">{{ emptyStateTitle }}</h5>
            <p class="text-muted mb-0">{{ emptyStateMessage }}</p>
          </div>
        </slot>
      </div>
      
      <ResourceList
        v-else
        :columns="masterColumns"
        :items="items"
        :loading="loading"
        :error="error"
        :pagination="pagination"
        :show-pagination="false"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        @userClick="handleItemClick"
        @update:error="$emit('update:error', $event)"
        @sort="$emit('sort', $event)"
      />
    </template>

    <!-- Detail Content Slot - Document Placeholder -->
    <template #detail-content="{ closePanel }">
      <DocumentViewer />
    </template>
  </MasterDetailContainer>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import ResourceList from './List.vue'
import DocumentViewer from './DocumentViewer.vue'
import MasterDetailContainer from './MasterDetailContainer.vue'
import type { Column, PaginationMeta } from '../../types'

interface Props {
  resource: string
  items: any[]
  loading?: boolean
  error?: string | null
  columns?: Column[]
  resourceTitle?: string
  pagination?: PaginationMeta
  showPagination?: boolean
  searchQuery?: string
  hasSearchResults?: boolean
  hasNoSearchResults?: boolean
  sortField?: string
  sortDirection?: 'asc' | 'desc'
}

interface Emits {
  (e: 'itemClick', item: any): void
  (e: 'create'): void
  (e: 'update:error', value: string | null): void
  (e: 'refresh'): void
  (e: 'sort', column: Column): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: null,
  columns: () => [],
  resourceTitle: undefined,
  pagination: () => ({
    currentPage: 1,
    totalPages: 1,
    perPage: 20,
    total: 0,
    hasNextPage: false,
    hasPrevPage: false,
    nextPage: null,
    prevPage: null,
    from: 0,
    to: 0
  }),
  showPagination: true,
  searchQuery: '',
  hasSearchResults: false,
  hasNoSearchResults: false,
  sortField: '',
  sortDirection: 'asc'
})

const emit = defineEmits<Emits>()

// State
const selectedItem = ref<any | null>(null)
const containerRef = ref<InstanceType<typeof MasterDetailContainer> | null>(null)

// Computed properties
const resourceTitle = computed(() => 
  props.resourceTitle || 
  props.resource.charAt(0).toUpperCase() + props.resource.slice(1)
)

const shouldShowEmptyState = computed(() => 
  props.hasNoSearchResults || (props.items.length === 0 && !props.loading)
)

const emptyStateIcon = computed(() => 
  props.hasNoSearchResults ? 'bi bi-search' : 'bi bi-inbox'
)

const emptyStateTitle = computed(() => 
  props.hasNoSearchResults ? 'No Results Found' : `No ${resourceTitle.value.toLowerCase()} found`
)

const emptyStateMessage = computed(() => 
  props.hasNoSearchResults 
    ? 'Try adjusting your search terms or filters'
    : `Select a ${resourceTitle.value.toLowerCase()} to view its document`
)

const panelTitle = computed(() => {
  if (selectedItem.value) {
    const itemName = selectedItem.value.name || selectedItem.value.title || `#${selectedItem.value.id}`
    return `${resourceTitle.value} - ${itemName}`
  }
  return `${resourceTitle.value} Document`
})

const masterColumns = computed(() => {
  if (!selectedItem.value) return props.columns
  
  const essentialColumns = props.columns.filter(col => 
    ['name', 'email', 'title'].includes(col.key)
  )
  
  return essentialColumns.length > 0 ? essentialColumns.slice(0, 1) : props.columns.slice(0, 1)
})

// Methods
const handleItemClick = (item: any) => {
  selectedItem.value = item
  emit('itemClick', item)
}

const handleCreate = () => {
  // For document view, create doesn't make sense, so we just emit the event
  emit('create')
}

const closePanel = () => {
  selectedItem.value = null
}

const getSelectedItemId = () => selectedItem.value?.id || null

const selectItem = (item: any) => { 
  selectedItem.value = item 
}

// Expose methods for compatibility
defineExpose({
  closeDetail: closePanel,
  closeCreate: closePanel,
  handleCreate,
  getSelectedItemId,
  selectItem
})
</script>
