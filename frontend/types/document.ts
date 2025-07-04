/**
 * Document Template System Types
 * TypeScript interfaces for dynamic document template system
 */
import type { ApiError } from './index'

/**
 * Template Categories for organizing document templates
 */
export enum TemplateCategory {
  INVOICE = 'invoice',
  RECEIPT = 'receipt'
}

/**
 * Document Template Interface
 * Represents metadata for a document template
 */
export interface DocumentTemplate {
  /** Unique identifier for the template */
  id: string
  /** Display name of the template */
  name: string
  /** Description of the template's purpose */
  description: string
  /** Category for organization */
  category: TemplateCategory
  /** Vue component name for dynamic loading */
  component: string
  /** Default data structure for this template (optional - data comes from backend) */
  defaultData?: DocumentData
  /** Preview thumbnail URL (optional) */
  thumbnail?: string
  /** Whether the template is available/active */
  isActive: boolean
  /** Template version for compatibility */
  version: string
  /** Creation date */
  createdAt: Date
  /** Last updated date */
  updatedAt: Date
}

/**
 * Document Data Interface
 * Flexible data structure for template props
 */
export interface DocumentData {
  /** Basic document information */
  title?: string
  subtitle?: string
  documentNumber?: string
  date?: string | Date
  dueDate?: string | Date
  
  /** Company/Organization information */
  company?: CompanyInfo
  
  /** Client/Customer information */
  client?: ClientInfo
  
  /** Line items for invoices, receipts, etc. */
  items?: LineItem[]
  
  /** Totals and calculations */
  totals?: DocumentTotals
  
  /** Additional sections for reports, contracts */
  sections?: DocumentSection[]
  
  /** Custom fields for template-specific data */
  customFields?: Record<string, any>
  
  /** Metadata */
  metadata?: DocumentMetadata
}

/**
 * Company Information
 */
export interface CompanyInfo {
  name: string
  address?: string
  city?: string
  state?: string
  zipCode?: string
  country?: string
  phone?: string
  email?: string
  website?: string
  logo?: string
  taxId?: string
  department?: string
}

/**
 * Client Information
 */
export interface ClientInfo {
  name: string
  contactPerson?: string
  address?: string
  city?: string
  state?: string
  zipCode?: string
  country?: string
  phone?: string
  email?: string
  clientId?: string
}

/**
 * Line Item for invoices, receipts, etc.
 */
export interface LineItem {
  id: string
  description: string
  quantity: number
  unitPrice: number
  total: number
  taxRate?: number
  taxAmount?: number
  category?: string
  notes?: string
}

/**
 * Document Totals
 */
export interface DocumentTotals {
  subtotal: number
  taxAmount: number
  discountAmount?: number
  shippingAmount?: number
  total: number
  amountPaid?: number
  amountDue?: number
  currency: string
}

/**
 * Document Section for reports, contracts
 */
export interface DocumentSection {
  id: string
  title: string
  content: string
  order: number
  type: 'text' | 'table' | 'chart' | 'image'
  data?: any
}

/**
 * Document Metadata
 */
export interface DocumentMetadata {
  author?: string
  createdBy?: string
  department?: string
  project?: string
  tags?: string[]
  status?: string
  version?: string
  notes?: string
}

/**
 * Document Error Interface
 * Extends existing ApiError for template-specific errors
 */
export interface DocumentError extends ApiError {
  /** Template-specific error type */
  templateError?: TemplateErrorType
  /** Template ID that caused the error */
  templateId?: string
  /** Validation details */
  validationDetails?: ValidationError[]
  /** Error context */
  context?: Record<string, any>
}

/**
 * Template Error Types
 */
export enum TemplateErrorType {
  TEMPLATE_NOT_FOUND = 'template_not_found',
  TEMPLATE_LOAD_FAILED = 'template_load_failed',
  INVALID_TEMPLATE_DATA = 'invalid_template_data',
  TEMPLATE_RENDER_ERROR = 'template_render_error',
  TEMPLATE_VALIDATION_FAILED = 'template_validation_failed'
}

/**
 * Validation Error
 */
export interface ValidationError {
  field: string
  message: string
  code: string
  value?: any
}

/**
 * Validation Result
 */
export interface ValidationResult {
  isValid: boolean
  errors: ValidationError[]
  warnings?: ValidationError[]
}

/**
 * Template Loading State
 */
export interface TemplateLoadingState {
  isLoading: boolean
  error: DocumentError | null
  progress?: number
}
