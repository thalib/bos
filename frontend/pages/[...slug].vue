<template>
  <!-- This page catches all unmatched routes and redirects to 404 -->
  <div class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="text-center">
      <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted">Redirecting...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

/**
 * Catch-all route handler for unmatched routes
 * This file catches any route that doesn't match existing pages
 * and redirects to the custom 404 page
 */

// Define custom page meta to avoid route name conflicts
definePageMeta({
  name: 'catch-all-404'
});

// Get the current route
const route = useRoute();

// Set metadata to prevent indexing
useHead({
  title: 'Redirecting... | Thanzil',
  meta: [
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ]
});

// Redirect to 404 page immediately when component mounts
onMounted(async () => {
  try {
    await navigateTo('/404', {
      replace: true // Replace the current history entry to prevent back navigation issues
    });
  } catch (error) {
    console.error('Failed to redirect to 404 page:', error);
    // Fallback: try to navigate to home
    try {
      await navigateTo('/', { replace: true });
    } catch (fallbackError) {
      console.error('Failed to navigate to home as fallback:', fallbackError);
    }
  }
});
</script>
