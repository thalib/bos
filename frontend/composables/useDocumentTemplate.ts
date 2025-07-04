/**
 * Document Template Composable
 * Provides template loading, validation, and management functionality
 */
import { ref, computed, readonly, type Ref, type Component, markRaw } from 'vue'
import { componentRef, loadDynamicComponent, createSafeComponentRegistry } from '~/utils/componentUtils'
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
  DOCUMENT_TEMPLATES, 
  getActiveTemplates, 
  getTemplateById as getTemplateFromRegistry,
  TEMPLATE_COMPONENT_MAP 
} from '~/config/documentTemplates'

// Create a safe version of the template component map
const SAFE_TEMPLATE_COMPONENT_MAP = createSafeComponentRegistry(TEMPLATE_COMPONENT_MAP)

// Global template cache for performance
const templateCache = new Map<string, Component>()
const templateRegistry = ref<DocumentTemplate[]>([])
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
  const templatesByCategory = computed(() => {
    const grouped: Record<TemplateCategory, DocumentTemplate[]> = {
      [TemplateCategory.INVOICE]: [],
      [TemplateCategory.REPORT]: [],
      [TemplateCategory.LETTER]: [],
      [TemplateCategory.CONTRACT]: [],
      [TemplateCategory.RECEIPT]: [],
      [TemplateCategory.STATEMENT]: [],
      [TemplateCategory.OTHER]: []
    }

    availableTemplates.value.forEach(template => {
      if (grouped[template.category]) {
        grouped[template.category].push(template)
      }
    })

    return grouped
  })

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

      // Use the template registry
      const templates = getActiveTemplates()
      
      availableTemplates.value = templates
      templateRegistry.value = templates
      
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
      loadingStates.value.set(templateId, {
        isLoading: true,
        error: null
      })

      // Dynamic import using the safe component map
      try {
        const componentLoader = SAFE_TEMPLATE_COMPONENT_MAP[template.component as keyof typeof SAFE_TEMPLATE_COMPONENT_MAP]
        
        if (!componentLoader) {
          throw new Error(`No component loader found for: ${template.component}`)
        }
        
        // Use our helper to load the component (it already handles markRaw)
        const component = await loadDynamicComponent<Component>(componentLoader)
        
        // Cache the component (already wrapped with markRaw)
        templateCache.set(templateId, component)

        // Update loading state
        loadingStates.value.set(templateId, {
          isLoading: false,
          error: null
        })

        return component
      } catch (importError) {
        const errorMessage = importError instanceof Error ? importError.message : 'Unknown import error'
        throw createDocumentError(
          `Failed to load template component: ${template.component} - ${errorMessage}`,
          TemplateErrorType.TEMPLATE_LOAD_FAILED,
          templateId,
          { importError: errorMessage }
        )
      }
    } catch (err) {
      const docError = err as DocumentError
      
      // Update loading state with error
      loadingStates.value.set(templateId, {
        isLoading: false,
        error: docError
      })

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

    const errors: ValidationResult['errors'] = []
    const warnings: ValidationResult['errors'] = []

    // Since data comes from backend, minimal validation is needed
    // Backend handles data validation, so we just check basic structure
    if (!data || typeof data !== 'object') {
      errors.push({
        field: 'data',
        message: 'Invalid document data structure',
        code: 'INVALID_DATA'
      })
    }

    return {
      isValid: errors.length === 0,
      errors,
      warnings
    }
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
    return loadingStates.value.get(templateId) || {
      isLoading: false,
      error: null
    }
  }

  /**
   * Clear template cache
   */
  const clearTemplateCache = (): void => {
    templateCache.clear()
    loadingStates.value.clear()
  }

  /**
   * Refresh available templates
   */
  const refreshTemplates = async (): Promise<void> => {
    clearTemplateCache()
    await getAvailableTemplates()
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
    templatesByCategory: readonly(templatesByCategory),
    activeTemplates: readonly(activeTemplates),
    
    // Methods
    getAvailableTemplates,
    loadTemplate,
    validateTemplateData,
    getTemplateById,
    getTemplateLoadingState,
    clearTemplateCache,
    refreshTemplates
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
