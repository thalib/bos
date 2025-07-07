<!--
  Global Loading Overlay Component
  Simple loading indicator with text and spinner
-->
<template>
  <!-- Global Loading Overlay - only render on client -->
  <Teleport to="body" v-if="isClient">
    <div
      v-if="shouldShowOverlay"
      class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light bg-opacity-75"
      style="z-index: 9999; transition: opacity 0.15s ease-in-out;"
    >
      <div class="d-flex align-items-center">
        <div class="spinner-border spinner-border-sm me-2 text-secondary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <span class="text-secondary">{{ loadingMessage }}</span>
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
const { isLoading, currentLoadingState } = useAppLoading()

// Determine if overlay should be shown - only for critical loading states
const shouldShowOverlay = computed(() => {
  if (!isClient.value || !currentLoadingState.value) return false
  
  // Only show overlay for auth and route loading (critical states)
  // Page and component loading should use inline indicators
  const criticalTypes = ['auth', 'route']
  return criticalTypes.includes(currentLoadingState.value.type)
})

// Set client flag on mount
onMounted(() => {
  isClient.value = true
})

// Simple loading message
const loadingMessage = computed(() => {
  return currentLoadingState.value?.message || 'Loading...'
})
</script>


