<template>
  <div class="min-vh-100">
    <!-- Initial Authentication & Page Loading State -->
    <div v-if="!canShowPageContent" class="d-flex justify-content-center align-items-center min-vh-100">
      <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted">{{ pageLoadingMessage }}</p>
      </div>
    </div>

    <!-- Page Not Found -->
    <div v-else-if="pageConfig === null" class="d-flex justify-content-center align-items-center min-vh-100">
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

    <!-- Page Structure (loads first) -->
    <div v-else-if="pageConfig" class="container-fluid">
      <!-- Page Header (renders immediately after auth) -->
      <div class="row">
        <div class="col-12">
          <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
            <div class="d-flex align-items-center">
              <!-- Skeleton loading for icon -->
              <div v-if="!canShowComponentContent" class="placeholder-glow me-2">
                <div class="placeholder rounded" style="width: 24px; height: 24px;"></div>
              </div>
              <i v-else-if="pageConfig?.icon" :class="pageConfig.icon" class="me-2 fs-4 text-primary"></i>
              
              <div>
                <!-- Title skeleton or actual title -->
                <div v-if="!canShowComponentContent" class="placeholder-glow">
                  <h1 class="h3 mb-0 placeholder col-6" style="height: 2rem;"></h1>
                  <p class="text-muted mb-0 small placeholder col-8" style="height: 1rem;"></p>
                </div>
                <div v-else>
                  <h1 class="h3 mb-0">{{ pageConfig?.title }}</h1>
                  <p class="text-muted mb-0 small">{{ pageConfig?.description }}</p>
                </div>
              </div>
            </div>
            <div v-if="canShowComponentContent && pageConfig" class="text-muted small">
              Route: /{{ pageConfig?.slug }}
            </div>
            <div v-else class="placeholder-glow">
              <div class="placeholder col-12" style="width: 120px; height: 1rem;"></div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Component Loading State -->
      <div v-if="!canShowComponentContent" class="row mt-3">
        <div class="col-12">
          <div class="text-center py-5">
            <div class="spinner-border text-secondary mb-3" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Loading page content...</p>
          </div>
        </div>
      </div>

      <!-- Dynamic Component Content (loads after page structure) -->
      <div v-else-if="pageConfig" class="row mt-3">
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
import { computed, ref, onMounted } from 'vue'
import { usePageConfig } from '~/composables/usePageConfig'
import { useAppLoading } from '~/composables/useAppLoading'
import { usePageLoading } from '~/composables/usePageLoading'
import { useToast } from '~/utils/errorHandling'

// Get route parameters
const route = useRoute()
const slug = computed(() => route.params.slug as string)

// Initialize loading helpers
const { canShowComponentContent } = useAppLoading()
const { withLoading } = usePageLoading()
const { showErrorToast } = useToast()

// Page loading state management
const isPageReady = ref(false)
const canShowPageContent = computed(() => isPageReady.value)
const pageLoadingMessage = ref('Authenticating...')

// Get page configuration and navigation
const { getPageConfig, isValidPage } = usePageConfig()
const { fetchMenuItems, isLoading: navLoading } = useNavigation()

// Check if page is valid and set up pageConfig reactively
const pageConfig = computed(() => {
  // Don't check validity until navigation is loaded
  if (navLoading.value) {
    return undefined // undefined means still loading
  }
  
  if (!isValidPage(slug.value)) {
    return null // null means page not found
  }
  return getPageConfig(slug.value)
})

// Validate page and set up data with proper loading sequence
const { pending, error } = await useLazyAsyncData(`page-${slug.value}`, async () => {
  // Update loading message
  pageLoadingMessage.value = 'Loading navigation...'
  
  // Ensure navigation is loaded first
  await fetchMenuItems()
  
  pageLoadingMessage.value = 'Loading page configuration...'
  
  // Validate the page exists
  if (!isValidPage(slug.value)) {
    return null // Return null instead of throwing error to allow component to render 404
  }
  
  return {
    config: pageConfig.value
  }
})

// Handle initialization sequence
onMounted(async () => {
  try {
    // Wait for page data to be ready
    if (pending.value) {
      pageLoadingMessage.value = 'Loading page...'
      await new Promise(resolve => {
        const checkPending = () => {
          if (!pending.value) {
            resolve(void 0)
          } else {
            setTimeout(checkPending, 50)
          }
        }
        checkPending()
      })
    }

    // Check for errors
    if (error.value) {
      showErrorToast(`Failed to load page: ${error.value.message || 'Unknown error'}`)
      return
    }

    // Page structure is ready
    pageLoadingMessage.value = 'Preparing content...'
    isPageReady.value = true

    // Small delay to show page structure before components
    await new Promise(resolve => setTimeout(resolve, 150))
    
  } catch (err) {
    console.error('Page initialization error:', err)
    showErrorToast('Failed to initialize page')
  }
})

// Set page middleware (must be called at top level)
definePageMeta({
  middleware: 'auth',
  name: 'single-dynamic-slug-page'
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
