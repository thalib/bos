<template>
  <div class="document-viewer">
    <!-- Action Toolbar -->
    <div class="toolbar-container bg-light border-bottom">
      <div class="container-fluid">
        <div class="row align-items-center py-2">
          <!-- Document Info -->
          <div class="col-md-6">
            <div class="d-flex align-items-center gap-2">
              <h6 class="mb-0">{{ documentTitle }}</h6>
              <span class="badge bg-primary">{{ resourceType }}</span>
              <span v-if="documentData.status" :class="getStatusBadgeClass()">
                {{ documentData.status }}
              </span>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="col-md-6 text-end">
            <div class="btn-group" role="group" aria-label="Document actions">
              <!-- Edit Button -->
              <button 
                v-if="!readOnly"
                class="btn btn-outline-primary"
                @click="handleEditAction"
                :disabled="isLoading"
              >
                <i class="bi bi-pencil me-1"></i>
                Edit
              </button>
              
              <!-- Download Dropdown -->
              <div class="btn-group" role="group">
                <button
                  type="button"
                  class="btn btn-outline-secondary dropdown-toggle"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                  :disabled="isLoading"
                >
                  <i class="bi bi-download me-1"></i>
                  Download
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <button class="dropdown-item" @click="handleDownload('pdf')">
                      <i class="bi bi-file-pdf me-2"></i>
                      Download as PDF
                    </button>
                  </li>
                  <li>
                    <button class="dropdown-item" @click="handleDownload('png')">
                      <i class="bi bi-file-image me-2"></i>
                      Download as PNG
                    </button>
                  </li>
                  <li>
                    <button class="dropdown-item" @click="handleDownload('jpg')">
                      <i class="bi bi-file-image me-2"></i>
                      Download as JPG
                    </button>
                  </li>
                </ul>
              </div>
              
              <!-- Print Button -->
              <button 
                class="btn btn-outline-secondary"
                @click="handlePrint"
                :disabled="isLoading"
              >
                <i class="bi bi-printer me-1"></i>
                Print
              </button>
              
              <!-- More Actions -->
              <div class="btn-group" role="group">
                <button
                  type="button"
                  class="btn btn-outline-secondary dropdown-toggle"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <button class="dropdown-item" @click="handleCopy">
                      <i class="bi bi-clipboard me-2"></i>
                      Copy to Clipboard
                    </button>
                  </li>
                  <li>
                    <button class="dropdown-item" @click="handleShare">
                      <i class="bi bi-share me-2"></i>
                      Share Document
                    </button>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <button class="dropdown-item" @click="handleDuplicate">
                      <i class="bi bi-files me-2"></i>
                      Duplicate
                    </button>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- View Controls -->
    <div class="view-controls bg-light border-bottom">
      <div class="container-fluid">
        <div class="row align-items-center py-2">
          <!-- Zoom Controls -->
          <div class="col-md-4">
            <div class="d-flex align-items-center gap-2">
              <span class="text-muted small">Zoom:</span>
              <button 
                class="btn btn-sm btn-outline-secondary"
                @click="adjustZoom(-10)"
                :disabled="zoomLevel <= 50"
              >
                <i class="bi bi-zoom-out"></i>
              </button>
              <span class="badge bg-light text-dark">{{ zoomLevel }}%</span>
              <button 
                class="btn btn-sm btn-outline-secondary"
                @click="adjustZoom(10)"
                :disabled="zoomLevel >= 200"
              >
                <i class="bi bi-zoom-in"></i>
              </button>
              <button 
                class="btn btn-sm btn-outline-secondary"
                @click="resetZoom"
              >
                <i class="bi bi-arrows-fullscreen"></i>
              </button>
            </div>
          </div>
          
          <!-- Paper Size Selector -->
          <div class="col-md-4 text-center">
            <select 
              class="form-select form-select-sm"
              style="width: auto; display: inline-block;"
              v-model="paperSize"
              @change="updatePreview"
            >
              <option value="A4">A4 (210 × 297 mm)</option>
              <option value="A5">A5 (148 × 210 mm)</option>
              <option value="Letter">Letter (8.5 × 11 in)</option>
            </select>
          </div>
          
          <!-- View Mode -->
          <div class="col-md-4 text-end">
            <div class="btn-group btn-group-sm" role="group">
              <input 
                type="radio" 
                class="btn-check" 
                id="preview-mode"
                v-model="viewMode"
                value="preview"
              >
              <label class="btn btn-outline-secondary" for="preview-mode">
                <i class="bi bi-eye me-1"></i>
                Preview
              </label>
              
              <input 
                type="radio" 
                class="btn-check" 
                id="code-mode"
                v-model="viewMode"
                value="code"
              >
              <label class="btn btn-outline-secondary" for="code-mode">
                <i class="bi bi-code me-1"></i>
                Source
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Document Preview -->
    <div class="preview-container">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <div class="text-center py-5">
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading document...</span>
          </div>
          <p class="text-muted">Rendering document preview...</p>
        </div>
      </div>
      
      <!-- Preview Mode -->
      <div v-else-if="viewMode === 'preview'" class="preview-content p-4">
        <div class="document-canvas-container" :style="getCanvasContainerStyle()">
          <canvas
            ref="documentCanvas"
            :width="canvasWidth"
            :height="canvasHeight"
            class="document-canvas shadow mx-auto d-block"
          ></canvas>
        </div>
      </div>
      
      <!-- Source Code Mode -->
      <div v-else-if="viewMode === 'code'" class="source-content p-4">
        <pre class="bg-light p-3 rounded"><code>{{ documentSource }}</code></pre>
      </div>
      
      <!-- Error State -->
      <div v-else-if="hasError" class="error-state text-center py-5">
        <i class="bi bi-exclamation-triangle display-1 text-danger mb-3"></i>
        <h5 class="text-danger">Failed to load document</h5>
        <p class="text-muted mb-4">Unable to load or render the document.</p>
        <button class="btn btn-outline-primary" @click="retryLoad">
          <i class="bi bi-arrow-clockwise me-1"></i>
          Try Again
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface Props {
  resource: string
  documentId: string | number
  template?: string
  paperSize?: 'A4' | 'A5' | 'Letter'
  readOnly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  template: 'default',
  paperSize: 'A4',
  readOnly: false
})

const emit = defineEmits<{
  actionTriggered: [{ action: string; data?: any }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()

// Reactive state
const documentData = ref<any>({})
const documentTemplate = ref<any>({})
const documentSource = ref('')
const isLoading = ref(true)
const hasError = ref(false)
const zoomLevel = ref(100)
const viewMode = ref<'preview' | 'code'>('preview')
const paperSize = ref(props.paperSize)

// Canvas refs and dimensions
const documentCanvas = ref<HTMLCanvasElement>()
const canvasWidth = ref(794) // A4 width in pixels at 96 DPI
const canvasHeight = ref(1123) // A4 height in pixels at 96 DPI

// Computed properties
const documentTitle = computed(() => 
  documentData.value.title || documentData.value.name || `${props.resource} #${props.documentId}`
)

const resourceType = computed(() => 
  props.resource.charAt(0).toUpperCase() + props.resource.slice(1, -1)
)

// Initialize component
onMounted(() => {
  loadDocumentData()
  setupCanvasDimensions()
})

// Load document data and template
const loadDocumentData = async () => {
  try {
    isLoading.value = true
    hasError.value = false
    
    // Load document data
    const docResponse = await apiService.get(props.resource, props.documentId)
    documentData.value = docResponse.data || {}
    
    // Load document template
    try {
      const templateResponse = await apiService.get(
        `${props.resource}/template`, 
        props.template
      )
      documentTemplate.value = templateResponse.data || {}
    } catch (templateError) {
      // Template endpoint might not exist, use default
      documentTemplate.value = { name: 'default', content: '<html>Default template</html>' }
    }
    
    // Generate document source
    generateDocumentSource()
    
    // Render to canvas
    await nextTick()
    renderDocumentToCanvas()
    
  } catch (error) {
    handleDocumentError('Load', error)
  } finally {
    isLoading.value = false
  }
}

// Canvas rendering
const renderDocumentToCanvas = async () => {
  if (!documentCanvas.value || !documentData.value) return
  
  const canvas = documentCanvas.value
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  
  // Clear canvas
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, canvas.width, canvas.height)
  
  // Render document content
  try {
    // For demo purposes, render basic document info
    ctx.font = '24px Arial'
    ctx.fillStyle = '#000000'
    ctx.fillText(documentTitle.value, 50, 50)
    
    ctx.font = '16px Arial'
    ctx.fillText(`Type: ${resourceType.value}`, 50, 80)
    
    if (documentData.value.status) {
      ctx.fillText(`Status: ${documentData.value.status}`, 50, 110)
    }
    
    ctx.fillText('Document content would be rendered here', 50, 150)
    ctx.fillText('using the loaded template and data.', 50, 180)
    
    // Draw a border
    ctx.strokeStyle = '#dee2e6'
    ctx.lineWidth = 1
    ctx.strokeRect(20, 20, canvas.width - 40, canvas.height - 40)
    
  } catch (error) {
    console.error('[DocumentView] Canvas rendering failed:', error)
    notifyService.error('Failed to render document preview')
  }
}

// Document actions
const handleEditAction = () => {
  emit('actionTriggered', { 
    action: 'edit', 
    data: { documentId: props.documentId } 
  })
}

const handleDownload = async (format: 'pdf' | 'png' | 'jpg') => {
  try {
    notifyService.info(`Preparing ${format.toUpperCase()} download...`)
    
    let downloadUrl: string
    let filename: string
    
    if (format === 'pdf') {
      // Generate PDF from API endpoint
      const response = await apiService.request(`/api/v1/${props.resource}/${props.documentId}/pdf`, {
        responseType: 'blob'
      })
      downloadUrl = URL.createObjectURL(response.data)
      filename = `${documentTitle.value}.pdf`
    } else {
      // Convert canvas to image
      if (!documentCanvas.value) throw new Error('Canvas not available')
      
      const dataUrl = documentCanvas.value.toDataURL(`image/${format}`)
      downloadUrl = dataUrl
      filename = `${documentTitle.value}.${format}`
    }
    
    // Trigger download
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    if (format === 'pdf') {
      URL.revokeObjectURL(downloadUrl)
    }
    
    notifyService.success(`Document downloaded as ${format.toUpperCase()}`)
    emit('actionTriggered', { action: 'download', data: { format, filename } })
    
  } catch (error) {
    handleDocumentError('Download', error)
  }
}

const handlePrint = () => {
  if (documentCanvas.value) {
    const printWindow = window.open('', '_blank')
    if (printWindow) {
      const dataUrl = documentCanvas.value.toDataURL('image/png')
      printWindow.document.write(`
        <html>
          <head><title>${documentTitle.value}</title></head>
          <body style="margin:0; padding:20px;">
            <img src="${dataUrl}" style="max-width:100%; height:auto;" />
          </body>
        </html>
      `)
      printWindow.document.close()
      printWindow.print()
    }
  }
  
  emit('actionTriggered', { action: 'print' })
}

const handleCopy = async () => {
  try {
    if (documentCanvas.value) {
      const dataUrl = documentCanvas.value.toDataURL('image/png')
      
      // For modern browsers with Clipboard API
      if (navigator.clipboard && navigator.clipboard.write) {
        const response = await fetch(dataUrl)
        const blob = await response.blob()
        await navigator.clipboard.write([
          new ClipboardItem({ 'image/png': blob })
        ])
        notifyService.success('Document copied to clipboard')
      } else {
        // Fallback for older browsers
        notifyService.info('Copy to clipboard not supported in this browser')
      }
    }
  } catch (error) {
    notifyService.error('Failed to copy document to clipboard')
  }
  
  emit('actionTriggered', { action: 'copy' })
}

const handleShare = () => {
  notifyService.info('Share functionality would be implemented here')
  emit('actionTriggered', { action: 'share' })
}

const handleDuplicate = () => {
  notifyService.info('Duplicate functionality would be implemented here')
  emit('actionTriggered', { action: 'duplicate' })
}

// View controls
const adjustZoom = (delta: number) => {
  const newZoom = Math.max(50, Math.min(200, zoomLevel.value + delta))
  zoomLevel.value = newZoom
  updateCanvasScale()
}

const resetZoom = () => {
  zoomLevel.value = 100
  updateCanvasScale()
}

const updateCanvasScale = () => {
  if (documentCanvas.value) {
    const scale = zoomLevel.value / 100
    documentCanvas.value.style.transform = `scale(${scale})`
  }
}

const setupCanvasDimensions = () => {
  const dimensions = {
    'A4': { width: 794, height: 1123 },
    'A5': { width: 559, height: 794 },
    'Letter': { width: 816, height: 1056 }
  }
  
  const size = dimensions[paperSize.value]
  canvasWidth.value = size.width
  canvasHeight.value = size.height
}

const updatePreview = () => {
  setupCanvasDimensions()
  nextTick(() => renderDocumentToCanvas())
}

const getCanvasContainerStyle = () => ({
  transform: `scale(${zoomLevel.value / 100})`,
  transformOrigin: 'center top',
  transition: 'transform 0.2s ease'
})

const generateDocumentSource = () => {
  documentSource.value = JSON.stringify({
    data: documentData.value,
    template: documentTemplate.value
  }, null, 2)
}

const retryLoad = () => {
  loadDocumentData()
}

// Helper functions
const getStatusBadgeClass = () => {
  const status = documentData.value.status?.toLowerCase()
  const statusClasses = {
    'draft': 'badge bg-secondary',
    'pending': 'badge bg-warning',
    'approved': 'badge bg-success',
    'rejected': 'badge bg-danger'
  }
  return statusClasses[status] || 'badge bg-light text-dark'
}

// Error handling
const handleDocumentError = (operation: string, error: any) => {
  console.error(`[DocumentView] ${operation} failed:`, error)
  hasError.value = true
  notifyService.error(`Failed to ${operation.toLowerCase()} document.`)
}

// Watch for prop changes
watch(() => props.paperSize, (newSize) => {
  paperSize.value = newSize
  updatePreview()
})

watch(viewMode, () => {
  if (viewMode.value === 'preview') {
    nextTick(() => renderDocumentToCanvas())
  }
})

// Expose methods for testing
defineExpose({
  handleEditAction,
  handleDownload,
  handlePrint,
  handleCopy,
  adjustZoom,
  resetZoom,
  setupCanvasDimensions,
  isLoading,
  hasError,
  zoomLevel,
  viewMode,
  canvasWidth,
  canvasHeight,
  documentData
})
</script>

<style scoped>
.document-viewer {
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.preview-container {
  flex: 1;
  overflow: auto;
  background-color: #f8f9fa;
}

.document-canvas {
  background: white;
  transition: transform 0.2s ease;
}

.canvas-container {
  display: flex;
  justify-content: center;
  padding: 2rem;
}

.source-content {
  font-family: 'Courier New', monospace;
  font-size: 0.875rem;
}

.loading-state,
.error-state {
  padding: 2rem;
  text-align: center;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .preview-content {
    padding: 1rem !important;
  }
  
  .view-controls .row > div {
    margin-bottom: 0.5rem;
  }
  
  .view-controls .col-md-4 {
    flex: 0 0 100%;
    max-width: 100%;
    text-align: center !important;
  }
}
</style>