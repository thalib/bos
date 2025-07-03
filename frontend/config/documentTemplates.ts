/**
 * Document Template Registry
 * Central configuration for all available document templates
 */
import type { DocumentTemplate, DocumentData } from '~/types/document'
import { TemplateCategory } from '~/types/document'

/**
 * Template metadata registry
 * Contains all available templates with their configurations
 */
export const DOCUMENT_TEMPLATES: DocumentTemplate[] = [
  {
    id: 'invoice-basic',
    name: 'Basic Invoice',
    description: 'Simple invoice template with line items, totals, and company branding',
    category: TemplateCategory.INVOICE,
    component: 'InvoiceTemplate',
    defaultData: createDefaultInvoiceData(),
    thumbnail: '/images/templates/invoice-basic-thumb.png',
    isActive: true,
    version: '1.0.0',
    createdAt: new Date('2024-01-01'),
    updatedAt: new Date()
  },
  {
    id: 'invoice-detailed',
    name: 'Detailed Invoice',
    description: 'Comprehensive invoice template with tax details, payment terms, and notes',
    category: TemplateCategory.INVOICE,
    component: 'InvoiceTemplate',
    defaultData: createDefaultDetailedInvoiceData(),
    thumbnail: '/images/templates/invoice-detailed-thumb.png',
    isActive: true,
    version: '1.1.0',
    createdAt: new Date('2024-01-15'),
    updatedAt: new Date()
  },
  {
    id: 'report-basic',
    name: 'Basic Report',
    description: 'Standard report template with sections, data tables, and charts',
    category: TemplateCategory.REPORT,
    component: 'ReportTemplate',
    defaultData: createDefaultReportData(),
    thumbnail: '/images/templates/report-basic-thumb.png',
    isActive: true,
    version: '1.0.0',
    createdAt: new Date('2024-01-01'),
    updatedAt: new Date()
  },
  {
    id: 'report-executive',
    name: 'Executive Report',
    description: 'Executive-level report with key metrics, executive summary, and insights',
    category: TemplateCategory.REPORT,
    component: 'ReportTemplate',
    defaultData: createDefaultExecutiveReportData(),
    thumbnail: '/images/templates/report-executive-thumb.png',
    isActive: true,
    version: '1.0.0',
    createdAt: new Date('2024-01-10'),
    updatedAt: new Date()
  },
  {
    id: 'receipt-simple',
    name: 'Simple Receipt',
    description: 'Basic receipt template for transactions and payments',
    category: TemplateCategory.RECEIPT,
    component: 'InvoiceTemplate', // Reuse invoice template for receipts
    defaultData: createDefaultReceiptData(),
    thumbnail: '/images/templates/receipt-simple-thumb.png',
    isActive: true,
    version: '1.0.0',
    createdAt: new Date('2024-01-05'),
    updatedAt: new Date()
  },
  {
    id: 'statement-monthly',
    name: 'Monthly Statement',
    description: 'Monthly account statement with transaction history',
    category: TemplateCategory.STATEMENT,
    component: 'ReportTemplate', // Reuse report template for statements
    defaultData: createDefaultStatementData(),
    thumbnail: '/images/templates/statement-monthly-thumb.png',
    isActive: true,
    version: '1.0.0',
    createdAt: new Date('2024-01-08'),
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
    id: TemplateCategory.REPORT,
    name: 'Reports',
    description: 'Business reports and analytics documents',
    icon: 'bi-file-earmark-bar-graph',
    color: 'success'
  },
  {
    id: TemplateCategory.RECEIPT,
    name: 'Receipts',
    description: 'Payment receipts and transaction confirmations',
    icon: 'bi-receipt-cutoff',
    color: 'info'
  },
  {
    id: TemplateCategory.STATEMENT,
    name: 'Statements',
    description: 'Account statements and summaries',
    icon: 'bi-file-earmark-spreadsheet',
    color: 'warning'
  },
  {
    id: TemplateCategory.LETTER,
    name: 'Letters',
    description: 'Business letters and correspondence',
    icon: 'bi-envelope',
    color: 'secondary'
  },
  {
    id: TemplateCategory.CONTRACT,
    name: 'Contracts',
    description: 'Legal contracts and agreements',
    icon: 'bi-file-earmark-text',
    color: 'danger'
  },
  {
    id: TemplateCategory.OTHER,
    name: 'Other',
    description: 'Miscellaneous document types',
    icon: 'bi-file-earmark',
    color: 'dark'
  }
]

/**
 * Lazy loading configuration for template components
 * Using dynamic imports with markRaw to prevent Vue reactivity issues
 */
export const TEMPLATE_COMPONENT_MAP = {
  InvoiceTemplate: () => import('~/components/Document/templates/InvoiceTemplate.vue'),
  ReportTemplate: () => import('~/components/Document/templates/ReportTemplate.vue'),
  BaseTemplate: () => import('~/components/Document/templates/BaseTemplate.vue')
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

// Default data creation functions

/**
 * Create default invoice data
 */
function createDefaultInvoiceData(): DocumentData {
  return {
    title: 'Invoice',
    documentNumber: 'INV-001',
    date: new Date().toISOString().split('T')[0],
    dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    company: {
      name: 'Your Company Name',
      address: '123 Business Street',
      city: 'Business City',
      state: 'State',
      zipCode: '12345',
      country: 'United States',
      phone: '(555) 123-4567',
      email: 'info@yourcompany.com',
      website: 'www.yourcompany.com',
      taxId: 'TAX123456789'
    },
    client: {
      name: 'Client Company Name',
      contactPerson: 'John Doe',
      address: '456 Client Avenue',
      city: 'Client City',
      state: 'State',
      zipCode: '67890',
      country: 'United States',
      phone: '(555) 987-6543',
      email: 'contact@clientcompany.com'
    },
    items: [
      {
        id: '1',
        description: 'Professional Services',
        quantity: 10,
        unitPrice: 150.00,
        total: 1500.00,
        taxRate: 0.08,
        taxAmount: 120.00,
        category: 'Services'
      },
      {
        id: '2',
        description: 'Software License',
        quantity: 1,
        unitPrice: 299.99,
        total: 299.99,
        taxRate: 0.08,
        taxAmount: 24.00,
        category: 'Software'
      }
    ],
    totals: {
      subtotal: 1799.99,
      taxAmount: 144.00,
      total: 1943.99,
      amountDue: 1943.99,
      currency: 'USD'
    },
    metadata: {
      notes: 'Payment is due within 30 days. Thank you for your business!',
      author: 'Accounting Department',
      department: 'Finance'
    }
  }
}

/**
 * Create detailed invoice data
 */
function createDefaultDetailedInvoiceData(): DocumentData {
  const basicData = createDefaultInvoiceData()
  return {
    ...basicData,
    documentNumber: 'INV-002',
    totals: {
      ...basicData.totals!,
      discountAmount: 100.00,
      shippingAmount: 25.00,
      total: 1868.99,
      amountDue: 1868.99
    },
    metadata: {
      ...basicData.metadata,
      notes: 'Payment terms: Net 30 days. Late fees may apply after due date. Discount applied for early payment.',
      project: 'Q1 2024 Implementation',
      version: '1.1'
    }
  }
}

/**
 * Create default report data
 */
function createDefaultReportData(): DocumentData {
  return {
    title: 'Monthly Business Report',
    subtitle: 'Performance Analysis for Current Period',
    documentNumber: 'RPT-001',
    date: new Date().toISOString().split('T')[0],
    company: {
      name: 'Your Company Name',
      address: '123 Business Street',
      city: 'Business City',
      state: 'State',
      zipCode: '12345'
    },
    sections: [
      {
        id: '1',
        title: 'Executive Summary',
        content: 'This report provides a comprehensive analysis of our business performance for the current period. Key highlights include increased revenue, improved customer satisfaction, and successful implementation of new initiatives.',
        order: 1,
        type: 'text'
      },
      {
        id: '2',
        title: 'Financial Performance',
        content: 'Revenue and expense analysis',
        order: 2,
        type: 'table',
        data: {
          headers: ['Metric', 'Current Period', 'Previous Period', 'Change'],
          rows: [
            ['Revenue', '$125,000', '$118,000', '+5.9%'],
            ['Expenses', '$85,000', '$82,000', '+3.7%'],
            ['Net Profit', '$40,000', '$36,000', '+11.1%'],
            ['Profit Margin', '32%', '30.5%', '+1.5%']
          ]
        }
      },
      {
        id: '3',
        title: 'Sales Trends',
        content: 'Monthly sales performance chart',
        order: 3,
        type: 'chart',
        data: [
          { month: 'Jan', sales: 105000 },
          { month: 'Feb', sales: 118000 },
          { month: 'Mar', sales: 125000 }
        ]
      }
    ],
    customFields: {
      metrics: [
        {
          label: 'Total Revenue',
          value: '$125,000',
          icon: 'bi-currency-dollar'
        },
        {
          label: 'New Customers',
          value: '42',
          icon: 'bi-people'
        },
        {
          label: 'Customer Satisfaction',
          value: '4.8/5',
          icon: 'bi-star-fill'
        },
        {
          label: 'Growth Rate',
          value: '+5.9%',
          icon: 'bi-graph-up'
        }
      ]
    },
    metadata: {
      author: 'Analytics Team',
      department: 'Analytics Department',
      project: 'Monthly Reporting',
      status: 'published',
      version: '1.0'
    }
  }
}

/**
 * Create executive report data
 */
function createDefaultExecutiveReportData(): DocumentData {
  const basicReport = createDefaultReportData()
  return {
    ...basicReport,
    title: 'Executive Summary Report',
    subtitle: 'Strategic Overview and Key Performance Indicators',
    documentNumber: 'EXE-001',
    sections: [
      {
        id: '1',
        title: 'Strategic Overview',
        content: 'This executive summary presents critical business metrics and strategic initiatives that drive our organization forward. Our focus remains on sustainable growth, operational excellence, and market leadership.',
        order: 1,
        type: 'text'
      },
      {
        id: '2',
        title: 'Key Performance Indicators',
        content: 'Critical business metrics',
        order: 2,
        type: 'table',
        data: {
          headers: ['KPI', 'Target', 'Actual', 'Performance'],
          rows: [
            ['Revenue Growth', '5%', '5.9%', '✓ Exceeded'],
            ['Customer Retention', '95%', '97%', '✓ Exceeded'],
            ['Market Share', '15%', '14.2%', '⚠ Below Target'],
            ['Employee Satisfaction', '85%', '88%', '✓ Exceeded']
          ]
        }
      }
    ],
    customFields: {
      metrics: [
        {
          label: 'Revenue Growth',
          value: '+5.9%',
          icon: 'bi-graph-up'
        },
        {
          label: 'Market Position',
          value: '#2',
          icon: 'bi-trophy'
        },
        {
          label: 'Team Size',
          value: '156',
          icon: 'bi-people'
        },
        {
          label: 'Customer NPS',
          value: '68',
          icon: 'bi-heart'
        }
      ]
    }
  }
}

/**
 * Create default receipt data
 */
function createDefaultReceiptData(): DocumentData {
  return {
    title: 'Receipt',
    documentNumber: 'RCP-001',
    date: new Date().toISOString().split('T')[0],
    company: {
      name: 'Your Store Name',
      address: '123 Store Street',
      city: 'Store City',
      state: 'State',
      zipCode: '12345',
      phone: '(555) 123-4567'
    },
    client: {
      name: 'Customer Name'
    },
    items: [
      {
        id: '1',
        description: 'Product Purchase',
        quantity: 2,
        unitPrice: 49.99,
        total: 99.98
      }
    ],
    totals: {
      subtotal: 99.98,
      taxAmount: 8.00,
      total: 107.98,
      amountPaid: 107.98,
      amountDue: 0,
      currency: 'USD'
    },
    metadata: {
      notes: 'Thank you for your purchase!'
    }
  }
}

/**
 * Create default statement data
 */
function createDefaultStatementData(): DocumentData {
  return {
    title: 'Monthly Statement',
    subtitle: 'Account Activity Summary',
    documentNumber: 'STMT-001',
    date: new Date().toISOString().split('T')[0],
    company: {
      name: 'Your Financial Institution',
      address: '123 Finance Street',
      city: 'Finance City',
      state: 'State',
      zipCode: '12345'
    },
    client: {
      name: 'Account Holder',
      clientId: 'ACC-123456'
    },
    sections: [
      {
        id: '1',
        title: 'Account Summary',
        content: 'Monthly account activity and balance information',
        order: 1,
        type: 'table',
        data: {
          headers: ['Description', 'Amount', 'Date', 'Balance'],
          rows: [
            ['Opening Balance', '$1,250.00', '2024-01-01', '$1,250.00'],
            ['Deposit', '$2,500.00', '2024-01-15', '$3,750.00'],
            ['Payment', '-$150.00', '2024-01-20', '$3,600.00'],
            ['Interest', '$12.50', '2024-01-31', '$3,612.50']
          ]
        }
      }
    ],
    totals: {
      subtotal: 3612.50,
      taxAmount: 0,
      total: 3612.50,
      currency: 'USD'
    },
    metadata: {
      notes: 'Statement period: January 1-31, 2024',
      department: 'Customer Service'
    }
  }
}
