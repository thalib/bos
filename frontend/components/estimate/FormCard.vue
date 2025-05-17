<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { useEstimate } from '~/composables/useEstimate'; // Import the composable
import { useProducts } from '~/composables/useProducts'; // Import the products composable
import { useCompanyData } from '~/composables/useCompanyData'; // Import the new company data composable
import type { Company, Bank } from '~/composables/useCompanyData'; // Import types
import EstimateItems from '~/components/estimate/EstimateItems.vue';
import OrderStats from '~/components/estimate/OrderStats.vue'; // Import the new component

// Use the composable
const { setEstimate, clearEstimate } = useEstimate();

// Use the composable for products 
const { products: availableProducts, loading: productsLoading, error: productsError } = useProducts();

// Use the composable for company data
const { companies, banks, loadingCompanies, loadingBanks, error: companyError } = useCompanyData();

const DEFAULT_INVOICE_NUMBER = 99; // Default invoice number
const DEFAULT_COMPANY_INDEX = 0;
const DEFAULT_DOCUMENT_TYPE = 'Estimate';
const DEFAULT_VALIDITY_DAYS = 5;
const DEFAULT_DISPATCH_DAYS = '12-15';
const DEFAULT_CUSTOMER_BILL_TO = '';
const DEFAULT_CUSTOMER_NOTES = '';
const DEFAULT_SWITCHES = {
  incTax: true,
  showTotal: true,
  freeDelivery: false,
  vrlLogistics: false
};
const DEFAULT_BANK_INDEX = 0;

// Company Details State
const selectedCompanyIndex = ref(DEFAULT_COMPANY_INDEX);
const companyAddress = computed(() => {
  if (loadingCompanies.value || companies.value.length === 0) {
    return ''; // Return empty if still loading or no data
  }
  return companies.value[selectedCompanyIndex.value]?.address || '';
});
const companyPrefix = computed(() => {
  if (loadingCompanies.value || companies.value.length === 0) {
    return ''; // Return empty if still loading or no data
  }
  return companies.value[selectedCompanyIndex.value]?.prefix || '';
});

// Document Details State
const documentTypes = ['Estimate', 'Bill', 'Invoice', 'Empty'];
const selectedDocumentType = ref(DEFAULT_DOCUMENT_TYPE);
const estimateDate = ref(new Date().toISOString().split('T')[0]); // Default to today
const invoiceNumber = ref(DEFAULT_INVOICE_NUMBER); // Default start
const validityDays = ref(DEFAULT_VALIDITY_DAYS); // Default validity in days
const dispatchDays = ref(DEFAULT_DISPATCH_DAYS); // Default dispatch in days

const invoicePrefix = computed(() => {
  const date = new Date(estimateDate.value);
  const year = date.getFullYear().toString().slice(-2);
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  return `${companyPrefix.value}${year}${month}`;
});

// Centralized function to get the next invoice number
function get_invoice_number() {
  let storedInvoice = localStorage.getItem('invoice_number');
  if (storedInvoice && !isNaN(Number(storedInvoice))) {
    return Number(storedInvoice) + 1;
  }
  return DEFAULT_INVOICE_NUMBER;
}

// On app load, get invoice number from localStorage or use DEFAULT_INVOICE_NUMBER
onMounted(() => {
  invoiceNumber.value = get_invoice_number();
});

// Customer Details State
const customerBillTo = ref(DEFAULT_CUSTOMER_BILL_TO);
const customerNotes = ref(DEFAULT_CUSTOMER_NOTES);

// Estimate Items State (will be managed more deeply in EstimateItems component)
const selectedProducts = ref<any[]>([]); // Array to hold products added to the estimate

// Switches State
const switches = ref({ ...DEFAULT_SWITCHES });

// Bank Account State
const selectedBankIndex = ref(DEFAULT_BANK_INDEX); // 0 = None, 1+ = banks

// Terms & Conditions State
const termsAndConditions = computed(() => {
  let terms = `- Unit price is ${switches.value.incTax ? 'inclusive' : 'exclusive'} of GST\n`;
  terms += `- Price are ExFactory/ExDepot Price (Chennai, Tamilnadu)\n`;
  terms += `- Payment Terms: 100% Advance\n`;
  terms += `- Dispatch: ${dispatchDays.value} working days from the date of payment\n`;


  // Transport
  if (switches.value.freeDelivery) {
    terms += '- Transport: FREE Delivery (within Tamilnadu)\n';
  } else {
    terms += '- Transport: EXTRA (Buyer Scope)\n';
  }

  // Unloading Charges
  terms += '- Unloading Charges: Exclusive (Buyer Scope)\n';

  // VRL Logistics
  if (switches.value.vrlLogistics) {
    terms += '- Shipping Mode: VRL Logistics\n';
    terms += '- Delivery Type: Customer must pick up the order from the VRL Logistics Hub/Warehouse\n';
    terms += '- Shipping Charges: Not included. To be paid separately at the VRL Logistics Hub/Warehouse during pickup\n';
  }

  terms += '- Tolerance: ± 0.5% for Weight, ± 5% for thickness & Qty is acceptable.\n';
  return terms;
});

const editableTerms = ref(termsAndConditions.value); // Initialize with computed terms

// Keep editableTerms in sync with termsAndConditions unless user edits
watch(termsAndConditions, (val) => {
  if (!editableTerms.value || editableTerms.value === '' || editableTerms.value === termsAndConditions.value) {
    editableTerms.value = val;
  }
});

// Watch relevant fields (excluding customer details) and update terms in realtime
watch([
  selectedCompanyIndex,
  selectedDocumentType,
  estimateDate,
  invoiceNumber,
  validityDays,
  dispatchDays,
  switches,
  selectedBankIndex
], () => {
  editableTerms.value = termsAndConditions.value;
}, { immediate: true, deep: true });

// Function to handle adding a product (passed down to ProductSelection)
const handleAddProduct = (product: any) => {
  const existingProduct = selectedProducts.value.find(p => p.id === product.id);
  if (!existingProduct) {
    // Ensure added product has editable fields initialized correctly
    selectedProducts.value.push({ 
      ...product,
      quantity: 1,
      price: product.price, // Use original price initially
      mrp: product.mrp,
      taxPercentage: product.taxPercentage
    });
  }
};

// Function to remove a product from the estimate
const removeProduct = (productId: string) => {
  selectedProducts.value = selectedProducts.value.filter(p => p.id !== productId);
};

// Function to handle updates from EstimateItems (e.g., quantity, price change)
const handleUpdateProduct = (updatedProduct: any) => {
  const index = selectedProducts.value.findIndex(p => p.id === updatedProduct.id);
  if (index !== -1) {
    // Update the product in the array to maintain reactivity
    selectedProducts.value[index] = { ...selectedProducts.value[index], ...updatedProduct };
  }
};

// Function to reset the form to defaults
const resetForm = () => {
  selectedCompanyIndex.value = DEFAULT_COMPANY_INDEX;
  selectedDocumentType.value = DEFAULT_DOCUMENT_TYPE;
  estimateDate.value = new Date().toISOString().split('T')[0];
  invoiceNumber.value = get_invoice_number();
  validityDays.value = DEFAULT_VALIDITY_DAYS;
  dispatchDays.value = DEFAULT_DISPATCH_DAYS;
  customerBillTo.value = DEFAULT_CUSTOMER_BILL_TO;
  customerNotes.value = DEFAULT_CUSTOMER_NOTES;
  // Force a new array reference to ensure reactivity in EstimateItems
  selectedProducts.value = [];
  // Force nextTick to ensure the change is detected by child components
  nextTick(() => {
    // Double-ensure emptiness with a new reference in case the child didn't update
    selectedProducts.value = [];
  });
  switches.value = { ...DEFAULT_SWITCHES };
  selectedBankIndex.value = DEFAULT_BANK_INDEX;
  editableTerms.value = termsAndConditions.value;
  orderStats.value = null;
  // Hide the estimate output when creating a new estimate
  clearEstimate();
  console.log('Form reset to defaults completed');
};

// Order Stats State
const orderStats = ref<any>(null); // null means hidden

// Centralized function to update order stats
function updatedOrderStats() {
  let totalCost = 0;
  let totalPrice = 0;
  let totalTax = 0;
  let totalProfit = 0;
  let netProfitPAT = 0;
  let netProfitMargin = 0;

  selectedProducts.value.forEach(item => {
    const cost = Number(item.product?.cost ?? item.cost) || 0;
    const price = Number(item.product?.price ?? item.price) || 0;
    const qty = Number(item.quantity) || 0;
    const taxPerc = Number(item.product?.taxPercentage ?? item.taxPercentage) || 0;
    const itemTotal = price * qty;
    const itemCost = cost * qty;
    let itemTax = 0;
    if (switches.value.incTax) {
      itemTax = itemTotal - (itemTotal / (1 + taxPerc / 100));
    } else {
      itemTax = itemTotal * (taxPerc / 100);
    }
    totalCost += itemCost;
    totalPrice += itemTotal;
    totalTax += itemTax;
    totalProfit += (price - cost) * qty;
  });

  // Net Profit (PAT): sum of lineitems ({price - cost} / (1 + tax%))
  netProfitPAT = selectedProducts.value.reduce((sum, item) => {
    const price = Number(item.product?.price ?? item.price) || 0;
    const cost = Number(item.product?.cost ?? item.cost) || 0;
    const taxPerc = Number(item.product?.taxPercentage ?? item.taxPercentage) || 0;
    const qty = Number(item.quantity) || 0;
    // PAT for this line: (price - cost) / (1 + tax%) * qty
    return sum + ((price - cost) / (1 + taxPerc / 100)) * qty;
  }, 0);

  // Net Profit Margin: (PAT / Total Price) * 100
  netProfitMargin = totalPrice > 0 ? (netProfitPAT / totalPrice) * 100 : 0;

  orderStats.value = {
    totalCost,
    totalPrice,
    totalTax,
    totalProfit,
    netProfitPAT,
    netProfitMargin
  };
}

// Generate Estimate Logic - Updated to use editableTerms and update order stats
const generateEstimate = () => {
  updatedOrderStats();
  // Save current invoice number to localStorage
  localStorage.setItem('invoice_number', String(invoiceNumber.value));
  // Calculate totals directly to ensure up-to-date values
  let totalPrice = 0;
  let totalTax = 0;
  selectedProducts.value.forEach(item => {
    const price = Number(item.product?.price ?? item.price) || 0;
    const qty = Number(item.quantity) || 0;
    const taxPerc = Number(item.product?.taxPercentage ?? item.taxPercentage) || 0;
    const itemTotal = price * qty;
    let itemTax = 0;
    if (switches.value.incTax) {
      itemTax = itemTotal - (itemTotal / (1 + taxPerc / 100));
    } else {
      itemTax = itemTotal * (taxPerc / 100);
    }
    totalPrice += itemTotal;
    totalTax += itemTax;
  });
  const estimateData = {
    estimateNumber: `${invoicePrefix.value}${invoiceNumber.value.toString().padStart(3, '0')}`,
    documentType: selectedDocumentType.value, // Add document type to output
    date: new Date(estimateDate.value),    business: {
      name: companies.value[selectedCompanyIndex.value]?.name,
      address: companyAddress.value,
      gstin: companyAddress.value.match(/GSTIN:\s*([A-Z0-9]+)/)?.[1] || 'N/A'
    },
    customer: {
      billTo: customerBillTo.value,
      notes: customerNotes.value
    },
    items: selectedProducts.value.map(item => ({
      product: {
        id: item.product?.id || item.id,
        name: item.product?.name || item.name,
        description: item.product?.description || item.description,
        price: Number(item.product?.price ?? item.price) || 0, // Ensure number
        mrp: Number(item.product?.mrp ?? item.mrp) || 0,
        taxPercentage: Number(item.product?.taxPercentage ?? item.taxPercentage) || 0,
        unit: item.product?.unit || item.unit
      },
      quantity: Number(item.quantity) || 0,
      price: Number(item.product?.price ?? item.price) || 0, // Ensure number
      tax: ((Number(item.product?.price ?? item.price) || 0) * (Number(item.quantity) || 0)) * ((Number(item.product?.taxPercentage ?? item.taxPercentage) || 0) / 100),
      total: (Number(item.product?.price ?? item.price) || 0) * (Number(item.quantity) || 0),
      unit: item.product?.unit || item.unit,
      taxPercentage: Number(item.product?.taxPercentage ?? item.taxPercentage) || 0
    })),
    subtotal: totalPrice,
    totalTax: totalTax,
    grandTotal: switches.value.incTax ? totalPrice : totalPrice + totalTax,
    switches: { ...switches.value },
    validityDays: validityDays.value,
    dispatchDays: dispatchDays.value,
    termsAndConditions: editableTerms.value,
    bankDetails: selectedBankIndex.value > 0 && banks.value.length > 0 ? 
                 banks.value[selectedBankIndex.value - 1]?.details : null
  };
  setEstimate(estimateData);
  //console.log('Generated Estimate:', estimateData);
};

// If invoice_number is empty, always use DEFAULT_INVOICE_NUMBER
watch(invoiceNumber, (val) => {
  if (!val || isNaN(Number(val))) {
    invoiceNumber.value = DEFAULT_INVOICE_NUMBER;
  }
});

</script>

<template>
  <div class="card shadow rounded">
    <div class="card-body p-4">
      <h5 class="card-title mb-4">Estimate Details</h5>

      <!-- Generate Button -->
      <div class="text-start my-4">
        <button class="btn bg-primary fw-bold px-4 py-2" @click="resetForm">
          New Estimate
        </button>
      </div>

      <!-- Company & Document Details Fieldset -->
      <fieldset class="mb-3 p-2 border rounded">
        <legend class="float-none w-auto px-2 h6">Company Details</legend>        <div class="row mb-1">
          <div class="col-md-6 mb-3">
            <label for="companySelect" class="form-label">Company</label>
            <div v-if="loadingCompanies" class="alert alert-info py-1">Loading companies...</div>
            <select v-else id="companySelect" class="form-select" v-model="selectedCompanyIndex">
              <option v-for="(company, index) in companies" :key="company.name" :value="index">
                {{ company.name }}
              </option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="companyAddress" class="form-label">Company Address</label>
            <div v-if="loadingCompanies" class="alert alert-info py-1">Loading address...</div>
            <textarea v-else id="companyAddress" class="form-control" rows="4" :value="companyAddress" readonly></textarea>
          </div>
        </div>
      </fieldset>

      <!-- Customer Details Fieldset -->
      <fieldset class="mb-3 p-2 border rounded">
        <legend class="float-none w-auto px-2 h6">Customer Details</legend>
        <div class="row mb-1">
          <div class="col-md-6 mb-3">
            <label for="customerBillTo" class="form-label">Bill To</label>
            <textarea id="customerBillTo" class="form-control" rows="1" v-model="customerBillTo"></textarea>
          </div>
          <div class="col-md-6 mb-3">
            <label for="customerNotes" class="form-label">Notes</label>
            <textarea id="customerNotes" class="form-control" rows="1" v-model="customerNotes"></textarea>
          </div>
        </div>
      </fieldset>

      <!-- Document Options Fieldset -->
      <fieldset class="mb-4 p-2 border rounded">
        <legend class="float-none w-auto px-2 h6">Document Options</legend>
        <div class="row mb-1">
          <div class="col-md-2 mb-3">
            <label for="documentType" class="form-label">Document Type</label>
            <select id="documentType" class="form-select" v-model="selectedDocumentType">
              <option v-for="type in documentTypes" :key="type" :value="type">{{ type }}</option>
            </select>
          </div>
          <div class="col-md-2 mb-3">
            <label for="estimateDate" class="form-label">Date</label>
            <input type="date" id="estimateDate" class="form-control" v-model="estimateDate">
          </div>
          <div class="col-md-2 mb-3">
            <label for="validityDays" class="form-label">Validity (days)</label>
            <input type="number" id="validityDays" class="form-control" v-model.number="validityDays" min="1">
          </div>
          <div class="col-md-2 mb-3">
            <label for="dispatchDays" class="form-label">Dispatch (days)</label>
            <input type="text" id="dispatchDays" class="form-control" v-model="dispatchDays">
          </div>
          <div class="col-md-2 mb-3">
            <label for="invoicePrefix" class="form-label">Invoice Prefix</label>
            <input type="text" id="invoicePrefix" class="form-control" :value="invoicePrefix" readonly>
          </div>
          <div class="col-md-2 mb-3">
            <label for="invoiceNumber" class="form-label">Invoice Number</label>
            <input type="number" id="invoiceNumber" class="form-control" v-model.number="invoiceNumber" min="1">
          </div>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-center mt-2">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="incTaxSwitch" v-model="switches.incTax">
            <label class="form-check-label" for="incTaxSwitch">Inc. Tax</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="showTotalSwitch" v-model="switches.showTotal">
            <label class="form-check-label" for="showTotalSwitch">Show Total</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="freeDeliverySwitch" v-model="switches.freeDelivery">
            <label class="form-check-label" for="freeDeliverySwitch">Free Delivery</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="vrlLogisticsSwitch" v-model="switches.vrlLogistics">
            <label class="form-check-label" for="vrlLogisticsSwitch">VRL Logistics</label>
          </div>          <div class="form-group d-flex align-items-center mb-0" style="gap: 0.5rem;">
            <div v-if="loadingBanks" class="alert alert-info py-1">Loading banks...</div>
            <select v-else id="bankSelect" class="form-select" style="width: auto; min-width: 180px;" v-model="selectedBankIndex">
              <option :value="0">Select Bank</option>
              <option v-for="(bank, idx) in banks" :key="bank.name" :value="idx + 1">{{ bank.name }}</option>
            </select>
          </div>
        </div>
      </fieldset>      <!-- Estimate Items Component -->
      <EstimateItems
        :available-products="availableProducts"
        v-model:selectedProducts="selectedProducts"
        @add-product="handleAddProduct"
        @remove-product="removeProduct"
        @update-product="handleUpdateProduct"
      />

      <!-- Terms & Conditions Fieldset -->
      <fieldset class="mb-4 p-2 border rounded">
        <legend class="float-none w-auto px-2 h6">Terms & Conditions</legend>
        <textarea class="form-control" rows="8" v-model="editableTerms"></textarea>
      </fieldset>

      <!-- Generate Button -->
      <div class="text-start mt-4">
        <button class="btn bg-warning text-dark fw-bold px-4 py-2" @click="generateEstimate">
          Generate Estimate
        </button>
      </div>

      <!-- Order Summary Row -->
      <OrderStats v-if="orderStats" :orderStats="orderStats" />

    </div>
  </div>
</template>

<style scoped>
/* Add specific styles for FormCard if needed */
.card {
  background-color: var(--bs-card-bg, white); /* Ensure background respects theme */
}
textarea[readonly] {
  background-color: var(--bs-secondary-bg); /* Adjust readonly background for theme */
  cursor: default;
}
</style>