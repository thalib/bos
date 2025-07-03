<template>
  <BaseTemplate :document-data="documentData" :template-config="templateConfig">
    <!-- Invoice Header -->
    <template #header="{ documentData: data }">
      <div class="row">
        <!-- Company Information -->
        <div class="col-md-6">
          <div class="company-info">
            <h3 class="mb-2 text-primary fw-bold">
              {{ data?.company?.name || 'Your Company Name' }}
            </h3>
            <div v-if="data?.company" class="text-muted small">
              <p v-if="data.company.address" class="mb-1">{{ data.company.address }}</p>
              <p v-if="data.company.city || data.company.state || data.company.zipCode" class="mb-1">
                {{ [data.company.city, data.company.state, data.company.zipCode].filter(Boolean).join(', ') }}
              </p>
              <p v-if="data.company.phone" class="mb-1">
                <i class="bi bi-telephone me-1"></i>{{ data.company.phone }}
              </p>
              <p v-if="data.company.email" class="mb-0">
                <i class="bi bi-envelope me-1"></i>{{ data.company.email }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- Invoice Details -->
        <div class="col-md-6 text-md-end">
          <h2 class="mb-3 text-dark fw-bold">INVOICE</h2>
          <div class="invoice-details">
            <div class="row mb-2">
              <div class="col-6 text-md-end">
                <strong class="text-muted">Invoice #:</strong>
              </div>
              <div class="col-6 text-md-end">
                {{ data?.documentNumber || 'INV-001' }}
              </div>
            </div>
            <div class="row mb-2">
              <div class="col-6 text-md-end">
                <strong class="text-muted">Date:</strong>
              </div>
              <div class="col-6 text-md-end">
                {{ formatDate(data?.date) }}
              </div>
            </div>
            <div v-if="data?.dueDate" class="row mb-2">
              <div class="col-6 text-md-end">
                <strong class="text-muted">Due Date:</strong>
              </div>
              <div class="col-6 text-md-end">
                {{ formatDate(data.dueDate) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Invoice Content -->
    <template #content="{ documentData: data }">
      <div class="invoice-content">
        <!-- Bill To Section -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="bill-to-section">
              <h5 class="mb-3 text-dark fw-semibold border-bottom pb-2">Bill To:</h5>
              <div v-if="data?.client" class="client-info">
                <h6 class="mb-2 fw-bold">{{ data.client.name || 'Client Name' }}</h6>
                <div class="text-muted small">
                  <p v-if="data.client.contactPerson" class="mb-1">
                    <strong>Contact:</strong> {{ data.client.contactPerson }}
                  </p>
                  <p v-if="data.client.address" class="mb-1">{{ data.client.address }}</p>
                  <p v-if="data.client.city || data.client.state || data.client.zipCode" class="mb-1">
                    {{ [data.client.city, data.client.state, data.client.zipCode].filter(Boolean).join(', ') }}
                  </p>
                  <p v-if="data.client.email" class="mb-1">
                    <i class="bi bi-envelope me-1"></i>{{ data.client.email }}
                  </p>
                  <p v-if="data.client.phone" class="mb-0">
                    <i class="bi bi-telephone me-1"></i>{{ data.client.phone }}
                  </p>
                </div>
              </div>
              <div v-else class="text-muted">
                <p class="mb-0">Client information not provided</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <!-- Additional invoice information can go here -->
          </div>
        </div>

        <!-- Line Items Table -->
        <div class="row mb-4">
          <div class="col-12">
            <h5 class="mb-3 text-dark fw-semibold border-bottom pb-2">Items:</h5>
            
            <!-- Desktop Table View -->
            <div class="d-none d-md-block">
              <table class="table table-borderless table-sm">
                <thead class="border-bottom">
                  <tr class="text-muted">
                    <th scope="col" class="fw-semibold">Description</th>
                    <th scope="col" class="fw-semibold text-center" style="width: 100px;">Qty</th>
                    <th scope="col" class="fw-semibold text-end" style="width: 120px;">Unit Price</th>
                    <th scope="col" class="fw-semibold text-end" style="width: 120px;">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!data?.items || data.items.length === 0">
                    <td colspan="4" class="text-center text-muted py-4">
                      <i class="bi bi-inbox display-6 d-block mb-2"></i>
                      No items found
                    </td>
                  </tr>
                  <tr v-else v-for="item in data.items" :key="item.id" class="border-bottom">
                    <td class="py-3">
                      <div class="fw-semibold">{{ item.description }}</div>
                      <small v-if="item.notes" class="text-muted">{{ item.notes }}</small>
                    </td>
                    <td class="py-3 text-center">{{ item.quantity }}</td>
                    <td class="py-3 text-end">{{ formatCurrency(item.unitPrice, data?.totals?.currency) }}</td>
                    <td class="py-3 text-end fw-semibold">{{ formatCurrency(item.total, data?.totals?.currency) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Mobile Card View -->
            <div class="d-md-none">
              <div v-if="!data?.items || data.items.length === 0" class="text-center text-muted py-4">
                <i class="bi bi-inbox display-6 d-block mb-2"></i>
                <p class="mb-0">No items found</p>
              </div>
              <div v-else class="invoice-items-mobile">
                <div v-for="item in data.items" :key="item.id" class="card mb-3 border-0 bg-light">
                  <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h6 class="mb-1 fw-semibold">{{ item.description }}</h6>
                      <span class="badge bg-primary">{{ formatCurrency(item.total, data?.totals?.currency) }}</span>
                    </div>
                    <div class="row text-sm">
                      <div class="col-6">
                        <small class="text-muted">Quantity: </small>
                        <small class="fw-semibold">{{ item.quantity }}</small>
                      </div>
                      <div class="col-6 text-end">
                        <small class="text-muted">Unit Price: </small>
                        <small class="fw-semibold">{{ formatCurrency(item.unitPrice, data?.totals?.currency) }}</small>
                      </div>
                    </div>
                    <small v-if="item.notes" class="text-muted d-block mt-2">{{ item.notes }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Totals Section -->
        <div class="row">
          <div class="col-md-6 offset-md-6">
            <div class="totals-section">
              <div class="card border-0">
                <div class="card-body p-3 bg-light">
                  <div v-if="data?.totals" class="totals-content">
                    <div class="d-flex justify-content-between mb-2">
                      <span class="text-muted">Subtotal:</span>
                      <span class="fw-semibold">{{ formatCurrency(data.totals.subtotal, data.totals.currency) }}</span>
                    </div>
                    <div v-if="data.totals.taxAmount > 0" class="d-flex justify-content-between mb-2">
                      <span class="text-muted">Tax:</span>
                      <span class="fw-semibold">{{ formatCurrency(data.totals.taxAmount, data.totals.currency) }}</span>
                    </div>
                    <div v-if="data.totals.discountAmount" class="d-flex justify-content-between mb-2">
                      <span class="text-muted">Discount:</span>
                      <span class="fw-semibold text-success">-{{ formatCurrency(data.totals.discountAmount, data.totals.currency) }}</span>
                    </div>
                    <div v-if="data.totals.shippingAmount" class="d-flex justify-content-between mb-2">
                      <span class="text-muted">Shipping:</span>
                      <span class="fw-semibold">{{ formatCurrency(data.totals.shippingAmount, data.totals.currency) }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-3">
                      <span class="h6 fw-bold">Total:</span>
                      <span class="h6 fw-bold text-primary">{{ formatCurrency(data.totals.total, data.totals.currency) }}</span>
                    </div>
                    
                    <!-- Payment Information -->
                    <div v-if="data.totals.amountPaid || data.totals.amountDue" class="payment-info">
                      <div v-if="data.totals.amountPaid" class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Amount Paid:</small>
                        <small class="fw-semibold text-success">{{ formatCurrency(data.totals.amountPaid, data.totals.currency) }}</small>
                      </div>
                      <div v-if="data.totals.amountDue" class="d-flex justify-content-between">
                        <small class="text-muted">Amount Due:</small>
                        <small class="fw-bold text-danger">{{ formatCurrency(data.totals.amountDue, data.totals.currency) }}</small>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center text-muted">
                    <p class="mb-0">No totals calculated</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes Section -->
        <div v-if="data?.metadata?.notes" class="row mt-4">
          <div class="col-12">
            <div class="notes-section">
              <h6 class="mb-2 text-dark fw-semibold">Notes:</h6>
              <div class="card border-0 bg-light">
                <div class="card-body p-3">
                  <p class="mb-0 text-muted">{{ data.metadata.notes }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Invoice Footer -->
    <template #footer="{ documentData: data }">
      <div class="row align-items-center">
        <div class="col-md-6">
          <p v-if="data?.company?.name" class="mb-0 text-muted small">
            {{ data.company.name }}
            <span v-if="data.company.taxId"> • Tax ID: {{ data.company.taxId }}</span>
          </p>
        </div>
        <div class="col-md-6 text-md-end">
          <p class="mb-0 text-muted small">
            <i class="bi bi-calendar3 me-1"></i>
            Generated on {{ formatDate(new Date()) }}
          </p>
        </div>
      </div>
    </template>
  </BaseTemplate>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import BaseTemplate from './BaseTemplate.vue'
import type { DocumentData, TemplateConfig } from '~/types/document'

// Props
interface Props {
  documentData?: DocumentData
  templateConfig?: TemplateConfig
  selectedItem?: any
}

const props = withDefaults(defineProps<Props>(), {
  documentData: () => ({}),
  templateConfig: undefined,
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

const formatCurrency = (amount: number | undefined, currency: string = 'USD'): string => {
  if (amount === undefined || amount === null) return '$0.00'
  
  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency.toUpperCase(),
      minimumFractionDigits: 2
    }).format(amount)
  } catch (error) {
    // Fallback for invalid currency codes
    return `$${amount.toFixed(2)}`
  }
}

// Computed properties for validation
const isValid = computed(() => {
  const data = props.documentData
  return !!(
    data?.company?.name &&
    data?.client?.name &&
    data?.items?.length &&
    data?.totals?.total
  )
})

// Watch for data changes and validate
watch(() => props.documentData, (newData) => {
  if (newData && !isValid.value) {
    console.warn('Invoice template: Invalid or incomplete data provided')
  }
}, { deep: true })
</script>

<style scoped>
/* Invoice-specific styling */
.company-info h3 {
  line-height: 1.2;
}

.invoice-details {
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  padding: 1rem;
}

.bill-to-section,
.totals-section {
  margin-bottom: 0;
}

.client-info h6 {
  color: #495057;
}

/* Table styling */
.table th {
  border-top: none;
  padding-bottom: 0.75rem;
  font-size: 0.875rem;
}

.table td {
  border-top: 1px solid #e9ecef;
  vertical-align: middle;
}

/* Mobile card styling */
.invoice-items-mobile .card {
  transition: transform 0.2s ease;
}

.invoice-items-mobile .card:hover {
  transform: translateY(-1px);
}

/* Totals section styling */
.totals-section .card {
  border-radius: 0.5rem;
}

.payment-info {
  border-top: 1px solid #dee2e6;
  padding-top: 0.5rem;
  margin-top: 0.5rem;
}

/* Print optimizations */
@media print {
  .invoice-details {
    background-color: transparent !important;
    border: 1px solid #000 !important;
  }
  
  .totals-section .card,
  .notes-section .card {
    border: 1px solid #000 !important;
    background-color: transparent !important;
  }
  
  .bg-light {
    background-color: transparent !important;
  }
  
  .text-primary {
    color: #000 !important;
  }
  
  .badge {
    background-color: transparent !important;
    color: #000 !important;
    border: 1px solid #000 !important;
  }
  
  /* Ensure mobile view is hidden in print */
  .d-md-none {
    display: none !important;
  }
  
  /* Ensure desktop table shows in print */
  .d-none.d-md-block {
    display: block !important;
  }
  
  /* Table print styling */
  .table {
    border-collapse: collapse;
  }
  
  .table th,
  .table td {
    border: 1px solid #000 !important;
    padding: 0.5rem !important;
  }
  
  .table thead th {
    background-color: #f0f0f0 !important;
    font-weight: bold !important;
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .invoice-details {
    margin-top: 1rem;
    text-align: left !important;
  }
  
  .invoice-details .row {
    text-align: left;
  }
  
  .invoice-details .col-6:last-child {
    text-align: right;
  }
}
</style>
