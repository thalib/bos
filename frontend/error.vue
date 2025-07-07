<template>
  <div class="d-flex justify-content-center align-items-center min-vh-100 p-3 bg-light">
    <div class="card shadow border-0 text-center p-4 mx-auto" style="max-width: 500px;">
      <div class="card-body">
        <div class="mb-4">
          <i class="bi bi-exclamation-triangle-fill text-warning display-1"></i>
        </div>
        <h1 class="fw-bold mb-3">{{ error?.statusCode || 'Error' }}</h1>
        <h2 class="mb-4">
          {{ error?.statusCode === 404 ? 'Page Not Found' : (error?.statusMessage || 'Something went wrong') }}
        </h2>
        <p class="text-muted mb-4">
          {{ error?.statusCode === 404 
              ? 'The page you are looking for does not exist or has been moved.' 
              : (error?.message || 'An unexpected error occurred while processing your request.') 
          }}
        </p>
        <button
          class="btn btn-primary px-4 py-2"
          @click="handleError"
        >
          Back to Home
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app'

// Define props according to Nuxt 3 documentation
const props = defineProps({
  error: Object as () => NuxtError
})

// Clear the error and redirect to home page
const handleError = () => {
  clearError({ redirect: '/' });
};
</script>
