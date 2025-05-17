<script setup lang="ts">
import { computed, ref } from 'vue';
import { useEstimate } from '~/composables/useEstimate';
import html2canvas from 'html2canvas';

const { estimateData } = useEstimate();
const outputPrintRef = ref<HTMLElement | null>(null);

const formatCurrency = (value: number) =>
  new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);

const formatDate = (date: Date) =>
  new Intl.DateTimeFormat('en-IN', { dateStyle: 'long' }).format(date);

const totalQuantity = computed(() =>
  estimateData.value?.items?.reduce((total: number, item: any) => total + item.quantity, 0) || 0
);

const downloadPNG = async () => {
  if (!outputPrintRef.value) return;
  try {
    const canvas = await html2canvas(outputPrintRef.value, {
      scale: 2,
      useCORS: true,
      backgroundColor: '#ffffff'
    });
    const link = document.createElement('a');
    link.download = `${estimateData.value?.estimateNumber || 'details'}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
  } catch (error) {
    console.error('Error generating PNG:', error);
    alert('Failed to generate PNG. See console for details.');
  }
};

const printEstimate = () => {
  if (!outputPrintRef.value) return;
  const printWindow = window.open('', '_blank', '');
  if (printWindow) {
    const contentToPrint = outputPrintRef.value.cloneNode(true) as HTMLElement;
    const bootstrapLink = document.createElement('link');
    bootstrapLink.rel = 'stylesheet';
    bootstrapLink.href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css';
    const printStyles = document.createElement('style');

    printWindow.document.head.appendChild(bootstrapLink);
    printWindow.document.head.appendChild(printStyles);
    printWindow.document.body.appendChild(contentToPrint);
    // Set the document title to the estimate number for PDF file name
    printWindow.document.title = estimateData.value?.estimateNumber || 'details';
    bootstrapLink.onload = () => {
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
    };
    setTimeout(() => {
      if (!printWindow.document.readyState || printWindow.document.readyState === 'complete') {
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
      }
    }, 1000);
  } else {
    alert('Could not open print window. Please check your browser pop-up settings.');
  }
};
</script>

<template>
  <div class="row">
    <!-- Action Row -->
    <div id="outputControll" class="d-flex justify-content-end mb-3 gap-2 no-print">
      <button class="btn btn-success btn-sm text-dark fw-bold" @click="downloadPNG">
        <i class="bi bi-download me-1"></i> Download PNG
      </button>
      <button class="btn btn-primary btn-sm text-dark fw-bold" @click="printEstimate">
        <i class="bi bi-printer me-1"></i> Print
      </button>
    </div>
  </div>
  <div class="row">
    <!-- Estimate Display Area -->
    <div id="outputPrint" ref="outputPrintRef" class="p-2 rounded bg-white text-dark">
      <div class="estimate-inner">
        <div v-if="estimateData">
          <div class="container">
            <!-- Header -->
            <div id="documentHeader">
              <!-- Row 1: Company name and address, centered, with left+right+bottom border -->
              <div
                class="row justify-content-center align-items-center text-center header-main-row border border-secondary border-2 py-2 px-3">
                <div class="col">
                  <h4 class="mb-1">{{ estimateData.business.name }}</h4>
                  <p style="white-space: pre-line; font-size: 0.9rem;">{{ estimateData.business.address }}</p>
                </div>
              </div>

              <!-- Row 2: Details and Document Type -->
              <div class="row align-items-center border border-secondary border-2 border-top-0">
                <div class="col-6 py-2 text-start border-secondary border-2 border-end">
                  <div><strong>Estimate #:</strong> {{ estimateData.estimateNumber }}</div>
                  <div><strong>Date:</strong> {{ formatDate(estimateData.date) }}</div>
                  <div><strong>Valid for:</strong> {{ estimateData.validityDays }} days</div>
                  <div v-if="estimateData.switches?.freeDelivery">
                    <strong>Transport:</strong> FREE Delivery (within Tamilnadu)
                  </div>
                </div>
                <div class="col-6 text-center">
                  <span class="text-uppercase fs-4 fw-bold">{{ estimateData.documentType || 'ESTIMATE' }}</span>
                </div>
              </div>
            </div>

            <!-- Customer Details -->
            <div id="orderCustomerDetails" class="row">
              <div class="col-12 border border-secondary border-2 border-top-0 fw-bold">
                Bill To:
              </div>
              <div class="col-12 border border-secondary border-2 border-top-0 ">
                <p style="white-space: pre-line;">{{ estimateData.customer.billTo || '-' }}</p>
              </div>
            </div>

            <!-- Order Items Table -->
            <div id="orderItemTable">
              <!-- Table Header -->
              <div class="row">
                <div class="col-6 border border-secondary border-2 border-top-0 fw-bold">
                  Product
                </div>
                <div class="col-2 text-center border border-secondary border-2 border-top-0 border-start-0 fw-bold">
                  Qty
                </div>
                <div class="col-2 text-center border border-secondary border-2 border-top-0 border-start-0 fw-bold">
                  Price
                </div>
                <div class="col-2 text-center border border-secondary border-2 border-top-0 border-start-0 fw-bold">
                  Total
                </div>
              </div>
              <!-- Row Items -->
              <div v-for="(item, index) in estimateData.items" :key="item.product.id" class="row ">

                <div class="col-6 border border-secondary border-2 border-top-0"> <span class="fw-semibold">{{
                  item.product.name }}</span><br>
                  <small>{{ item.product.description }}</small>
                </div>
                <div class="col-2 text-center fw-semibold border border-secondary border-2 border-top-0 border-start-0">
                  {{ item.quantity }} <span v-if="item.unit">{{ item.unit }}</span>
                </div>
                <div class="col-2 text-center fw-semibold border border-secondary border-2 border-top-0 border-start-0">
                  <span v-if="item.product.mrp && item.product.mrp !== 0">
                    <s class="text-danger">₹{{ formatCurrency(item.product.mrp) }}</s><br>
                  </span>
                  {{ formatCurrency(item.price) }}
                  <br>
                  <span class="text-secondary small">
                    ({{ estimateData.switches?.incTax ? 'inc.' : 'excl.' }} GST {{ item.taxPercentage }}%)
                  </span>
                </div>

                <div class="col-2 text-end fw-semibold border border-secondary border-2 border-top-0 border-start-0">
                  {{ formatCurrency(item.total) }}
                </div>
              </div>
              <!-- Table Footer -->
              <!-- Subtotal -->
              <div class="row fw-semibold">
                <div class="col-6 border border-secondary border-2 border-top-0"></div>
                <div class="col-2 text-center border border-secondary border-2 border-top-0 border-start-0">
                  {{ totalQuantity }}
                </div>
                <div class="col-2 text-end border border-secondary border-2 border-top-0 border-start-0">
                  <span v-if="!estimateData.switches?.incTax">Subtotal</span>
                </div>
                <div class="col-2 text-end border border-secondary border-2 border-top-0 border-start-0">
                  <span v-if="!estimateData.switches?.incTax">{{ formatCurrency(estimateData.subtotal) }}</span>
                </div>
              </div>
              <!-- TAX -->
              <div v-if="!estimateData.switches?.incTax" class="row">
                <div class="col-6 border-secondary border-2 border-start"></div>
                <div class="col-2 border-secondary border-2 border-end"></div>
                <div class="col-2 text-end fw-bold border border-secondary border-2 border-top-0 border-start-0">
                  GST Total
                </div>
                <div class="col-2 text-end border border-secondary border-2 border-top-0 border-start-0 fw-bold">
                  {{ formatCurrency(estimateData.totalTax) }}
                </div>
              </div>
              <!-- Totals -->
              <div v-if="estimateData.switches?.showTotal !== false" class="row">
                <div class="col-6 border-secondary border-2 border-start"></div>
                <div class="col-2 border-secondary border-2 border-end"></div>
                <div class="col-2 text-end border border-secondary border-2 border-top-0 border-start-0">
                  <span class="fs-4 fw-bold">Total</span>
                  <span class="fw-semibold"><br>(Inc. GST)</span>
                </div>
                <div class="col-2 text-end border border-secondary border-2 border-top-0 border-start-0 fw-bold">
                  {{ formatCurrency(estimateData.grandTotal) }}
                </div>
              </div>
            </div> <!-- Order Items Table -->

            <div id="orderTerms">
              <!-- Optional: Bank Transfer Details -->
              <div v-if="estimateData.bankDetails" id="orderBankDetails" class="row">
                <div class="col-12 border-secondary border-2 border border-top-0 border-bottom-0 pt-3">
                  <h6 class="fw-semibold">Bank Transfer Details:</h6>
                  <div class="small ms-2" style="white-space: pre-line;">{{ estimateData.bankDetails }}</div>
                </div>
              </div>
              <!-- Optional: Notes -->
              <div v-if="estimateData.customer.notes && estimateData.customer.notes.trim()" id="orderNotes"
                class="row">
                <div class="col-12 border-secondary border-2 border border-top-0 border-bottom-0 pt-3">
                  <h6 class="fw-semibold">Notes:</h6>
                  <div class="small ms-2" style="white-space: pre-line;">{{ estimateData.customer.notes }}</div>
                </div>
              </div>

              <!-- Optional: Terms and Conditions -->
              <div id="orderTerms" class="row">
                <div class="col-12 border-secondary border-2 border border-top-0 pt-3">
                  <h6 class="fw-semibold">Terms & Conditions:</h6>
                  <div class="small mb-2" style="white-space: pre-line;">
                    {{ (estimateData.termsAndConditions ?? '') }}
                  </div>
                </div>
              </div>

              <div id="orderFooter" class="row">
                <div class="col-12 text-center fw-semibold border-secondary border-2 border border-top-0 py-2">
                  This is a temporary quote. Upon confirmation, we will send an official proforma invoice
                </div>
              </div>
              
            </div> <!-- End orderTerms -->

          </div> <!-- End container -->
        </div>
        <div v-else class="text-center">
          Generate an estimate using the form above to see the output here.
        </div>
      </div> <!-- End estimate-inner -->
    </div>
  </div>
</template>

<style scoped>
/* Ensure the output area has a white background even in dark mode for printing/PNG */
#outputPrint {
  background-color: #ffffff !important;
  color: #000000 !important;
}

/* Hide controls during print */
@media print {
  .no-print {
    display: none !important;
  }
  #outputPrint {
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  #outputPrint .container,
  #outputPrint .row,
  #outputPrint .col {
    width: 100% !important;
    max-width: 100% !important;
    flex: 0 0 100% !important;
    box-sizing: border-box;
    margin: 0 !important;
    padding: 0 !important;
  }
  /* Remove default page margins for print */
  @page {
    size: A4;
    margin: 0;
  }
  body, html {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }
}

</style>