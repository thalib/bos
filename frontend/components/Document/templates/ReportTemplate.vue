<template>
  <BaseTemplate :document-data="documentData">
    <!-- Report Header -->
    <template #header="{ documentData: data }">
      <div class="row align-items-center">
        <!-- Report Title and Company -->
        <div class="col-md-8">
          <div class="report-header-content">
            <h2 class="mb-1 text-primary fw-bold">
              {{ data?.title || 'Report Title' }}
            </h2>
            <h5 v-if="data?.subtitle" class="mb-2 text-muted fw-normal">
              {{ data.subtitle }}
            </h5>
            <p v-if="data?.company?.name" class="mb-0 text-muted small">
              <i class="bi bi-building me-1"></i>
              {{ data.company.name }}
              <span v-if="data.company.department"> • {{ data.company.department }}</span>
            </p>
          </div>
        </div>
        
        <!-- Report Metadata -->
        <div class="col-md-4 text-md-end">
          <div class="report-meta bg-light rounded p-3">
            <div class="row g-2">
              <div class="col-12" v-if="data?.documentNumber">
                <small class="text-muted d-block">Report ID</small>
                <strong class="text-dark">{{ data.documentNumber }}</strong>
              </div>
              <div class="col-12" v-if="data?.date">
                <small class="text-muted d-block">Date</small>
                <strong class="text-dark">{{ formatDate(data.date) }}</strong>
              </div>
              <div class="col-12" v-if="data?.metadata?.author">
                <small class="text-muted d-block">Author</small>
                <strong class="text-dark">{{ data.metadata.author }}</strong>
              </div>
              <div class="col-12" v-if="data?.metadata?.status">
                <small class="text-muted d-block">Status</small>
                <span class="badge" :class="getStatusBadgeClass(data.metadata.status)">
                  {{ data.metadata.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Report Content -->
    <template #content="{ documentData: data }">
      <div class="report-content">
        <!-- Executive Summary (if available) -->
        <div v-if="getExecutiveSummary(data)" class="row mb-4">
          <div class="col-12">
            <div class="executive-summary">
              <h4 class="mb-3 text-dark fw-semibold border-bottom pb-2">
                <i class="bi bi-file-text me-2"></i>
                Executive Summary
              </h4>
              <div class="card border-0 bg-light">
                <div class="card-body p-4">
                  <p class="mb-0 lead">{{ getExecutiveSummary(data) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Key Metrics (if available) -->
        <div v-if="getKeyMetrics(data)" class="row mb-4">
          <div class="col-12">
            <h4 class="mb-3 text-dark fw-semibold border-bottom pb-2">
              <i class="bi bi-graph-up me-2"></i>
              Key Metrics
            </h4>
            <div class="row g-3">
              <div v-for="(metric, index) in getKeyMetrics(data)" :key="index" class="col-lg-3 col-md-6">
                <div class="card border-0 bg-primary bg-opacity-10 h-100">
                  <div class="card-body text-center p-3">
                    <i :class="metric.icon || 'bi-bar-chart'" class="display-6 text-primary mb-2"></i>
                    <h5 class="mb-1 text-dark fw-bold">{{ metric.value }}</h5>
                    <p class="mb-0 text-muted small">{{ metric.label }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Report Sections -->
        <div v-if="data?.sections && data.sections.length > 0" class="report-sections">
          <div v-for="section in sortedSections(data.sections)" :key="section.id" class="row mb-4">
            <div class="col-12">
              <div class="report-section">
                <h4 class="mb-3 text-dark fw-semibold border-bottom pb-2">
                  <i :class="getSectionIcon(section.type)" class="me-2"></i>
                  {{ section.title }}
                </h4>
                
                <!-- Text Section -->
                <div v-if="section.type === 'text'" class="text-section">
                  <div class="card border-0 bg-light">
                    <div class="card-body p-4">
                      <div v-html="formatContent(section.content)" class="content-text"></div>
                    </div>
                  </div>
                </div>
                
                <!-- Table Section -->
                <div v-else-if="section.type === 'table'" class="table-section">
                  <div class="table-responsive">
                    <table v-if="section.data && section.data.headers && section.data.rows" class="table table-striped table-hover">
                      <thead class="table-dark">
                        <tr>
                          <th v-for="header in section.data.headers" :key="header" scope="col">
                            {{ header }}
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(row, rowIndex) in section.data.rows" :key="rowIndex">
                          <td v-for="(cell, cellIndex) in row" :key="cellIndex">
                            {{ cell }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <div v-else class="text-center text-muted py-4">
                      <i class="bi bi-table display-6 d-block mb-2"></i>
                      <p class="mb-0">Table data not available</p>
                    </div>
                  </div>
                </div>
                
                <!-- Chart Section -->
                <div v-else-if="section.type === 'chart'" class="chart-section">
                  <div class="chart-placeholder bg-light border rounded d-flex align-items-center justify-content-center" style="height: 300px;">
                    <div class="text-center text-muted">
                      <i class="bi bi-bar-chart display-4 mb-3"></i>
                      <h6 class="mb-2">Chart Placeholder</h6>
                      <p class="mb-0 small">{{ section.content || 'Chart will be displayed here' }}</p>
                      <small v-if="section.data" class="text-muted d-block mt-2">
                        Data points: {{ Array.isArray(section.data) ? section.data.length : 'Available' }}
                      </small>
                    </div>
                  </div>
                </div>
                
                <!-- Image Section -->
                <div v-else-if="section.type === 'image'" class="image-section">
                  <div v-if="section.data && section.data.url" class="text-center">
                    <img :src="section.data.url" :alt="section.content" class="img-fluid rounded shadow-sm" style="max-height: 400px;">
                    <p v-if="section.content" class="mt-2 text-muted small">{{ section.content }}</p>
                  </div>
                  <div v-else class="image-placeholder bg-light border rounded d-flex align-items-center justify-content-center" style="height: 300px;">
                    <div class="text-center text-muted">
                      <i class="bi bi-image display-4 mb-3"></i>
                      <h6 class="mb-2">Image Placeholder</h6>
                      <p class="mb-0 small">{{ section.content || 'Image will be displayed here' }}</p>
                    </div>
                  </div>
                </div>
                
                <!-- Default Section -->
                <div v-else class="default-section">
                  <div class="card border-0 bg-light">
                    <div class="card-body p-4">
                      <p class="mb-0">{{ section.content || 'No content available for this section.' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- No Content State -->
        <div v-else class="row">
          <div class="col-12">
            <div class="no-content text-center py-5">
              <i class="bi bi-file-earmark-text display-1 text-muted mb-4"></i>
              <h5 class="text-muted mb-3">No Report Content</h5>
              <p class="text-muted mb-0">This report doesn't contain any sections or data to display.</p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Report Footer -->
    <template #footer="{ documentData: data }">
      <div class="row align-items-center">
        <div class="col-md-4">
          <p v-if="data?.company?.name" class="mb-0 text-muted small">
            {{ data.company.name }}
            <span v-if="data.metadata?.department"> • {{ data.metadata.department }}</span>
          </p>
        </div>
        <div class="col-md-4 text-center">
          <p v-if="data?.metadata?.project" class="mb-0 text-muted small">
            <strong>Project:</strong> {{ data.metadata.project }}
          </p>
        </div>
        <div class="col-md-4 text-end">
          <p class="mb-0 text-muted small">
            <i class="bi bi-calendar3 me-1"></i>
            Generated on {{ formatDate(new Date()) }}
            <span v-if="data?.metadata?.version"> • v{{ data.metadata.version }}</span>
          </p>
        </div>
      </div>
    </template>
  </BaseTemplate>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import BaseTemplate from './BaseTemplate.vue'
import type { DocumentData, DocumentSection } from '~/types/document'

// Props
interface Props {
  documentData?: DocumentData
  selectedItem?: any
}

const props = withDefaults(defineProps<Props>(), {
  documentData: () => ({}),
  selectedItem: null
})

// Events
interface Emits {
  (e: 'data-updated', data: DocumentData): void
  (e: 'error', error: any): void
}

const emit = defineEmits<Emits>()

// Methods
const formatDate = (date: string | Date | undefined): string => {
  if (!date) return new Date().toLocaleDateString()
  
  const dateObj = typeof date === 'string' ? new Date(date) : date
  
  if (isNaN(dateObj.getTime())) return 'Invalid Date'
  
  return dateObj.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatContent = (content: string): string => {
  if (!content) return ''
  
  // Basic text formatting - convert line breaks to HTML
  return content.replace(/\n/g, '<br>')
}

const getExecutiveSummary = (data: DocumentData): string | null => {
  // Look for executive summary in sections
  const summarySection = data?.sections?.find(section => 
    section.title.toLowerCase().includes('executive') || 
    section.title.toLowerCase().includes('summary')
  )
  
  if (summarySection) {
    return summarySection.content
  }
  
  // Look in metadata
  if (data?.metadata?.notes) {
    return data.metadata.notes
  }
  
  return null
}

const getKeyMetrics = (data: DocumentData): any[] | null => {
  // Look for metrics in custom fields
  if (data?.customFields?.metrics) {
    return data.customFields.metrics
  }
  
  // Generate sample metrics if we have totals (for invoice-like reports)
  if (data?.totals) {
    return [
      {
        label: 'Total Amount',
        value: formatCurrency(data.totals.total, data.totals.currency),
        icon: 'bi-currency-dollar'
      },
      {
        label: 'Items Count',
        value: data.items?.length || 0,
        icon: 'bi-list-ul'
      }
    ]
  }
  
  return null
}

const formatCurrency = (amount: number | undefined, currency: string = 'USD'): string => {
  if (amount === undefined || amount === null) return '$0.00'
  
  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency.toUpperCase(),
      minimumFractionDigits: 2
    }).format(amount)
  } catch (error) {
    return `$${amount.toFixed(2)}`
  }
}

const sortedSections = (sections: DocumentSection[]): DocumentSection[] => {
  return [...sections].sort((a, b) => a.order - b.order)
}

const getSectionIcon = (type: string): string => {
  const iconMap: Record<string, string> = {
    text: 'bi-file-text',
    table: 'bi-table',
    chart: 'bi-bar-chart',
    image: 'bi-image',
    default: 'bi-file-earmark-text'
  }
  return iconMap[type] || iconMap.default
}

const getStatusBadgeClass = (status: string): string => {
  const statusMap: Record<string, string> = {
    draft: 'bg-secondary',
    pending: 'bg-warning text-dark',
    approved: 'bg-success',
    published: 'bg-primary',
    archived: 'bg-dark',
    default: 'bg-info'
  }
  
  const normalizedStatus = status.toLowerCase()
  return statusMap[normalizedStatus] || statusMap.default
}

// Computed properties
const isValid = computed(() => {
  const data = props.documentData
  return !!(data?.title && (data?.sections?.length || data?.customFields))
})

// Watch for data changes
watch(() => props.documentData, (newData) => {
  if (newData && !isValid.value) {
    console.warn('Report template: Invalid or incomplete data provided')
  }
}, { deep: true })
</script>

<style scoped>
/* Report-specific styling */
.report-header-content h2 {
  line-height: 1.2;
}

.report-meta {
  min-height: 120px;
}

.executive-summary .lead {
  font-size: 1.1rem;
  line-height: 1.6;
}

/* Key metrics cards */
.card.bg-primary.bg-opacity-10 {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card.bg-primary.bg-opacity-10:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Report sections */
.report-section {
  margin-bottom: 2rem;
}

.report-section:last-child {
  margin-bottom: 0;
}

/* Content formatting */
.content-text {
  line-height: 1.6;
}

.content-text :deep(p) {
  margin-bottom: 1rem;
}

.content-text :deep(p:last-child) {
  margin-bottom: 0;
}

/* Table styling */
.table-section .table {
  margin-bottom: 0;
}

.table thead th {
  border-top: none;
  font-weight: 600;
  font-size: 0.875rem;
}

/* Chart and image placeholders */
.chart-placeholder,
.image-placeholder {
  border: 2px dashed #dee2e6;
  transition: border-color 0.3s ease;
}

.chart-placeholder:hover,
.image-placeholder:hover {
  border-color: #adb5bd;
}

/* Status badges */
.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

/* No content state */
.no-content {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* Print optimizations */
@media print {
  .report-meta {
    background-color: transparent !important;
    border: 1px solid #000 !important;
  }
  
  .card.border-0 {
    border: 1px solid #000 !important;
  }
  
  .bg-light,
  .bg-primary.bg-opacity-10 {
    background-color: transparent !important;
  }
  
  .table-dark {
    background-color: #f0f0f0 !important;
    color: #000 !important;
  }
  
  .badge {
    background-color: transparent !important;
    color: #000 !important;
    border: 1px solid #000 !important;
  }
  
  .chart-placeholder,
  .image-placeholder {
    border: 1px solid #000 !important;
    background-color: transparent !important;
  }
  
  /* Ensure section breaks */
  .report-section {
    page-break-inside: avoid;
  }
  
  .executive-summary {
    page-break-inside: avoid;
  }
  
  /* Hide interactive elements */
  .card:hover {
    transform: none !important;
    box-shadow: none !important;
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .report-meta {
    margin-top: 1rem;
    text-align: left !important;
  }
  
  .executive-summary .lead {
    font-size: 1rem;
  }
  
  .chart-placeholder,
  .image-placeholder {
    height: 200px !important;
  }
}

@media (max-width: 576px) {
  .report-section h4 {
    font-size: 1.1rem;
  }
  
  .chart-placeholder,
  .image-placeholder {
    height: 150px !important;
  }
}
</style>
