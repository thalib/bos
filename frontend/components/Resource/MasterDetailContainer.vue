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
            <h5 class="mb-0">
              <slot 
                name="panel-title"
                :selected-item="selectedItem"
                :show-detail-panel="showDetailPanel"
              >
                {{ computedPanelTitle }}
              </slot>
            </h5>
            <button 
              type="button"
              class="btn btn-outline-secondary btn-sm" 
              @click="closePanel"
              title="Close"
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
