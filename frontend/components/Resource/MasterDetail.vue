<template>
  <MasterDetailContainer
    ref="containerRef"
    :selected-item="selectedItem"
    :show-detail-panel="!!(selectedItem || showCreateForm)"
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

    <!-- Detail Content Slot -->
    <template #detail-content="{ closePanel }">
      <ResourceForm
        :ref="showCreateForm ? 'createFormRef' : 'formRef'"
        :resource="resource"
        :id="selectedItem?.id"
        :api-version="apiVersion"
        :resource-title="resourceTitle"
        :show-header="false"
        @success="handleFormSuccess"
        @error="$emit('error', $event)"
        @cancel="closePanel"
        @deleted="handleItemDeleted"
      />
    </template>
  </MasterDetailContainer>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import ResourceList from './List.vue'
import ResourceForm from './Form.vue'
import MasterDetailContainer from './MasterDetailContainer.vue'
import type { Column, PaginationMeta } from '../../types'

interface Props {
  resource: string
  items: any[]
  loading?: boolean
  error?: string | null
  columns?: Column[]
  resourceTitle?: string
  apiVersion?: string
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
  (e: 'success', data: any): void
  (e: 'updateItemInMemory', data: any): void
  (e: 'error', error: any): void
  (e: 'sort', column: Column): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  error: null,
  columns: () => [],
  resourceTitle: undefined,
  apiVersion: undefined,
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
const showCreateForm = ref(false)
const formRef = ref<InstanceType<typeof ResourceForm> | null>(null)
const createFormRef = ref<InstanceType<typeof ResourceForm> | null>(null)
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
    : `Create your first ${resourceTitle.value.toLowerCase()} to get started`
)

const panelTitle = computed(() => {
  if (showCreateForm.value) return `Create ${resourceTitle.value}`
  if (selectedItem.value) {
    return selectedItem.value.name || `${resourceTitle.value} #${selectedItem.value.id}`
  }
  return ''
})

const masterColumns = computed(() => {
  if (!selectedItem.value && !showCreateForm.value) return props.columns
  
  const essentialColumns = props.columns.filter(col => 
    ['name', 'email', 'title'].includes(col.key)
  )
  
  return essentialColumns.length > 0 ? essentialColumns.slice(0, 1) : props.columns.slice(0, 1)
})

// Methods
const handleItemClick = (item: any) => {
  selectedItem.value = item
  showCreateForm.value = false
  emit('itemClick', item)
}

const handleCreate = () => {
  selectedItem.value = null
  showCreateForm.value = true
  emit('create')
}

const closePanel = () => {
  selectedItem.value = null
  showCreateForm.value = false
}

const handleFormSuccess = async (data: any) => {
  if (showCreateForm.value) {
    showCreateForm.value = false
    selectedItem.value = data
    emit('success', data)
  } else if (selectedItem.value?.id && data) {
    Object.assign(selectedItem.value, data)
    emit('updateItemInMemory', data)
    
    await nextTick()
    formRef.value?.loadForm()
  } else {
    emit('success', data)
  }
}

const handleItemDeleted = () => {
  selectedItem.value = null
  emit('refresh')
}

const getSelectedItemId = () => selectedItem.value?.id || null

const directRestoreSelection = async (itemId: any) => {
  if (!itemId || !props.items?.length) return
  
  const itemToRestore = props.items.find((item: any) => item.id == itemId)
  if (itemToRestore) {
    selectedItem.value = itemToRestore
    await nextTick()
    formRef.value?.loadForm()
  }
}

// Expose methods
defineExpose({
  closeDetail: closePanel,
  closeCreate: closePanel,
  handleCreate,
  getSelectedItemId,
  directRestoreSelection,
  selectItem: (item: any) => { selectedItem.value = item }
})
</script>
