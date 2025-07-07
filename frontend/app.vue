<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute } from '#app';
import { useAuth } from '~/composables/useAuth';
import { useToast } from '~/utils/errorHandling';
import LoginForm from '~/components/LoginForm.vue';
import Navbar from '~/components/Navbar.vue';
import GlobalLoadingOverlay from '~/components/GlobalLoadingOverlay.vue';
import Toast from '~/components/Toast.vue';

// Authentication state
const { isAuthenticated, isInitialized, user } = useAuth();

// Toast notifications
const { showErrorToast, showSuccessToast } = useToast();

// Get current route
const route = useRoute();

// State for dark mode - default to true
const isDarkMode = ref(true);

// Client-side rendering flag to prevent hydration mismatches
const isClient = ref(false);

// Simple computed for showing different content states
const showMainApp = computed(() => {
  // Only show main app for authenticated users on non-error pages
  return isClient.value && isInitialized.value && isAuthenticated.value;
});

const showLoginForm = computed(() => {
  return isClient.value && isInitialized.value && !isAuthenticated.value && route.path !== '/';
});

const showHomePage = computed(() => {
  return isClient.value && isInitialized.value && route.path === '/';
});

const showInitialLoading = computed(() => {
  return !isClient.value || !isInitialized.value;
});

// Watch for authentication state changes and provide feedback
watch(isAuthenticated, (newValue, oldValue) => {
  // Only show notifications after initial load and when auth state actually changes
  if (isClient.value && isInitialized.value && oldValue !== undefined) {
    if (newValue && !oldValue) {
      showSuccessToast(`Welcome back, ${user.value?.name || 'User'}!`);
    } else if (!newValue && oldValue) {
      showSuccessToast('You have been logged out successfully.');
    }
  }
}, { immediate: false });

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
onMounted(async () => {
  // Set client flag to enable reactive rendering
  await nextTick();
  isClient.value = true;
  
  // Apply the default theme
  updateHtmlAttribute();
});

// Computed class for the main container based on the theme
const containerClass = computed(() => {
  return isDarkMode.value ? 'bg-dark text-light' : 'bg-light';
});
</script>

<template>
  <div :class="containerClass" style="min-height: 100vh;">
    <!-- Initial loading state -->
    <div v-if="showInitialLoading" class="d-flex justify-content-center align-items-center min-vh-100">
      <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="mb-2">Initializing Application</h4>
        <p class="text-muted">Checking authentication status...</p>
      </div>
    </div>

    <!-- Login Form for Unauthenticated Users (not on home page) -->
    <div v-if="showLoginForm" class="d-flex justify-content-center align-items-center min-vh-100">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-6 col-lg-4">
            <div class="card shadow">
              <div class="card-header text-center">
                <h3 class="mb-0">Authentication Required</h3>
              </div>
              <div class="card-body">
                <p class="text-center text-muted mb-4">
                  Please log in to access this page.
                </p>
                <LoginForm />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Home Page Content (handles its own auth) -->
    <div v-if="showHomePage">
      <div id="app" class="pt-3 pb-5">
        <NuxtPage />
      </div>
    </div>

    <!-- Main App Content for Authenticated Users -->
    <div v-if="showMainApp">
      <!-- Navbar Component -->
      <ClientOnly>
        <Navbar 
          @toggle-dark-mode="toggleDarkMode" 
          :is-dark-mode="isDarkMode" 
        />
        <template #fallback>
          <!-- Navbar loading skeleton -->
          <nav class="navbar navbar-expand-lg sticky-top shadow-sm"
            :class="isDarkMode ? 'navbar-dark bg-dark' : 'navbar-light bg-light'">
            <div class="container-fluid d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <div class="placeholder-glow me-2">
                  <div class="placeholder rounded" style="width: 40px; height: 40px;"></div>
                </div>
                <div class="placeholder-glow">
                  <h1 class="placeholder col-6 mb-0" style="height: 2rem; width: 120px;"></h1>
                </div>
              </div>
              <div class="d-flex align-items-center">
                <div class="placeholder-glow me-3">
                  <span class="placeholder col-12" style="width: 100px; height: 1rem;"></span>
                </div>
                <div class="placeholder-glow">
                  <div class="placeholder rounded" style="width: 80px; height: 32px;"></div>
                </div>
              </div>
            </div>
          </nav>
        </template>
      </ClientOnly>

      <div id="app" class="pt-3 pb-5">
        <NuxtPage />
      </div>
    </div>
    
    <!-- Global components -->
    <ClientOnly>
      <GlobalLoadingOverlay />
    </ClientOnly>
    
    <ClientOnly>
      <Toast />
    </ClientOnly>
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
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
}
</style>
