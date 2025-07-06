<!--
  Global Loading Overlay Component
  Displays loading indicators with consistent Bootstrap 5.3 styling
-->
<template>
  <!-- Global Loading Overlay - only render on client -->
  <Teleport to="body" v-if="isClient">
    <div
      v-if="shouldShowOverlay"
      class="global-loading-overlay position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
      :class="overlayClasses"
      style="z-index: 9999;"
      role="dialog"
      aria-live="polite"
      aria-label="Loading"
    >
      <div class="text-center">
        <!-- Loading Spinner -->
        <div 
          class="spinner-border mb-3"
          :class="spinnerClasses"
          role="status"
          style="width: 3rem; height: 3rem;"
        >
          <span class="visually-hidden">Loading...</span>
        </div>
        
        <!-- Loading Message -->
        <h4 class="mb-2" :class="textClasses">
          {{ loadingMessage }}
        </h4>
        
        <!-- Sub-message for specific contexts -->
        <p class="mb-0" :class="subTextClasses">
          {{ subMessage }}
        </p>
        
        <!-- Progress indicator for route transitions -->
        <div v-if="showProgress" class="mt-3">
          <div class="progress" style="height: 4px; width: 200px;">
            <div 
              class="progress-bar progress-bar-striped progress-bar-animated"
              :class="progressClasses"
              role="progressbar"
              style="width: 100%"
              aria-valuenow="100"
              aria-valuemin="0"
              aria-valuemax="100"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useAppLoading } from '~/composables/useAppLoading'

// Client-side rendering flag to prevent hydration mismatches
const isClient = ref(false)

// Get global loading state
const { 
  isLoading, 
  currentLoadingState, 
  isAuthLoading, 
  isRouteLoading 
} = useAppLoading()

// Determine if overlay should be shown - only on client
const shouldShowOverlay = computed(() => {
  return isClient.value && isLoading.value && currentLoadingState.value !== null
})

// Set client flag on mount
onMounted(() => {
  isClient.value = true
})

// Loading message from current state
const loadingMessage = computed(() => {
  return currentLoadingState.value?.message || 'Loading...'
})

// Sub-message based on loading type
const subMessage = computed(() => {
  if (!currentLoadingState.value) return ''
  
  switch (currentLoadingState.value.type) {
    case 'auth':
      return 'Please wait while we verify your session'
    case 'route':
      return 'Preparing your page content'
    case 'page':
      return 'Fetching the latest data'
    case 'component':
      return 'Loading interface elements'
    case 'global':
      return 'Initializing application'
    default:
      return 'This will only take a moment'
  }
})

// Show progress bar for route transitions
const showProgress = computed(() => {
  return isRouteLoading.value
})

// Dynamic overlay classes based on loading type
const overlayClasses = computed(() => {
  if (!currentLoadingState.value) return 'bg-black bg-opacity-50'
  
  switch (currentLoadingState.value.type) {
    case 'auth':
      return 'bg-primary bg-opacity-10 backdrop-blur'
    case 'route':
      return 'bg-success bg-opacity-10 backdrop-blur'
    case 'page':
      return 'bg-info bg-opacity-10 backdrop-blur'
    case 'component':
      return 'bg-secondary bg-opacity-10 backdrop-blur'
    default:
      return 'bg-black bg-opacity-50'
  }
})

// Dynamic spinner classes based on loading type
const spinnerClasses = computed(() => {
  if (!currentLoadingState.value) return 'text-primary'
  
  switch (currentLoadingState.value.type) {
    case 'auth':
      return 'text-primary'
    case 'route':
      return 'text-success'
    case 'page':
      return 'text-info'
    case 'component':
      return 'text-secondary'
    default:
      return 'text-primary'
  }
})

// Dynamic text classes based on loading type
const textClasses = computed(() => {
  if (!currentLoadingState.value) return 'text-dark'
  
  switch (currentLoadingState.value.type) {
    case 'auth':
      return 'text-primary'
    case 'route':
      return 'text-success'
    case 'page':
      return 'text-info'
    case 'component':
      return 'text-secondary'
    default:
      return 'text-dark'
  }
})

// Sub-text classes (muted)
const subTextClasses = computed(() => {
  return 'text-muted small'
})

// Progress bar classes
const progressClasses = computed(() => {
  if (!currentLoadingState.value) return 'bg-primary'
  
  switch (currentLoadingState.value.type) {
    case 'auth':
      return 'bg-primary'
    case 'route':
      return 'bg-success'
    case 'page':
      return 'bg-info'
    case 'component':
      return 'bg-secondary'
    default:
      return 'bg-primary'
  }
})
</script>

<style scoped>
.global-loading-overlay {
  /* Backdrop blur effect for modern browsers */
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
}

/* Smooth entrance animation */
.global-loading-overlay {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Ensure overlay is above all content */
.global-loading-overlay {
  pointer-events: auto;
}

/* Responsive adjustments */
@media (max-width: 576px) {
  .global-loading-overlay h4 {
    font-size: 1.1rem;
  }
  
  .global-loading-overlay .spinner-border {
    width: 2.5rem !important;
    height: 2.5rem !important;
  }
}
</style>
