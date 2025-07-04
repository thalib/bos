<template>
  <div class="d-flex justify-content-center align-items-center h-100 p-3">
    <div 
      class="document-viewer bg-white border border-secondary-subtle shadow rounded-2 d-flex flex-column"
      style="width: 595px; height: 842px; max-width: 90vw; max-height: 80vh;"
    >
      <div class="flex-grow-1 overflow-auto position-relative">
        <!-- Loading State -->
        <div
          v-if="templateLoading"
          class="d-flex flex-column align-items-center justify-content-center h-100 p-4"
        >
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading template...</span>
          </div>
          <p class="text-muted mb-0">Loading document template...</p>
        </div>

        <!-- Error State -->
        <div
          v-else-if="error"
          class="d-flex flex-column align-items-center justify-content-center h-100 p-4 text-center"
        >
          <i class="bi bi-exclamation-triangle display-4 text-warning mb-3"></i>
          <h5 class="text-secondary mb-2">Template Error</h5>
          <p class="text-muted mb-3">{{ error.message }}</p>
          <button 
            class="btn btn-outline-primary btn-sm"
            @click="retryLoadTemplate"
            :disabled="templateLoading"
          >
            <i class="bi bi-arrow-clockwise me-1"></i>
            Try Again
          </button>
        </div>

        <!-- No Template Selected State -->
        <div
          v-else-if="!currentTemplateId"
          class="d-flex flex-column align-items-center justify-content-center h-100 p-4 text-center"
        >
          <i class="bi bi-file-earmark display-4 text-secondary mb-3"></i>
          <h5 class="text-secondary mb-2">No Template Selected</h5>
          <p class="text-muted mb-3">Choose a template from the dropdown above to begin</p>
        </div>

        <!-- Dynamic Template Component -->
        <div v-else-if="templateComponent" class="h-100">
          <Suspense>
            <template #default>
              <component
                :is="templateComponent"
                :document-data="processedDocumentData"
                :selected-item="selectedItem"
                @data-updated="emit('data-updated', $event)"
                @error="handleTemplateError"
              />
            </template>
            <template #fallback>
              <div class="d-flex flex-column align-items-center justify-content-center h-100 p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                  <span class="visually-hidden">Loading component...</span>
                </div>
                <p class="text-muted mb-0">Rendering template...</p>
              </div>
            </template>
          </Suspense>
        </div>

        <!-- Fallback Content for Development -->
        <div
          v-else
          class="d-flex flex-column align-items-center justify-content-center h-100 p-4 text-center"
        >
          <i class="bi bi-file-earmark-text display-4 text-secondary mb-3"></i>
          <h5 class="text-secondary mb-2">Template Preview</h5>
          <p class="text-muted mb-3">Template: {{ currentTemplate?.name || 'Unknown' }}</p>
          <div class="alert alert-info" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            Template components will be implemented in Prompt 2
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, type Component, shallowRef } from 'vue'
import { useDocumentTemplate } from '~/composables/useDocumentTemplate'
import { useDocumentRenderer } from '~/composables/useDocumentRenderer'
import type { DocumentData, DocumentError } from '~/types/document'

// Props
interface Props {
  selectedItem?: any
  templateId?: string
  documentData?: DocumentData
}

const props = withDefaults(defineProps<Props>(), {
  selectedItem: null,
  templateId: undefined,
  documentData: undefined
})

// Events
interface Emits {
  (e: 'template-changed', templateId: string): void
  (e: 'data-updated', data: DocumentData): void
  (e: 'error', error: DocumentError): void
}

const emit = defineEmits<Emits>()

// Composables
const {
  activeTemplates,
  getAvailableTemplates,
  getTemplateById
} = useDocumentTemplate()

const {
  renderTemplate,
  extractDocumentData,
  validateTemplateData: validateData
} = useDocumentRenderer()

// Local state
const currentTemplateId = ref<string | undefined>(props.templateId)
const templateComponent = shallowRef<Component | null>(null)
const templateLoading = ref(false)
const error = ref<DocumentError | null>(null)

// Computed properties
const currentTemplate = computed(() => {
  return currentTemplateId.value ? getTemplateById(currentTemplateId.value) : null
})

const processedDocumentData = computed((): DocumentData => {
  if (props.documentData) return props.documentData
  if (props.selectedItem) return extractDocumentData(props.selectedItem)
  return currentTemplate.value?.defaultData || {}
})

// Template type mapping
const TEMPLATE_TYPE_MAP: Record<string, string> = {
  'INVOICE': 'template-invoice',
  'RECEIPT': 'template-receipt'
}

// Methods
const selectTemplate = async (templateId: string): Promise<void> => {
  if (templateId === currentTemplateId.value) return
  
  try {
    error.value = null
    templateLoading.value = true
    
    const template = getTemplateById(templateId)
    if (!template) {
      throw new Error(`Template not found: ${templateId}`)
    }
    
    const component = await renderTemplate(templateId, processedDocumentData.value)
    
    if (!component) {
      throw new Error('Failed to load template component')
    }
    
    currentTemplateId.value = templateId
    templateComponent.value = component
    emit('template-changed', templateId)
  } catch (err) {
    const docError: DocumentError = {
      name: 'TemplateLoadError',
      message: err instanceof Error ? err.message : 'Failed to load template',
      templateId
    }
    error.value = docError
    emit('error', docError)
  } finally {
    templateLoading.value = false
  }
}

const retryLoadTemplate = (): void => {
  if (currentTemplateId.value) {
    selectTemplate(currentTemplateId.value)
  }
}

const handleTemplateError = (err: DocumentError): void => {
  error.value = err
  emit('error', err)
}

const getTemplateForDocumentType = (documentType: string): string => {
  return TEMPLATE_TYPE_MAP[documentType?.toUpperCase()] || 'template-invoice'
}

const autoSelectTemplate = async (item: any): Promise<void> => {
  const documentType = item.type || item.document_type
  if (!documentType) return
  
  const templateId = getTemplateForDocumentType(documentType)
  if (templateId !== currentTemplateId.value) {
    await selectTemplate(templateId)
  }
}

// Expose methods for parent components
defineExpose({
  getCurrentTemplateInfo: () => ({
    templateId: currentTemplateId.value,
    template: currentTemplate.value,
    documentData: processedDocumentData.value
  }),
  refreshTemplate: () => currentTemplateId.value && selectTemplate(currentTemplateId.value),
  selectTemplate,
  retryLoadTemplate
})

// Watchers
watch(
  () => props.templateId,
  (newTemplateId) => {
    if (newTemplateId && newTemplateId !== currentTemplateId.value) {
      selectTemplate(newTemplateId)
    }
  },
  { immediate: true }
)

watch(
  () => props.selectedItem,
  async (newItem) => {
    if (!newItem) return
    
    await autoSelectTemplate(newItem)
    
    // Validate data when selected item changes
    if (currentTemplateId.value) {
      const validation = validateData(currentTemplateId.value, processedDocumentData.value)
      if (!validation.isValid) {
        console.warn('Template data validation failed:', validation.errors)
      }
    }
  },
  { immediate: true }
)

// Lifecycle
onMounted(async () => {
  try {
    await getAvailableTemplates()
    
    // Auto-select template based on document type if selectedItem is available
    if (props.selectedItem) {
      await autoSelectTemplate(props.selectedItem)
      return
    }
    
    // Fallback: Select first active template if none selected
    if (!currentTemplateId.value && activeTemplates.value.length > 0) {
      await selectTemplate(activeTemplates.value[0].id)
    }
  } catch (err) {
    console.error('Failed to initialize templates:', err)
  }
})
</script>

<style scoped>
/* A4 aspect ratio for mobile devices */
@media (max-width: 768px) {
  .document-viewer {
    aspect-ratio: 595 / 842;
    height: auto !important;
    max-width: 95vw !important;
    max-height: 85vh !important;
  }
}

@media (max-width: 576px) {
  .document-viewer {
    max-width: 98vw !important;
    max-height: 90vh !important;
  }
}

/* Smooth transitions */
.document-viewer {
  transition: all 0.3s ease;
}
</style>
