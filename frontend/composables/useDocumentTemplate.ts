/**
 * Document Template Composable
 * Provides template loading, validation, and management functionality
 */
import { ref, computed, readonly, type Component, markRaw } from 'vue'
import type { 
  DocumentTemplate, 
  DocumentData, 
  DocumentError, 
  ValidationResult, 
  TemplateLoadingState
} from '~/types/document'
import { 
  TemplateCategory,
  TemplateErrorType 
} from '~/types/document'
import { 
  getActiveTemplates, 
  getTemplateById as getTemplateFromRegistry,
  TEMPLATE_COMPONENT_MAP 
} from '~/config/documentTemplates'

// Global template cache for performance
const templateCache = new Map<string, Component>()
const loadingStates = ref<Map<string, TemplateLoadingState>>(new Map())

/**
 * Document Template Composable
 * Manages template loading, validation, and state
 */
export const useDocumentTemplate = () => {
  // Reactive state
  const loading = ref(false)
  const error = ref<DocumentError | null>(null)
  const availableTemplates = ref<DocumentTemplate[]>([])

  // Computed properties
  const activeTemplates = computed(() => 
    availableTemplates.value.filter(template => template.isActive)
  )

  /**
   * Get available templates from registry
   */
  const getAvailableTemplates = async (): Promise<DocumentTemplate[]> => {
    try {
      loading.value = true
      error.value = null

      const templates = getActiveTemplates()
      availableTemplates.value = templates
      
      return templates
    } catch (err) {
      const docError: DocumentError = {
        name: 'TemplateLoadError',
        message: 'Failed to load available templates',
        templateError: TemplateErrorType.TEMPLATE_LOAD_FAILED,
        statusCode: 500
      }
      error.value = docError
      throw docError
    } finally {
      loading.value = false
    }
  }

  /**
   * Load a specific template component dynamically
   */
  const loadTemplate = async (templateId: string): Promise<Component | null> => {
    try {
      // Check cache first
      if (templateCache.has(templateId)) {
        return templateCache.get(templateId)!
      }

      // Find template metadata
      const template = availableTemplates.value.find(t => t.id === templateId)
      if (!template) {
        throw createDocumentError(
          'Template not found',
          TemplateErrorType.TEMPLATE_NOT_FOUND,
          templateId
        )
      }

      // Set loading state
      loadingStates.value.set(templateId, { isLoading: true, error: null })

      // Dynamic import using component map
      const componentLoader = TEMPLATE_COMPONENT_MAP[template.component as keyof typeof TEMPLATE_COMPONENT_MAP]
      
      if (!componentLoader) {
        throw new Error(`No component loader found for: ${template.component}`)
      }
      
      // Load and wrap component
      const loaded = await componentLoader()
      const component = markRaw('default' in loaded ? loaded.default : loaded)
      
      // Cache the component
      templateCache.set(templateId, component)

      // Update loading state
      loadingStates.value.set(templateId, { isLoading: false, error: null })

      return component
    } catch (err) {
      const docError = err instanceof Error 
        ? createDocumentError(
            `Failed to load template component: ${err.message}`,
            TemplateErrorType.TEMPLATE_LOAD_FAILED,
            templateId
          )
        : err as DocumentError
      
      // Update loading state with error
      loadingStates.value.set(templateId, { isLoading: false, error: docError })
      error.value = docError
      return null
    }
  }

  /**
   * Validate template data against template requirements
   */
  const validateTemplateData = (templateId: string, data: DocumentData): ValidationResult => {
    const template = availableTemplates.value.find(t => t.id === templateId)
    
    if (!template) {
      return {
        isValid: false,
        errors: [{
          field: 'templateId',
          message: 'Template not found',
          code: 'TEMPLATE_NOT_FOUND'
        }]
      }
    }

    // Since data comes from backend, minimal validation is needed
    if (!data || typeof data !== 'object') {
      return {
        isValid: false,
        errors: [{
          field: 'data',
          message: 'Invalid document data structure',
          code: 'INVALID_DATA'
        }]
      }
    }

    return { isValid: true, errors: [] }
  }

  /**
   * Get template by ID
   */
  const getTemplateById = (templateId: string): DocumentTemplate | null => {
    return availableTemplates.value.find(t => t.id === templateId) || null
  }

  /**
   * Get loading state for a template
   */
  const getTemplateLoadingState = (templateId: string): TemplateLoadingState => {
    return loadingStates.value.get(templateId) || { isLoading: false, error: null }
  }

  /**
   * Clear template cache
   */
  const clearTemplateCache = (): void => {
    templateCache.clear()
    loadingStates.value.clear()
  }

  // Initialize templates on first use
  if (availableTemplates.value.length === 0) {
    getAvailableTemplates().catch(console.error)
  }

  return {
    // State
    loading: readonly(loading),
    error: readonly(error),
    availableTemplates: readonly(availableTemplates),
    
    // Computed
    activeTemplates: readonly(activeTemplates),
    
    // Methods
    getAvailableTemplates,
    loadTemplate,
    validateTemplateData,
    getTemplateById,
    getTemplateLoadingState,
    clearTemplateCache
  }
}

/**
 * Helper function to create document errors
 */
function createDocumentError(
  message: string,
  templateError: TemplateErrorType,
  templateId?: string,
  context?: Record<string, any>
): DocumentError {
  return {
    name: 'DocumentError',
    message,
    templateError,
    templateId,
    context,
    statusCode: 400
  }
}
