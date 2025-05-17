<script setup>
import { ref, computed, nextTick, watch, onMounted, onUpdated } from 'vue'
import { useProducts } from '~/composables/useProducts';

// Use the composable for products (future compatible)
const { products: availableProducts, loading, error } = useProducts();

const props = defineProps({
  selectedProducts: {
    type: Array,
    default: () => []
  }
});

const searchQuery = ref('')
const selectedItems = ref([])
const isDropdownVisible = ref(false)
const highlightedIndex = ref(-1)
const dropdownList = ref(null)

const filteredProducts = computed(() => {
  if (searchQuery.value.trim()) {
    const lowerQuery = searchQuery.value.toLowerCase();
    return availableProducts.value.filter(p =>
      p.name.toLowerCase().includes(lowerQuery) ||
      p.description.toLowerCase().includes(lowerQuery)
    );
  }
  // Return all products when there's no search text but dropdown needs to be shown
  return isDropdownVisible.value ? availableProducts.value : [];
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);
};

const showDropdown = () => {
  // Only show dropdown if there's search text
  if (searchQuery.value.trim()) {
    isDropdownVisible.value = true;
    highlightedIndex.value = -1;
  }
}

// Show dropdown only when user is typing something
const onInput = () => {
  // Show dropdown only when there's text in the input
  isDropdownVisible.value = searchQuery.value.trim() !== '';
};

const hideDropdown = () => {
  setTimeout(() => {
    isDropdownVisible.value = false;
  }, 200);
}

const addProduct = (product) => {
  let newProduct;
  if (!product) {
    // Add a blank product for custom entry
    newProduct = {
      product: {
        id: `custom_${Date.now()}`,
        name: '',
        description: '',
        mrp: 0,
        unit: 'nos',
        price: 1,
        taxPercentage: 18
      },
      quantity: 1,
      cost: 0 // Only cost at root
    };
  } else {
    newProduct = {
      product: { ...product },
      quantity: 1,
      cost: product.cost // Only cost at root
    };
  }
  selectedItems.value.push(newProduct);
  emitUpdate();
  searchQuery.value = '';
  highlightedIndex.value = -1;
  isDropdownVisible.value = false;
}

const removeProduct = (productId) => {
  selectedItems.value = selectedItems.value.filter(item => item.product.id !== productId);
  emitUpdate();
}

const updateItem = () => {
  selectedItems.value.forEach(item => {
    item.quantity = Number(item.quantity) || 1;
    item.product.price = Number(item.product.price) || 0;
    item.product.taxPercentage = Number(item.product.taxPercentage) || 0;
  });
  emitUpdate();
}

const moveItemUp = (index) => {
  if (index > 0) {
    const temp = selectedItems.value[index];
    selectedItems.value[index] = selectedItems.value[index - 1];
    selectedItems.value[index - 1] = temp;
    emitUpdate();
  }
}

const moveItemDown = (index) => {
  if (index < selectedItems.value.length - 1) {
    const temp = selectedItems.value[index];
    selectedItems.value[index] = selectedItems.value[index + 1];
    selectedItems.value[index + 1] = temp;
    emitUpdate();
  }
}

const emit = defineEmits(['update:selectedProducts']);
const emitUpdate = () => {
  emit('update:selectedProducts', selectedItems.value);
}

const scrollToHighlighted = async () => {
  await nextTick();
  if (!dropdownList.value || highlightedIndex.value < 0) return;

  const list = dropdownList.value;
  const highlightedElement = list.children[highlightedIndex.value];

  if (highlightedElement) {
    const listRect = list.getBoundingClientRect();
    const elementRect = highlightedElement.getBoundingClientRect();

    if (elementRect.bottom > listRect.bottom) {
      list.scrollTop += elementRect.bottom - listRect.bottom;
    } else if (elementRect.top < listRect.top) {
      list.scrollTop -= listRect.top - elementRect.top;
    }
  }
}

const navigateDown = () => {
  if (!isDropdownVisible.value) {
    // Show dropdown regardless of search text when pressing down arrow
    isDropdownVisible.value = true;
    highlightedIndex.value = -1;
  }
  if (availableProducts.value.length > 0) {
    highlightedIndex.value = (highlightedIndex.value + 1) % availableProducts.value.length;
    scrollToHighlighted();
  }
}

const navigateUp = () => {
  if (!isDropdownVisible.value) return;
  if (availableProducts.value.length > 0) {
    highlightedIndex.value = (highlightedIndex.value - 1 + availableProducts.value.length) % availableProducts.value.length;
    scrollToHighlighted();
  }
}

const selectHighlighted = () => {
  if (isDropdownVisible.value && highlightedIndex.value >= 0 && highlightedIndex.value < filteredProducts.value.length) {
    // Add the highlighted product regardless of whether there's search text or not
    addProduct(filteredProducts.value[highlightedIndex.value]);
  } else {
    // Add a blank row for custom product only if no item is highlighted
    addProduct();
  }
}

const selectItem = (product) => {
  addProduct(product);
}

const totalTax = computed(() => {
  return selectedItems.value.reduce((sum, item) => {
    const price = Number(item.product.price) || 0;
    const quantity = Number(item.quantity) || 0;
    const taxPercentage = Number(item.product.taxPercentage) || 0;
    const itemTotal = price * quantity;
    const taxAmount = itemTotal * (taxPercentage / 100);
    return sum + taxAmount;
  }, 0);
});

const grandTotal = computed(() => {
  let total = 0;
  selectedItems.value.forEach(item => {
    const price = Number(item.product.price) || 0;
    const quantity = Number(item.quantity) || 0;
    const taxPercentage = Number(item.product.taxPercentage) || 0;
    const itemTotal = price * quantity;
    total += itemTotal + (itemTotal * (taxPercentage / 100));
  });
  return total;
});

// Sync selectedItems with selectedProducts prop
watch(() => props.selectedProducts, (newVal) => {  // Always force clear if parent clears
  if (Array.isArray(newVal) && newVal.length === 0) {
    selectedItems.value = [];
    console.log('EstimateItems: Cleared selectedItems');
    return;
  }
  
  // Only update if different to avoid infinite loop
  const isDifferent = JSON.stringify(selectedItems.value) !== JSON.stringify(newVal);
  if (isDifferent) {
    selectedItems.value = Array.isArray(newVal) ? JSON.parse(JSON.stringify(newVal)) : [];
    console.log('EstimateItems: Updated selectedItems from prop');
  }
}, { immediate: true, deep: true });

// Add evalFormula for arithmetic input
function evalFormula(event, index, key) {
  const txt = event.target.value;
  let value = 1;
  try {
    value = eval(txt);
    if (typeof value !== 'number' || isNaN(value)) value = 1;
  } catch {
    value = 1;
  }

  value = Math.round(value * 100) / 100;

  if (["mrp", "price", "taxPercentage"].includes(key)) {
    selectedItems.value[index].product[key] = value;
  } else if (["quantity", "cost", "unit"].includes(key)) {
    selectedItems.value[index][key] = value;
  }
  emitUpdate();
}

</script>

<template>
  <fieldset class="mb-3">
    <div class="position-relative">

      <div class="input-group mb-3">
        <input
          type="text"
          class="form-control border border-warning"
          id="productSearch"
          v-model="searchQuery"
          placeholder="Search or Type products name..."
          autocomplete="off"
          autofocus=""
          @focus="showDropdown"
          @blur="hideDropdown"
          @keydown.down.prevent="navigateDown"
          @keydown.up.prevent="navigateUp"
          @keydown.enter.prevent="selectHighlighted"
          @input="onInput"
        >
        <button class="btn btn-warning" type="button" @click="selectHighlighted">
          <i class="bi bi-plus-circle"></i>
        </button>
      </div>      <div
        v-if="isDropdownVisible && filteredProducts.length > 0"
        ref="dropdownList"
        class="list-group position-absolute w-100 bg-white border rounded shadow-sm mt-n2"
        style="max-height: 250px; overflow-y: auto; z-index: 1000;"
      >
        <button
          type="button"
          class="list-group-item list-group-item-action"
          v-for="(product, index) in filteredProducts"
          :key="product.id"
          :class="{ 'active': index === highlightedIndex }"
          @mousedown.prevent
          @click="selectItem(product)"
        >
          <strong>{{ product.name }}</strong> ({{ formatCurrency(product.price) }}) <br>
          <small>{{ product.description }}</small>
        </button>
      </div>      <div v-if="isDropdownVisible && searchQuery.trim() && filteredProducts.length === 0" class="alert alert-warning py-2 position-absolute w-100 mt-n2" style="z-index: 1000;">
        No products found matching "{{ searchQuery }}".
      </div>
    </div>

    <div v-if="selectedItems.length > 0">
      <div>
        <div v-for="(item, index) in selectedItems" :key="item.product.id" class="d-flex flex-column flex-md-row mb-3 border-bottom">
          <div class="d-flex flex-row flex-md-row-reverse flex-fill align-items-start">
            <div class="d-flex flex-column mb-3 flex-fill">
              <input type="text" class="form-control" v-model="item.product.name" autocomplete="off" required>
              <textarea type="text" class="form-control" v-model="item.product.description" placeholder="Description.." rows="1"></textarea>
            </div>
            <div class="d-flex flex-column align-items-start ms-2 mt-2 mt-md-0" style="min-width:32px;">
              <button type="button" class="btn btn-link p-0 text-danger mb-1" @click="removeProduct(item.product.id)"><i title="Remove item" class="bi bi-x-circle fs-5"></i></button>
              <div class="d-flex flex-row gap-1">
                <button type="button" class="btn btn-link p-0" :disabled="index === 0" @click="moveItemUp(index)"><i class="bi bi-arrow-up-circle fs-5"></i></button>
                <button type="button" class="btn btn-link p-0" :disabled="index === selectedItems.length - 1" @click="moveItemDown(index)"><i class="bi bi-arrow-down-circle fs-5"></i></button>
              </div>
            </div>
          </div>
          <div class="d-flex flex-row flex-wrap">
            <div class="ms-md-2">
              <div class="input-group mb-1" style="width: 150px;">
                <span class="input-group-text fw-semibold">Qty</span>
                <input class="form-control" type="text" v-model="item.quantity" min="1" @keydown.enter.prevent="evalFormula($event, index, 'quantity')" @change="updateItem">
              </div>
              <div class="input-group mb-1" style="width: 150px;">
                <span class="input-group-text fw-semibold">Unit</span>
                <select class="form-select" v-model="item.product.unit">
                  <option value="nos">nos</option>
                  <option value="pcs">pcs</option>
                  <option value="box">box</option>
                  <option value="pack">pack</option>
                  <option value="rolls">rolls</option>
                  <option value="sqft">sqft</option>
                  <option value="ft">ft</option>
                  <option value="kg">kg</option>
                  <option value="liter">liter</option>
                  <option value="ton">ton</option>
                  <option value="panel">panel</option>
                </select>
              </div>
            </div>
            <div class="ms-md-2">
              <div class="input-group mb-1" style="width: 150px;">
                <span class="input-group-text fw-semibold">MRP</span>
                <input class="form-control" type="text" v-model="item.product.mrp" @keydown.enter.prevent="evalFormula($event, index, 'mrp')">
              </div>
              <div class="input-group mb-3" style="width: 150px;">
                <span class="input-group-text fw-semibold">Rate</span>
                <input class="form-control" type="text" v-model="item.product.price" @keydown.enter.prevent="evalFormula($event, index, 'price')" @change="updateItem">
              </div>
            </div>
            <div class="ms-md-2">
              <div class="input-group mb-1" style="width: 130px;">
                <input class="form-control" type="text" v-model="item.cost" :placeholder="'Cost'" min="0" step="0.01" @keydown.enter.prevent="evalFormula($event, index, 'cost')">
              </div>
              <div class="input-group mb-1" style="width: 130px;">
                <span class="input-group-text fw-semibold">Tax</span>
                <select class="form-select" v-model.number="item.product.taxPercentage" @change="updateItem">
                  <option value="0">0</option>
                  <option value="5">5</option>
                  <option value="12">12</option>
                  <option value="18">18</option>
                  <option value="28">28</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="alert alert-info py-2 mt-3">
      No products added yet. Use the search above to find and add products.
    </div>
  </fieldset>
</template>