<template>
  <div>
    <!-- Global Loading Overlay -->
    <ClientOnly>
      <GlobalLoadingOverlay />
    </ClientOnly>

    <!-- Main App Content -->
    <div id="app-container" class="min-vh-100" :class="appClasses">
      <!-- Navbar - only show on valid pages and when authenticated -->
      <Navbar 
        v-if="shouldShowNavbar"
        :is-dark-mode="isDarkMode"
        @toggle-dark-mode="toggleDarkMode"
      />
      
      <!-- Main Content Area -->
      <main 
        :class="mainContentClasses"
        role="main"
      >
        <!-- Page Content Slot -->
        <slot />
      </main>
    </div>

    <!-- Toast Notifications -->
    <ClientOnly>
      <Toast />
    </ClientOnly>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute } from '#app';
import { useAuth } from '~/composables/useAuth';
import Navbar from '~/components/Navbar.vue';
import GlobalLoadingOverlay from '~/components/GlobalLoadingOverlay.vue';
import Toast from '~/components/Toast.vue';

// Route and auth state
const route = useRoute();
const { isAuthenticated, isInitialized } = useAuth();

// UI state
const isDarkMode = ref(true);
const isClient = ref(false);

// Pages that should not show the navbar
const noNavbarPages = [
  '/',      // Login page
  '/404',   // Error page
  '/error'  // Fallback error page
];

// Computed properties
const shouldShowNavbar = computed(() => {
  return isClient.value && 
         isInitialized.value && 
         isAuthenticated.value && 
         !noNavbarPages.includes(route.path);
});

const appClasses = computed(() => ({
  'bg-dark': isDarkMode.value,
  'bg-light': !isDarkMode.value,
  'text-light': isDarkMode.value,
  'text-dark': !isDarkMode.value
}));

const mainContentClasses = computed(() => ({
  'pt-0': !shouldShowNavbar.value, // No padding if no navbar
  'pt-4': shouldShowNavbar.value   // Add padding for navbar
}));

// Methods
const toggleDarkMode = (): void => {
  isDarkMode.value = !isDarkMode.value;
  
  // Save preference to localStorage
  if (typeof window !== 'undefined') {
    localStorage.setItem('darkMode', isDarkMode.value.toString());
  }
};

// Load saved preferences
onMounted(() => {
  isClient.value = true;
  
  // Load dark mode preference
  if (typeof window !== 'undefined') {
    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode !== null) {
      isDarkMode.value = savedDarkMode === 'true';
    }
  }
});

// Set layout metadata
useHead({
  htmlAttrs: {
    lang: 'en',
    class: computed(() => isDarkMode.value ? 'dark-theme' : 'light-theme')
  },
  bodyAttrs: {
    class: computed(() => [
      isDarkMode.value ? 'bg-dark text-light' : 'bg-light text-dark',
      'overflow-x-hidden' // Prevent horizontal scroll
    ].join(' '))
  }
});

// Error boundary for layout
onErrorCaptured((error) => {
  console.error('Layout Error:', error);
  return false; // Don't prevent error propagation
});
</script>

<style scoped>
#app-container {
  transition: background-color 0.3s ease, color 0.3s ease;
}

main {
  transition: padding-top 0.3s ease;
}
</style>
