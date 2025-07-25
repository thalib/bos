<template>
  <div class="master-detail-container">
    <div class="row g-0 h-100">
      <!-- Master Panel -->
      <div 
        class="col-lg-6 border-end"
        :class="{ 'col-12': !selectedItem || isMobile }"
      >
        <div class="master-panel h-100">
          <List
            :resource="resource"
            @item-selected="handleItemSelection"
          />
        </div>
      </div>
      
      <!-- Detail Panel -->
      <div 
        v-if="selectedItem && (splitView || !isMobile)"
        class="col-lg-6"
      >
        <div class="detail-panel h-100">
          <!-- Form Mode -->
          <Form
            v-if="mode === 'form'"
            :resource="resource"
            :resource-id="selectedItem.id"
            @form-saved="handleFormSaved"
          />
          
          <!-- Document Mode -->
          <DocumentView
            v-else-if="mode === 'document'"
            :resource="resource"
            :document-id="selectedItem.id"
            @action-triggered="handleDocumentAction"
          />
        </div>
      </div>
    </div>
    
    <!-- Mobile Detail Modal -->
    <div 
      v-if="selectedItem && isMobile"
      class="modal fade show"
      style="display: block;"
      tabindex="-1"
      role="dialog"
      aria-labelledby="mobileDetailModalTitle"
      aria-hidden="false"
    >
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="mobileDetailModalTitle">
              {{ selectedItem.title || selectedItem.name || `${resource} #${selectedItem.id}` }}
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="clearSelection"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body p-0">
            <!-- Form Mode -->
            <Form
              v-if="mode === 'form'"
              :resource="resource"
              :resource-id="selectedItem.id"
              @form-saved="handleFormSaved"
            />
            
            <!-- Document Mode -->
            <DocumentView
              v-else-if="mode === 'document'"
              :resource="resource"
              :document-id="selectedItem.id"
              @action-triggered="handleDocumentAction"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="d-flex justify-content-center align-items-center h-100">
        <div class="text-center">
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="text-muted">Loading details...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface Props {
  resource: string
  mode: 'form' | 'document'
  initialSelection?: string | number | null
  splitView?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialSelection: null,
  splitView: true
})

const emit = defineEmits<{
  selectionChanged: [{ selectedItem: any }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()

// Reactive state
const selectedItem = ref<any>(null)
const isLoading = ref(false)
const isMobile = ref(false)

// Computed properties
const resourceTitle = computed(() => 
  props.resource.charAt(0).toUpperCase() + props.resource.slice(1)
)

// Initialize component
onMounted(() => {
  setupViewportDetection()
  
  // Set initial selection if provided
  if (props.initialSelection) {
    loadInitialSelection()
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', updateViewport)
})

// Viewport detection
const setupViewportDetection = () => {
  updateViewport()
  window.addEventListener('resize', updateViewport)
}

const updateViewport = () => {
  isMobile.value = window.innerWidth < 992 // Bootstrap lg breakpoint
}

// Selection management
const loadInitialSelection = async () => {
  if (!props.initialSelection) return
  
  try {
    isLoading.value = true
    const response = await apiService.get(props.resource, props.initialSelection)
    
    if (response.success && response.data) {
      selectedItem.value = response.data
      emit('selectionChanged', { selectedItem: response.data })
    }
  } catch (error) {
    console.error('[MasterDetail] Failed to load initial selection:', error)
    notifyService.error('Failed to load selected item')
  } finally {
    isLoading.value = false
  }
}

const handleItemSelection = (item: any) => {
  selectedItem.value = item
  emit('selectionChanged', { selectedItem: item })
}

const clearSelection = () => {
  selectedItem.value = null
  emit('selectionChanged', { selectedItem: null })
}

// Child component event handlers
const handleFormSaved = (payload: { data: any; mode: string }) => {
  notifyService.success(`${resourceTitle.value} saved successfully`)
  
  // Update selected item with saved data if it's the same item
  if (selectedItem.value && payload.data && selectedItem.value.id === payload.data.id) {
    selectedItem.value = { ...selectedItem.value, ...payload.data }
  }
  
  // Emit event for parent coordination
  emit('selectionChanged', { selectedItem: selectedItem.value })
}

const handleDocumentAction = (payload: { action: string; data?: any }) => {
  switch (payload.action) {
    case 'edit':
      notifyService.info('Edit action triggered')
      break
    case 'download':
      notifyService.success(`Document downloaded as ${payload.data?.format || 'file'}`)
      break
    case 'print':
      notifyService.info('Print dialog opened')
      break
    default:
      console.log('[MasterDetail] Document action:', payload.action, payload.data)
  }
}

// Watch for prop changes
watch(() => props.initialSelection, (newSelection) => {
  if (newSelection && newSelection !== selectedItem.value?.id) {
    loadInitialSelection()
  } else if (!newSelection) {
    clearSelection()
  }
})

// Expose methods for testing
defineExpose({
  handleItemSelection,
  clearSelection,
  updateViewport,
  isLoading,
  isMobile,
  selectedItem
})
</script>

<style scoped>
.master-detail-container {
  height: 100vh;
  display: flex;
  flex-direction: column;
  position: relative;
}

.master-panel,
.detail-panel {
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.8);
  z-index: 1000;
}

.modal {
  z-index: 1050;
}

.modal-body {
  height: calc(100vh - 120px);
  overflow: auto;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
  .master-detail-container .col-lg-6 {
    flex: 0 0 100%;
    max-width: 100%;
  }
}

/* Ensure proper height inheritance */
.master-detail-container .row,
.master-detail-container .col-lg-6 {
  height: 100%;
}
</style>