/**
 * Document Renderer Composable
 * Provides template rendering, preview, and data extraction functionality
 */
import { ref, reactive, computed, type Component, markRaw } from 'vue'
import { useDocumentTemplate } from './useDocumentTemplate'
import type { 
  DocumentData, 
  DocumentError, 
  ValidationResult,
  DocumentTemplate 
} from '~/types/document'

/**
 * Document Renderer Composable
 * Handles template rendering, data extraction, and validation
 */
export const useDocumentRenderer = () => {
  // Composables
  const {
    loadTemplate,
    getTemplateById,
    validateTemplateData,
    getAvailableTemplates
  } = useDocumentTemplate()

  // Reactive state
  const renderingState = reactive({
    isRendering: false,
    currentTemplateId: null as string | null,
    lastRenderedData: null as DocumentData | null,
    renderError: null as DocumentError | null
  })

  // Loading states
  const loading = ref(false)
  const error = ref<DocumentError | null>(null)

  // Computed properties
  const isReady = computed(() => !loading.value && !error.value)
  const hasError = computed(() => !!error.value)

  /**
   * Render a template with the provided data
   * @param templateId - Template identifier
   * @param data - Document data for rendering
   * @returns Promise<Component | null>
   */
  const renderTemplate = async (templateId: string, data: any): Promise<Component | null> => {
    try {
      renderingState.isRendering = true
      renderingState.renderError = null
      error.value = null
      
      // Validate template exists
      const template = getTemplateById(templateId)
      if (!template) {
        throw new Error(`Template not found: ${templateId}`)
      }
      
      // Validate template data
      const validation = validateTemplateData(templateId, data)
      if (!validation.isValid) {
        console.warn('Template data validation warnings:', validation.errors)
        // Continue with warnings, but don't fail
      }
      
      // Load template component
      const component = await loadTemplate(templateId)
      if (!component) {
        throw new Error(`Failed to load template component: ${templateId}`)
      }
      
      // Update state
      renderingState.currentTemplateId = templateId
      renderingState.lastRenderedData = data
      
      return component
    } catch (err) {
      const renderError: DocumentError = {
        name: 'TemplateRenderError',
        message: err instanceof Error ? err.message : 'Failed to render template',
        templateId
      }
      
      renderingState.renderError = renderError
      error.value = renderError
      
      // Try to fallback to default template
      const fallbackComponent = await fallbackToDefaultTemplate(data)
      return fallbackComponent
    } finally {
      renderingState.isRendering = false
    }
  }

  /**
   * Generate HTML preview of a template with data
   * @param templateId - Template identifier
   * @param data - Document data for preview
   * @returns Promise<string> - HTML string
   */
  const previewTemplate = async (templateId: string, data: any): Promise<string> => {
    try {
      const component = await renderTemplate(templateId, data)
      if (!component) {
        throw new Error('Failed to render template for preview')
      }
      
      // For now, return a basic HTML representation
      // In a full implementation, you might use server-side rendering
      // or a virtual DOM renderer to generate actual HTML
      const template = getTemplateById(templateId)
      return `
        <div class="document-preview">
          <h3>${template?.name || 'Document Preview'}</h3>
          <p>Template: ${templateId}</p>
          <p>Data: ${JSON.stringify(data, null, 2)}</p>
          <small class="text-muted">Preview generated at ${new Date().toLocaleString()}</small>
        </div>
      `
    } catch (err) {
      throw new Error(`Preview generation failed: ${err instanceof Error ? err.message : 'Unknown error'}`)
    }
  }

  /**
   * Extract document data from a selected item
   * @param selectedItem - Selected item object
   * @returns DocumentData - Extracted and formatted document data
   */
  const extractDocumentData = (selectedItem: any): DocumentData => {
    if (!selectedItem) {
      return {}
    }

    // Basic data extraction with common field mappings
    const extractedData: DocumentData = {
      title: selectedItem.name || selectedItem.title || 'Untitled Document',
      subtitle: selectedItem.subtitle || selectedItem.description,
      documentNumber: selectedItem.id || selectedItem.number || selectedItem.reference,
      date: selectedItem.date || selectedItem.created_at || new Date().toISOString().split('T')[0],
      dueDate: selectedItem.due_date || selectedItem.dueDate,
      
      // Extract company information if available
      company: extractCompanyInfo(selectedItem),
      
      // Extract client information if available
      client: extractClientInfo(selectedItem),
      
      // Extract line items if available (for invoices, receipts)
      items: extractLineItems(selectedItem),
      
      // Extract totals if available
      totals: extractTotals(selectedItem),
      
      // Extract sections for reports
      sections: extractSections(selectedItem),
      
      // Custom fields - preserve original item for template-specific processing
      customFields: {
        originalItem: selectedItem,
        extractedAt: new Date().toISOString(),
        itemType: selectedItem.type || 'unknown'
      },
      
      // Metadata
      metadata: {
        author: selectedItem.author || selectedItem.created_by,
        createdBy: selectedItem.created_by || selectedItem.user?.name,
        status: selectedItem.status,
        tags: selectedItem.tags || [],
        notes: selectedItem.notes || selectedItem.comments
      }
    }

    return extractedData
  }

  /**
   * Validate template data for a specific template
   * @param templateId - Template identifier
   * @param data - Document data to validate
   * @returns ValidationResult
   */
  const validateTemplateDataForTemplate = (templateId: string, data: any): ValidationResult => {
    try {
      return validateTemplateData(templateId, data)
    } catch (err) {
      return {
        isValid: false,
        errors: [{
          field: 'template',
          message: err instanceof Error ? err.message : 'Unknown error',
          code: 'VALIDATION_FAILED'
        }],
        warnings: []
      }
    }
  }

  /**
   * Fallback to default template when primary template fails
   * @param data - Document data
   * @returns Promise<Component | null>
   */
  const fallbackToDefaultTemplate = async (data: any): Promise<Component | null> => {
    try {
      // Try to load BaseTemplate as fallback
      const fallbackComponent = await loadTemplate('base')
      if (fallbackComponent) {
        console.warn('Falling back to base template due to render error')
        return fallbackComponent
      }
      
      // If base template is not available, try the first available template
      const templates = await getAvailableTemplates()
      if (templates.length > 0) {
        const firstTemplate = templates[0]
        console.warn(`Falling back to first available template: ${firstTemplate.id}`)
        return await loadTemplate(firstTemplate.id)
      }
    } catch (fallbackErr) {
      console.error('Fallback template loading failed:', fallbackErr)
    }
    
    return null
  }

  // Helper functions for data extraction

  const extractCompanyInfo = (item: any) => {
    const company = item.company || item.organization || item.business
    if (!company && !item.company_name) return undefined

    return {
      name: company?.name || item.company_name || '',
      address: company?.address || item.company_address,
      city: company?.city || item.company_city,
      state: company?.state || item.company_state,
      zipCode: company?.zip_code || item.company_zip,
      country: company?.country || item.company_country,
      phone: company?.phone || item.company_phone,
      email: company?.email || item.company_email,
      website: company?.website || item.company_website,
      logo: company?.logo || item.company_logo,
      taxId: company?.tax_id || item.company_tax_id
    }
  }

  const extractClientInfo = (item: any) => {
    const client = item.client || item.customer || item.contact
    if (!client && !item.client_name) return undefined

    return {
      name: client?.name || item.client_name || '',
      contactPerson: client?.contact_person || item.contact_person,
      address: client?.address || item.client_address,
      city: client?.city || item.client_city,
      state: client?.state || item.client_state,
      zipCode: client?.zip_code || item.client_zip,
      country: client?.country || item.client_country,
      phone: client?.phone || item.client_phone,
      email: client?.email || item.client_email,
      clientId: client?.id || item.client_id
    }
  }

  const extractLineItems = (item: any) => {
    const items = item.items || item.line_items || item.products
    if (!Array.isArray(items)) return undefined

    return items.map((lineItem: any, index: number) => ({
      id: lineItem.id || `item-${index}`,
      description: lineItem.description || lineItem.name || '',
      quantity: parseFloat(lineItem.quantity || 1),
      unitPrice: parseFloat(lineItem.unit_price || lineItem.price || 0),
      total: parseFloat(lineItem.total || (lineItem.quantity * lineItem.unit_price) || 0),
      taxRate: parseFloat(lineItem.tax_rate || 0),
      taxAmount: parseFloat(lineItem.tax_amount || 0),
      category: lineItem.category,
      notes: lineItem.notes
    }))
  }

  const extractTotals = (item: any) => {
    const totals = item.totals || item.summary
    if (!totals && typeof item.total === 'undefined') return undefined

    return {
      subtotal: parseFloat(totals?.subtotal || item.subtotal || 0),
      taxAmount: parseFloat(totals?.tax_amount || item.tax_amount || 0),
      discountAmount: parseFloat(totals?.discount_amount || item.discount_amount || 0),
      shippingAmount: parseFloat(totals?.shipping_amount || item.shipping_amount || 0),
      total: parseFloat(totals?.total || item.total || 0),
      amountPaid: parseFloat(totals?.amount_paid || item.amount_paid || 0),
      amountDue: parseFloat(totals?.amount_due || item.amount_due || 0),
      currency: totals?.currency || item.currency || 'USD'
    }
  }

  const extractSections = (item: any) => {
    const sections = item.sections || item.content_sections
    if (!Array.isArray(sections)) return undefined

    return sections.map((section: any, index: number) => ({
      id: section.id || `section-${index}`,
      title: section.title || section.name || '',
      content: section.content || section.text || '',
      order: section.order || index,
      type: section.type || 'text',
      data: section.data
    }))
  }

  // Return the composable interface
  return {
    // State
    loading: computed(() => loading.value),
    error: computed(() => error.value),
    isReady,
    hasError,
    renderingState: computed(() => renderingState),
    
    // Methods
    renderTemplate,
    previewTemplate,
    extractDocumentData,
    validateTemplateData: validateTemplateDataForTemplate,
    
    // Utility methods
    clearError: () => {
      error.value = null
      renderingState.renderError = null
    },
    reset: () => {
      loading.value = false
      error.value = null
      renderingState.isRendering = false
      renderingState.currentTemplateId = null
      renderingState.lastRenderedData = null
      renderingState.renderError = null
    }
  }
}
