<template>
  <div class="h-100" style="min-height: 600px;">
    <!-- Results Summary Slot -->
    <slot name="results-summary"></slot>
    
    <!-- Master-Detail Layout -->
    <div class="row g-0 h-100">
      <!-- Master Panel -->
      <div
        :class="[
          showDetailPanel ? 'col-md-4' : 'col-12',
          'd-flex flex-column h-100'
        ]"
        style="transition: all 0.3s ease;"
      >
        <div class="h-100 overflow-auto">
          <slot 
            name="master-content"
            :selected-item="selectedItem"
            :show-detail-panel="showDetailPanel"
            :handle-item-click="handleItemClick"
            :handle-create="handleCreate"
          ></slot>
        </div>
      </div>

      <!-- Detail Panel -->
      <div 
        v-if="showDetailPanel" 
        class="col-md-8 h-100"
      >
        <div class="card h-100 ms-md-2 mt-2 mt-md-0 border-0 shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 flex-grow-1">
              <slot 
                name="panel-title"
                :selected-item="selectedItem"
                :show-detail-panel="showDetailPanel"
              >
                {{ computedPanelTitle }}
              </slot>
            </h5>
            
            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-2 me-2">
              <!-- Edit Button -->
              <button 
                type="button"
                class="btn btn-outline-primary btn-sm"
                @click="handleEdit"
                :title="computedEditLabel"
                :aria-label="computedEditLabel"
              >
                <i class="bi bi-pencil"></i>
                <span class="d-none d-md-inline ms-1">Edit</span>
              </button>
              
              <!-- PDF/Print Dropdown -->
              <div class="dropdown">
                <button 
                  type="button"
                  class="btn btn-outline-secondary btn-sm dropdown-toggle"
                  data-bs-toggle="dropdown"
                  :title="computedExportLabel"
                  :aria-label="computedExportLabel"
                  aria-expanded="false"
                >
                  <i class="bi bi-download"></i>
                  <span class="d-none d-md-inline ms-1">Export</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <button 
                      class="dropdown-item" 
                      @click="handleExportPdf"
                      aria-label="Export as PDF"
                      title="Export as PDF"
                    >
                      <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                    </button>
                  </li>
                  <li>
                    <button 
                      class="dropdown-item" 
                      @click="handleExportJpg"
                      aria-label="Export as JPG image"
                      title="Export as JPG image"
                    >
                      <i class="bi bi-image me-2"></i>JPG
                    </button>
                  </li>
                  <li>
                    <button 
                      class="dropdown-item" 
                      @click="handlePrint"
                      aria-label="Print document"
                      title="Print document"
                    >
                      <i class="bi bi-printer me-2"></i>Print
                    </button>
                  </li>
                  <li>
                    <button 
                      class="dropdown-item" 
                      @click="handleCopy"
                      aria-label="Copy to clipboard"
                      title="Copy to clipboard"
                    >
                      <i class="bi bi-clipboard me-2"></i>Copy
                    </button>
                  </li>
                </ul>
              </div>
            </div>
            
            <!-- Close Button -->
            <button 
              type="button"
              class="btn btn-outline-secondary btn-sm" 
              @click="closePanel"
              :title="computedCloseLabel"
              :aria-label="computedCloseLabel"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="card-body overflow-auto">
            <slot 
              name="detail-content"
              :selected-item="selectedItem"
              :show-detail-panel="showDetailPanel"
              :close-panel="closePanel"
            ></slot>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useDocumentExport } from '~/composables/useDocumentExport'

// Get composable functions
const { exportAsJpg, copyToClipboard, printDocument } = useDocumentExport()

interface Props {
  selectedItem?: any | null
  showDetailPanel?: boolean
  panelTitle?: string
  resourceTitle?: string
}

interface Emits {
  (e: 'itemClick', item: any): void
  (e: 'create'): void
  (e: 'closePanel'): void
  (e: 'update:selectedItem', value: any | null): void
  (e: 'update:showDetailPanel', value: boolean): void
  (e: 'edit'): void
  (e: 'export:pdf'): void
  (e: 'export:jpg'): void
  (e: 'print'): void
  (e: 'copy'): void
}

const props = withDefaults(defineProps<Props>(), {
  selectedItem: null,
  showDetailPanel: false,
  panelTitle: undefined,
  resourceTitle: undefined
})

const emit = defineEmits<Emits>()

// Computed Properties
const computedPanelTitle = computed(() => {
  if (props.panelTitle) return props.panelTitle
  
  if (props.selectedItem) {
    const itemName = props.selectedItem.name || props.selectedItem.title || `#${props.selectedItem.id}`
    return props.resourceTitle ? `${props.resourceTitle} - ${itemName}` : itemName
  }
  
  return props.resourceTitle || 'Details'
})

// Computed Accessibility Labels
const computedEditLabel = computed(() => {
  const resourceName = props.resourceTitle || 'record'
  return `Edit ${resourceName.toLowerCase()}`
})

const computedExportLabel = computed(() => {
  const resourceName = props.resourceTitle || 'content'
  return `Export and print ${resourceName.toLowerCase()} options`
})

const computedCloseLabel = computed(() => {
  const panelType = props.resourceTitle || 'detail'
  return `Close ${panelType.toLowerCase()} panel`
})

// Methods
const handleItemClick = (item: any) => {
  emit('update:selectedItem', item)
  emit('update:showDetailPanel', true)
  emit('itemClick', item)
}

const handleCreate = () => {
  emit('update:selectedItem', null)
  emit('update:showDetailPanel', true)
  emit('create')
}

const closePanel = () => {
  emit('update:selectedItem', null)
  emit('update:showDetailPanel', false)
  emit('closePanel')
}

const selectItem = (item: any) => {
  emit('update:selectedItem', item)
  emit('update:showDetailPanel', true)
}

const getSelectedItem = () => {
  return props.selectedItem
}

// Action Button Handlers
const handleEdit = () => {
  emit('edit')
  alert('Feature not implemented')
}

const handleExportPdf = () => {
  emit('export:pdf')
  alert('Feature not implemented')
}

const handleExportJpg = async () => {
  emit('export:jpg')
  try {
    const documentElement = document.querySelector('.document-viewer') as HTMLElement
    if (!documentElement) {
      throw new Error('Document viewer not found. Please ensure the document is displayed.')
    }
    
    await exportAsJpg(documentElement)
    // Success feedback will be handled by the composable
  } catch (error) {
    console.error('Failed to export as JPG:', error)
    const errorMessage = error instanceof Error ? error.message : 'Failed to export document as JPG'
    alert(`Export Error: ${errorMessage}`)
  }
}

const handlePrint = () => {
  emit('print')
  try {
    const documentElement = document.querySelector('.document-viewer') as HTMLElement
    if (!documentElement) {
      throw new Error('Document viewer not found. Please ensure the document is displayed.')
    }
    
    printDocument(documentElement)
  } catch (error) {
    console.error('Failed to print document:', error)
    const errorMessage = error instanceof Error ? error.message : 'Failed to open print dialog'
    alert(`Print Error: ${errorMessage}`)
  }
}

const handleCopy = async () => {
  emit('copy')
  try {
    const documentElement = document.querySelector('.document-viewer') as HTMLElement
    if (!documentElement) {
      throw new Error('Document viewer not found. Please ensure the document is displayed.')
    }
    
    await copyToClipboard(documentElement)
    alert('Document copied to clipboard successfully!')
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
    const errorMessage = error instanceof Error ? error.message : 'Failed to copy document to clipboard'
    alert(`Copy Error: ${errorMessage}`)
  }
}

// Watch for external changes to selectedItem
watch(() => props.selectedItem, (newValue) => {
  if (newValue && !props.showDetailPanel) {
    emit('update:showDetailPanel', true)
  }
}, { immediate: false })

// Watch for external changes to showDetailPanel
watch(() => props.showDetailPanel, (newValue) => {
  if (!newValue) {
    emit('update:selectedItem', null)
  }
}, { immediate: false })

// Expose Methods
defineExpose({
  closePanel,
  handleCreate,
  selectItem,
  getSelectedItem
})
</script>

<style scoped>
/* Responsive adjustments for mobile */
@media (max-width: 768px) {
  .ms-md-2 {
    margin-left: 0 !important;
  }
  
  .mt-md-0 {
    margin-top: 1rem !important;
  }
}

/* Smooth transitions for panel animations */
.col-md-4,
.col-12 {
  transition: all 0.3s ease;
}
</style>
