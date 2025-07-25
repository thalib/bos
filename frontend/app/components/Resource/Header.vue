<template>
  <div class="header-container">
    <!-- Header Title Row -->
    <div class="row align-items-center mb-3">
      <div class="col">
        <div class="d-flex align-items-center gap-2">
          <h2 class="mb-0">{{ title }}</h2>
          <span v-if="itemCount" class="badge bg-secondary">{{ itemCount }}</span>
          <div v-if="loading" class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      
      <!-- Desktop Actions -->
      <div class="col-auto d-none d-lg-block">
        <div class="btn-group" role="group" aria-label="Page actions">
          <button 
            class="btn btn-primary"
            @click="handleCreateAction"
            :disabled="loading"
          >
            <i class="bi bi-plus-lg me-1"></i>
            Create {{ getSingularResource() }}
          </button>
          
          <div class="btn-group" role="group">
            <button
              type="button"
              class="btn btn-outline-secondary dropdown-toggle"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              :disabled="loading"
            >
              Actions
            </button>
            <ul class="dropdown-menu">
              <li>
                <button class="dropdown-item" @click="handleExportAction">
                  <i class="bi bi-download me-2"></i>
                  Export {{ title }}
                </button>
              </li>
              <li>
                <button class="dropdown-item" @click="handleImportAction">
                  <i class="bi bi-upload me-2"></i>
                  Import {{ title }}
                </button>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <button class="dropdown-item" @click="handleRefreshAction">
                  <i class="bi bi-arrow-clockwise me-2"></i>
                  Refresh Data
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Search and Filter Row -->
    <div class="row g-3 mb-3">
      <div class="col-lg-6">
        <slot name="search" :loading="loading">
          <div class="text-muted small">Search component not provided</div>
        </slot>
      </div>
      
      <div class="col-lg-6">
        <slot name="filters" :loading="loading">
          <div class="text-muted small">Filter component not provided</div>
        </slot>
      </div>
    </div>
    
    <!-- Mobile Action Bar (visible on mobile only) -->
    <div class="d-lg-none mb-3">
      <div class="row g-2">
        <div class="col-6">
          <button class="btn btn-primary w-100" @click="handleCreateAction" :disabled="loading">
            <i class="bi bi-plus-lg"></i>
            Create
          </button>
        </div>
        <div class="col-6">
          <button 
            class="btn btn-outline-secondary w-100"
            @click="showMobileActions = !showMobileActions"
            :disabled="loading"
          >
            <i class="bi bi-three-dots"></i>
            More
          </button>
        </div>
      </div>
      
      <!-- Mobile Actions Collapse -->
      <div v-if="showMobileActions" class="mt-2">
        <div class="list-group">
          <button 
            class="list-group-item list-group-item-action"
            @click="handleExportAction"
            :disabled="loading"
          >
            <i class="bi bi-download me-2"></i>
            Export Data
          </button>
          <button 
            class="list-group-item list-group-item-action"
            @click="handleImportAction"
            :disabled="loading"
          >
            <i class="bi bi-upload me-2"></i>
            Import Data
          </button>
          <button 
            class="list-group-item list-group-item-action"
            @click="handleRefreshAction"
            :disabled="loading"
          >
            <i class="bi bi-arrow-clockwise me-2"></i>
            Refresh
          </button>
        </div>
      </div>
    </div>
    
    <!-- Status Bar -->
    <div v-if="statusMessage" class="alert alert-info alert-dismissible" role="alert">
      <i class="bi bi-info-circle me-2"></i>
      {{ statusMessage }}
      <button 
        type="button" 
        class="btn-close" 
        @click="clearStatus"
        aria-label="Close"
      ></button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface Props {
  title: string
  resource: string
  loading?: boolean
  itemCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  'action-triggered': [{ action: string; data?: any }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()

// Reactive state
const showMobileActions = ref(false)
const statusMessage = ref('')
const isActionLoading = ref(false)

// Computed properties
const getSingularResource = () => {
  return props.resource.endsWith('s') ? props.resource.slice(0, -1) : props.resource
}

// Action handlers
const handleCreateAction = () => {
  emit('action-triggered', { action: 'create' })
}

const handleExportAction = async () => {
  try {
    isActionLoading.value = true
    
    // Call export API
    const response = await apiService.request(`/api/v1/${props.resource}/export`, {
      method: 'GET',
      responseType: 'blob'
    })
    
    // Create download link
    if (response.data) {
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `${props.resource}_export.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
      notifyService.success('Export completed successfully')
    }
    
    emit('action-triggered', { action: 'export' })
    
  } catch (error) {
    handleActionError('export', error)
  } finally {
    isActionLoading.value = false
  }
}

const handleImportAction = () => {
  // Create file input for import
  const fileInput = document.createElement('input')
  fileInput.type = 'file'
  fileInput.accept = '.csv,.xlsx,.json'
  
  fileInput.onchange = async (event: any) => {
    const file = event.target.files[0]
    if (file) {
      try {
        isActionLoading.value = true
        
        const formData = new FormData()
        formData.append('file', file)
        
        const response = await apiService.request(`/api/v1/${props.resource}/import`, {
          method: 'POST',
          body: formData,
          headers: {} // Let browser set content-type for FormData
        })
        
        if (response.success) {
          notifyService.success('Import completed successfully')
        }
        
        emit('action-triggered', { action: 'import', data: { file: file.name } })
        
      } catch (error) {
        handleActionError('import', error)
      } finally {
        isActionLoading.value = false
      }
    }
  }
  
  fileInput.click()
}

const handleRefreshAction = () => {
  emit('action-triggered', { action: 'refresh' })
}

const clearStatus = () => {
  statusMessage.value = ''
}

// Error handling
const handleActionError = (action: string, error: any) => {
  console.error(`[Header] ${action} action failed:`, error)
  
  notifyService.error(
    `Failed to ${action.toLowerCase()}. Please try again.`,
    `${action.charAt(0).toUpperCase() + action.slice(1)} Error`
  )
  
  isActionLoading.value = false
}

// Expose methods for testing
defineExpose({
  handleCreateAction,
  handleExportAction,
  handleImportAction,
  handleRefreshAction,
  clearStatus,
  loading: computed(() => props.loading || isActionLoading.value)
})
</script>