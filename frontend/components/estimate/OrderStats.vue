<template>
  <div v-if="orderStats" class="d-flex flex-wrap gap-4 mt-4 alert alert-info">
    <div class="fw-semibold">Cost: {{ formatCurrency(orderStats.totalCost) }}</div>
    <div class="fw-semibold">Total: {{ formatCurrency(orderStats.totalPrice) }}</div>
    <div class="fw-semibold">GST: {{ formatCurrency(orderStats.totalTax) }}</div>
    <div class="fw-semibold">Profit: {{ formatCurrency(orderStats.totalProfit) }}</div>
    <div class="fw-semibold">Net Profit (PAT): {{ formatCurrency(orderStats.netProfitPAT) }}</div>
    <div class="fw-semibold">Net Profit Margin: {{ orderStats.netProfitMargin.toFixed(2) }}%</div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps({
  orderStats: {
    type: Object,
    required: true
  }
});

function formatCurrency(value: number) {
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);
}
</script>
