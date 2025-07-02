<template>
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <div class="alert alert-info d-flex align-items-center" role="alert">
          <i class="bi bi-info-circle me-3 fs-4"></i>
          <div>
            <h4 class="alert-heading mb-1">{{ pageTitle }} - Coming Soon</h4>
            <p class="mb-0">This page is under development. Content for "{{ pageType }}" will be available soon.</p>
          </div>
        </div>
        
        <div class="card">
          <div class="card-body text-center py-5">
            <i v-if="pageIcon" :class="pageIcon" class="display-1 text-muted mb-3"></i>
            <h3 class="text-muted">{{ pageTitle }}</h3>
            <p class="text-muted">Page functionality will be implemented here</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  pageTitle?: string
  pageType?: string
  pageIcon?: string
}

const props = withDefaults(defineProps<Props>(), {
  pageTitle: 'Page',
  pageType: 'content',
  pageIcon: 'bi-file-earmark'
})
</script>
