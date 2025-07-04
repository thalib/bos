/**
 * Document Template Registry
 * Central configuration for all available document templates
 */
import type { DocumentTemplate } from '~/types/document'
import { TemplateCategory } from '~/types/document'

/**
 * Template metadata registry
 * Contains all available templates with their configurations
 */
export const DOCUMENT_TEMPLATES: DocumentTemplate[] = [
  {
    id: 'template-invoice',
    name: 'Invoice Template',
    description: 'Professional invoice template for billing clients',
    category: TemplateCategory.INVOICE,
    component: 'InvoiceTemplate',
    thumbnail: '/images/templates/invoice-thumb.png',
    isActive: true,
    version: '1.0.0',
    createdAt: new Date('2024-01-01'),
    updatedAt: new Date()
  },
  {
    id: 'template-receipt',
    name: 'Receipt Template',
    description: 'Simple receipt template for transactions and payments',
    category: TemplateCategory.RECEIPT,
    component: 'InvoiceTemplate',
    thumbnail: '/images/templates/receipt-thumb.png',
    isActive: true,
    version: '1.0.0',
    createdAt: new Date('2024-01-01'),
    updatedAt: new Date()
  }
]

/**
 * Template categories with metadata
 */
export const TEMPLATE_CATEGORIES = [
  {
    id: TemplateCategory.INVOICE,
    name: 'Invoices',
    description: 'Professional invoices for billing clients',
    icon: 'bi-receipt',
    color: 'primary'
  },
  {
    id: TemplateCategory.RECEIPT,
    name: 'Receipts',
    description: 'Payment receipts and transaction confirmations',
    icon: 'bi-receipt-cutoff',
    color: 'info'
  }
]

/**
 * Lazy loading configuration for template components
 */
export const TEMPLATE_COMPONENT_MAP = {
  InvoiceTemplate: () => import('~/components/Document/templates/InvoiceTemplate.vue')
}

/**
 * Get templates by category
 */
export function getTemplatesByCategory(category: TemplateCategory): DocumentTemplate[] {
  return DOCUMENT_TEMPLATES.filter(template => 
    template.category === category && template.isActive
  )
}

/**
 * Get template by ID
 */
export function getTemplateById(id: string): DocumentTemplate | undefined {
  return DOCUMENT_TEMPLATES.find(template => template.id === id)
}

/**
 * Get all active templates
 */
export function getActiveTemplates(): DocumentTemplate[] {
  return DOCUMENT_TEMPLATES.filter(template => template.isActive)
}

/**
 * Get category metadata
 */
export function getCategoryInfo(category: TemplateCategory) {
  return TEMPLATE_CATEGORIES.find(cat => cat.id === category)
}
