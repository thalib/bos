<template>
  <div class="container-fluid py-4">
    <div id="estForm" class="container-fluid mb-4">
      <FormCard />
    </div>
    <div v-if="showOutput" id="estOutput" class="container" style="max-width: 850px;">
      <OutputCard />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useEstimate } from '~/composables/useEstimate';
import FormCard from '~/components/estimate/FormCard.vue';
import OutputCard from '~/components/estimate/OutputCard.vue';
const { showOutput } = useEstimate();

definePageMeta({ middleware: 'auth' });
</script>

<style scoped>
/* Add any scoped styles for the Estimate Maker page here */
</style>