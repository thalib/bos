<script setup lang="ts">
import { computed, ref, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from '#imports';
import { useAuth } from '~/composables/useAuth';
import { useNavigation } from '~/composables/useNavigation';
import { useAppLoading } from '~/composables/useAppLoading';
import { useToast } from '~/utils/errorHandling';
import Sidebar from '~/components/navbar/Sidebar.vue';

// Define props received from the parent (app.vue)
const props = defineProps({
  isDarkMode: Boolean
});

// Define emits to send events to the parent (app.vue)
const emit = defineEmits(['toggle-dark-mode']);

const handleToggle = () => {
  emit('toggle-dark-mode');
};

// Authentication and loading state management
const { isAuthenticated, isInitialized, user } = useAuth();
const { canShowComponentContent } = useAppLoading();
const { showErrorToast, showSuccessToast } = useToast();

// Client-side rendering flag to prevent hydration mismatches
const isClient = ref(false);

// Use navigation composable
const { menuItems, flatMenuItems, isLoading: menuLoading, error: menuError } = useNavigation();

// Local loading states
const isNavbarReady = ref(false);

// Computed states for rendering control - only active on client side
const canShowNavbar = computed(() => {
  // Always return false during SSR to prevent hydration mismatch
  if (!isClient.value) return false;
  return isInitialized.value && isAuthenticated.value && isNavbarReady.value && canShowComponentContent.value;
});

const canShowNavbarContent = computed(() => {
  // Always return false during SSR to prevent hydration mismatch
  if (!isClient.value) return false;
  return canShowNavbar.value && !menuLoading.value;
});

const route = useRoute();
const currentPageName = computed(() => {
  // Return default during SSR
  if (!isClient.value || !canShowNavbarContent.value) return 'Thanzil';
  const found = flatMenuItems.value.find(item => item.path === route.path);
  return found ? found.name : 'Thanzil';
});

const router = useRouter();

// Initialize navbar after authentication is confirmed
onMounted(async () => {
  // Set client flag to enable reactive rendering
  await nextTick();
  isClient.value = true;
  
  // Wait for authentication to be initialized
  if (!isInitialized.value) {
    const unwatch = watch(isInitialized, (initialized) => {
      if (initialized && isAuthenticated.value) {
        initializeNavbar();
        unwatch();
      }
    });
  } else if (isAuthenticated.value) {
    initializeNavbar();
  }
});

const initializeNavbar = async () => {
  try {
    // Small delay to ensure smooth loading transition
    await new Promise(resolve => setTimeout(resolve, 100));
    isNavbarReady.value = true;
  } catch (error) {
    console.error('Navbar initialization error:', error);
    showErrorToast('Failed to initialize navigation');
  }
};

// Watch for authentication state changes
watch(isAuthenticated, (authenticated) => {
  // Only process changes on client side
  if (!isClient.value) return;
  
  if (!authenticated) {
    // Reset navbar state when user logs out
    isNavbarReady.value = false;
  } else if (isInitialized.value) {
    // Re-initialize when user logs back in
    initializeNavbar();
  }
});

// Watch for menu errors
watch(menuError, (error) => {
  // Only process errors on client side
  if (!isClient.value) return;
  
  if (error) {
    showErrorToast(`Navigation error: ${error}`);
  }
});
</script>

<template>
  <!-- Always render navbar structure to prevent layout shifts -->
  <div>
    <!-- Sidebar component (always render when navbar is visible) -->
    <Sidebar 
      v-if="canShowNavbar"
      :menu-items="menuItems" 
      :is-dark-mode="props.isDarkMode" 
      :is-loading="menuLoading" 
      :error="menuError"
      @toggle-dark-mode="handleToggle" 
    />

    <!-- Main Navbar - show loading skeleton until ready -->
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm"
      :class="props.isDarkMode ? 'navbar-dark bg-dark' : 'navbar-light bg-light'">
      <div class="container-fluid d-flex justify-content-between align-items-center">
        
        <!-- Left section -->
        <div class="d-flex align-items-center">
          <!-- Menu toggle button -->
          <button 
            class="btn me-2" 
            type="button" 
            :data-bs-toggle="canShowNavbar ? 'offcanvas' : undefined"
            :data-bs-target="canShowNavbar ? '#mainMenuOffcanvas' : undefined"
            :aria-controls="canShowNavbar ? 'mainMenuOffcanvas' : undefined"
            aria-label="Menu" 
            :disabled="!canShowNavbar || menuLoading">
            
            <!-- Loading states for menu button -->
            <div v-if="!canShowNavbar || menuLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
            <span v-else class="navbar-toggler-icon"></span>
          </button>

          <!-- Current page name -->
          <div class="navbar-brand fw-bold mb-0 h1 ms-2">
            <div v-if="!canShowNavbarContent" class="placeholder-glow">
              <span class="placeholder col-6" style="width: 120px; height: 1.5rem;"></span>
            </div>
            <span v-else>{{ currentPageName }}</span>
          </div>
        </div>

        <!-- Right section -->
        <div class="d-flex align-items-center">
          <!-- User info and logout - show content or skeleton -->
          <div v-if="canShowNavbarContent" class="d-flex align-items-center">
            <!-- User info -->
            <div>
              <span class="text-muted small">Welcome, </span>
              <span class="fw-semibold">{{ user?.name || 'User' }}</span>
            </div>
          </div>

          <!-- Loading skeleton for user section -->
          <div v-else class="d-flex align-items-center">
            <div class="placeholder-glow">
              <span class="placeholder col-12" style="width: 100px; height: 1rem;"></span>
            </div>
          </div>
        </div>
      </div>
    </nav>
  </div>
</template>
