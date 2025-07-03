<template>
  <div class="base-template h-100 d-flex flex-column bg-white">
    <!-- Document Header Section -->
    <header class="template-header border-bottom flex-shrink-0">
      <div class="container-fluid p-3">
        <slot name="header" :document-data="documentData" :template-config="templateConfig">
          <!-- Default header content -->
          <div class="row align-items-center">
            <div class="col-md-8">
              <h4 class="mb-1 text-dark fw-bold">
                {{ documentData?.title || 'Document Title' }}
              </h4>
              <p v-if="documentData?.subtitle" class="mb-0 text-muted">
                {{ documentData.subtitle }}
              </p>
            </div>
            <div class="col-md-4 text-md-end">
              <p v-if="documentData?.documentNumber" class="mb-1 text-muted small">
                <strong>{{ documentNumberLabel }}:</strong> {{ documentData.documentNumber }}
              </p>
              <p v-if="documentData?.date" class="mb-0 text-muted small">
                <strong>Date:</strong> {{ formatDate(documentData.date) }}
              </p>
            </div>
          </div>
        </slot>
      </div>
    </header>

    <!-- Document Main Content Section -->
    <main class="template-content flex-grow-1 overflow-auto">
      <div class="container-fluid p-3 h-100">
        <slot name="content" :document-data="documentData" :template-config="templateConfig">
          <!-- Default content -->
          <div class="row h-100">
            <div class="col-12">
              <div class="d-flex flex-column align-items-center justify-content-center h-100 text-center">
                <i class="bi bi-file-earmark-text display-4 text-muted mb-3"></i>
                <h5 class="text-muted mb-2">Document Content</h5>
                <p class="text-muted">Template content will be displayed here</p>
              </div>
            </div>
          </div>
        </slot>
      </div>
    </main>

    <!-- Document Footer Section -->
    <footer class="template-footer border-top flex-shrink-0">
      <div class="container-fluid p-3">
        <slot name="footer" :document-data="documentData" :template-config="templateConfig">
          <!-- Default footer content -->
          <div class="row align-items-center">
            <div class="col-md-6">
              <p v-if="documentData?.company?.name" class="mb-0 text-muted small">
                {{ documentData.company.name }}
              </p>
            </div>
            <div class="col-md-6 text-md-end">
              <p class="mb-0 text-muted small">
                Generated on {{ formatDate(new Date()) }}
              </p>
            </div>
          </div>
        </slot>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed, type PropType } from 'vue'
import type { DocumentData, TemplateConfig } from '~/types/document'

// Props
interface Props {
  documentData?: DocumentData
  templateConfig?: TemplateConfig
}

const props = withDefaults(defineProps<Props>(), {
  documentData: () => ({}),
  templateConfig: undefined
})

// Computed properties
const documentNumberLabel = computed(() => {
  // Determine appropriate label based on document type
  const title = props.documentData?.title?.toLowerCase() || ''
  
  if (title.includes('invoice')) return 'Invoice #'
  if (title.includes('receipt')) return 'Receipt #'
  if (title.includes('statement')) return 'Statement #'
  if (title.includes('report')) return 'Report #'
  if (title.includes('contract')) return 'Contract #'
  
  return 'Document #'
})

// Methods
const formatDate = (date: string | Date): string => {
  if (!date) return ''
  
  const dateObj = typeof date === 'string' ? new Date(date) : date
  
  // Check if date is valid
  if (isNaN(dateObj.getTime())) return ''
  
  return dateObj.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>

<style scoped>
/* A4 Paper Dimensions and Layout */
.base-template {
  min-height: 100%;
  max-width: 100%;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size: 14px;
  line-height: 1.4;
  color: #333;
}

/* Header Styling */
.template-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

/* Content Area */
.template-content {
  background-color: #ffffff;
}

/* Footer Styling */
.template-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #dee2e6;
  min-height: 60px;
}

/* Print Optimization */
@media print {
  .base-template {
    width: 210mm;
    min-height: 297mm;
    margin: 0;
    padding: 0;
    font-size: 12px;
    color: #000;
    background: white !important;
  }
  
  /* Hide interactive elements in print */
  .dropdown,
  .btn,
  button {
    display: none !important;
  }
  
  /* Ensure content fits on page */
  .template-header {
    background: white !important;
    border-bottom: 1px solid #000 !important;
    padding: 10mm 15mm !important;
  }
  
  .template-content {
    padding: 0 15mm !important;
    background: white !important;
  }
  
  .template-footer {
    background: white !important;
    border-top: 1px solid #000 !important;
    padding: 10mm 15mm !important;
    position: fixed;
    bottom: 0;
    width: 100%;
  }
  
  /* Page breaks */
  .page-break {
    page-break-before: always;
  }
  
  .no-break {
    page-break-inside: avoid;
  }
  
  /* Ensure tables don't break awkwardly */
  table {
    page-break-inside: avoid;
  }
  
  tr {
    page-break-inside: avoid;
  }
  
  /* Print-specific text sizes */
  h1, h2, h3, h4, h5, h6 {
    color: #000 !important;
  }
  
  .text-muted {
    color: #666 !important;
  }
  
  /* Remove shadows and effects for print */
  .shadow,
  .shadow-sm,
  .shadow-lg {
    box-shadow: none !important;
  }
  
  .border {
    border: 1px solid #000 !important;
  }
  
  .border-secondary-subtle {
    border-color: #000 !important;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .base-template {
    font-size: 13px;
  }
  
  .template-header .container-fluid,
  .template-content .container-fluid,
  .template-footer .container-fluid {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  /* Stack elements vertically on mobile */
  .template-header .row > .col-md-4,
  .template-footer .row > .col-md-6 {
    text-align: left !important;
    margin-top: 0.5rem;
  }
  
  .template-header .row > .col-md-4:first-child,
  .template-footer .row > .col-md-6:first-child {
    margin-top: 0;
  }
}

@media (max-width: 576px) {
  .base-template {
    font-size: 12px;
  }
  
  .template-header,
  .template-footer {
    padding: 0.75rem 0;
  }
  
  .template-content {
    padding: 0.75rem 0;
  }
}

/* Ensure consistent spacing */
.template-header h4 {
  line-height: 1.2;
}

.template-footer p {
  line-height: 1.3;
}

/* Custom utilities for templates */
.template-section {
  margin-bottom: 1.5rem;
}

.template-section:last-child {
  margin-bottom: 0;
}

.template-divider {
  border-top: 1px solid #dee2e6;
  margin: 1rem 0;
}

@media print {
  .template-divider {
    border-color: #000 !important;
  }
}
</style>
