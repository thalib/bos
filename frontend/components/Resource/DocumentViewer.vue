<template>
  <div class="d-flex justify-content-center align-items-center h-100 p-3">
    <div 
      class="document-viewer bg-white border border-secondary-subtle shadow d-flex align-items-center justify-content-center rounded-2"
      style="width: 595px; height: 842px; max-width: 90vw; max-height: 80vh;"
    >
      <div class="text-center p-4">
        <i class="bi bi-file-earmark-text display-1 text-secondary mb-4"></i>
        <h4 class="text-secondary fw-semibold mb-3">Feature not implemented</h4>
        <p class="text-muted mb-0 fs-6">Document viewing will be available soon</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// No props needed for this placeholder component
</script>

<style scoped>
/* Maintain A4 aspect ratio and responsive behavior */
@media (max-width: 768px) {
  .document-viewer {
    aspect-ratio: 595 / 842;
    height: auto !important;
    max-width: 95vw !important;
    max-height: 85vh !important;
  }
}

@media (max-width: 576px) {
  .document-viewer {
    max-width: 98vw !important;
    max-height: 90vh !important;
  }
}
</style>
