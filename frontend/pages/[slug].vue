<template>
  <div class="min-vh-100">
    <!-- Page Loading State -->
    <div v-if="pending" class="d-flex justify-content-center align-items-center min-vh-100">
      <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted">Loading page...</p>
      </div>
    </div>

    <!-- Page Not Found -->
    <div v-else-if="!pageConfig" class="d-flex justify-content-center align-items-center min-vh-100">
      <div class="text-center">
        <i class="bi bi-exclamation-triangle-fill text-warning display-1 mb-3"></i>
        <h1 class="h3 mb-3">Page Not Found</h1>
        <p class="text-muted mb-4">The page "{{ route.params.slug }}" you're looking for doesn't exist.</p>
        <NuxtLink to="/" class="btn btn-primary">
          <i class="bi bi-house me-2"></i>
          Go Home
        </NuxtLink>
      </div>
    </div>

    <!-- Dynamic Content -->
    <div v-else class="container-fluid">
      <!-- Page Header -->
      <div class="row">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center">
              <i v-if="pageConfig.icon" :class="pageConfig.icon" class="me-2 fs-4 text-primary"></i>
              <div>
                <h1 class="h3 mb-0">{{ pageConfig.title }}</h1>
                <p class="text-muted mb-0 small">{{ pageConfig.description }}</p>
              </div>
            </div>
            <div class="text-muted small">
              Route: /{{ pageConfig.slug }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Dynamic Component Content -->
      <div class="row mt-3">
        <div class="col-12">
          <ContentDefaultPageContent 
            :page-title="pageConfig.title"
            :page-type="pageConfig.slug"
            :page-icon="pageConfig.icon"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePageConfig } from '~/composables/usePageConfig'

// Get route parameters
const route = useRoute()
const slug = computed(() => route.params.slug as string)

// Get page configuration
const { getPageConfig, isValidPage } = usePageConfig()
const pageConfig = computed(() => getPageConfig(slug.value))

// Validate page and set up data
const { pending, error } = await useLazyAsyncData(`page-${slug.value}`, async () => {
  // Validate the page exists
  if (!isValidPage(slug.value)) {
    throw createError({
      statusCode: 404,
      statusMessage: `Page "${slug.value}" not found`
    })
  }
  
  return {
    config: pageConfig.value
  }
})

// Set page middleware (must be called at top level)
definePageMeta({
  middleware: 'auth'
})
</script>

<style scoped>
/* Only use custom CSS when absolutely necessary - responsive flexbox fix */
@media (max-width: 768px) {
  .d-flex.align-items-center.justify-content-between {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 0.5rem;
  }
}
</style>
