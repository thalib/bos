<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useEstimate } from '~/composables/useEstimate';

// Use the composable for shared state
const { showOutput } = useEstimate();

// State for dark mode - default to true
const isDarkMode = ref(true);

// Function to toggle dark mode
const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  updateHtmlAttribute();
};

// Function to update the data-bs-theme attribute on the <html> element
const updateHtmlAttribute = () => {
  if (process.client) { // Ensure this runs only on the client-side
    document.documentElement.setAttribute('data-bs-theme', isDarkMode.value ? 'dark' : 'light');
  }
};

// Apply the theme on component mount
onMounted(() => {
  // No need to check preference here anymore, just apply the default
  updateHtmlAttribute();
});

// Computed class for the main container based on the theme
const containerClass = computed(() => {
  return isDarkMode.value ? 'bg-dark text-light' : 'bg-light-violet'; // Use a custom class for violet
});

</script>

<template>
  <div :class="containerClass" style="min-height: 100vh;">
    <!-- Navbar Component wrapped in client-only to prevent hydration mismatch -->
    <client-only>
      <Navbar @toggle-dark-mode="toggleDarkMode" :is-dark-mode="isDarkMode" />
    </client-only>

    <div id="app" class="pt-3 pb-5">
      <NuxtPage />
    </div>
  </div>
</template>

<style>
/* Add Bootstrap and Bootstrap Icons CSS - Assuming they are configured in nuxt.config.ts or globally */

/* Custom class for light mode violet background */
/* .bg-light-violet {
  background-color: #8A2BE2; 
} */

/* Ensure content visibility in both modes */
body {
  transition: background-color 0.3s ease, color 0.3s ease;
  /* Remove violet background for light mode */
  /* background-color: #8A2BE2; */
}

/* Basic styling adjustments if needed */
#app {
  /* Add any global app styling here */
}
</style>
