<template>
  <div class="min-vh-100 d-flex align-items-center" :class="containerClasses">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
          <div class="text-center">
            <!-- Large 404 Error Message -->
            <div class="mb-4">
              <h1 class="display-1 fw-bold text-primary mb-3">404</h1>
              <h2 class="h3 fw-normal text-dark mb-3">Page Not Found</h2>
            </div>

            <!-- Friendly explanation text -->
            <div class="mb-5">
              <p class="lead text-muted mb-3">
                Oops! The page '{{ route.path }}' you're looking for doesn't exist or has been moved.
              </p>
              <p class="text-muted">
                {{ getContextualMessage() }}
              </p>
            </div>

            <!-- Primary navigation buttons -->
            <div class="d-grid gap-3 d-md-flex justify-content-md-center mb-4">
              <button
                class="btn btn-primary btn-lg px-4"
                type="button"
                @click="goHome"
                :disabled="isNavigating"
              >
                <i class="bi bi-house-fill me-2"></i>
                {{ isAuthenticated ? 'Go to Dashboard' : 'Go Home' }}
              </button>
            </div>

            <!-- Popular pages links (for unauthenticated users) -->
            <div v-if="!isAuthenticated && popularPages.length > 0" class="mt-5">
              <h5 class="text-muted mb-3">Popular Pages</h5>
              <div class="d-flex flex-wrap justify-content-center gap-2">
                <NuxtLink
                  v-for="page in popularPages"
                  :key="page.path"
                  :to="page.path"
                  class="btn btn-sm btn-outline-primary"
                  @click="handlePopularPageClick(page)"
                >
                  <i v-if="page.icon" :class="page.icon + ' me-1'"></i>
                  {{ page.label }}
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from '#app';
import { useToast } from '~/utils/errorHandling';
import type { User } from '~/types/index';

// Define TypeScript interfaces
interface PopularPage {
  label: string;
  path: string;
  icon?: string;
}

// Composables and services
const router = useRouter();
const route = useRoute();
const { showSuccessToast, showErrorToast } = useToast();

// Authentication state - only available on client side
const isAuthenticated = ref(false);
const isInitialized = ref(false);
const user = ref<User | null>(null);

// Reactive state
const isNavigating = ref(false);

// Popular pages configuration
const popularPages = ref<PopularPage[]>([
  { label: 'Dashboard', path: '/dashboard', icon: 'bi bi-speedometer2' },
  { label: 'Estimates', path: '/estimates', icon: 'bi bi-calculator' },
  { label: 'Documents', path: '/doc', icon: 'bi bi-file-text' },
  { label: 'Users', path: '/list/users', icon: 'bi bi-people' }
]);

// Computed properties for dynamic content
const containerClasses = computed(() => ({
  'pt-0': !isAuthenticated.value,  // No top padding if no navbar
  'pt-4': isAuthenticated.value    // Add padding if navbar is present
}));

// Dynamic messages based on authentication state
const getContextualMessage = (): string => {
  if (isAuthenticated.value) {
    return "Don't worry, you can use the navigation menu above to find what you're looking for.";
  }
  return "Don't worry, let's get you back on track.";
};

// Set page metadata
useHead({
  title: '404 - Page Not Found | Thanzil',
  meta: [
    {
      name: 'description',
      content: 'The page you are looking for could not be found. Return to Thanzil dashboard or search for what you need.'
    },
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ]
});

// Initialize client-side only composables
onMounted(async () => {
  // Only initialize on client side to avoid SSR issues
  if (import.meta.client) {
    try {
      // Initialize authentication
      const { useAuth } = await import('~/composables/useAuth');
      const auth = useAuth();
      isAuthenticated.value = auth.isAuthenticated.value;
      isInitialized.value = auth.isInitialized.value;
      user.value = auth.user.value;

      // Watch for auth changes
      watch(auth.isAuthenticated, (newValue) => {
        isAuthenticated.value = newValue;
      });
      watch(auth.isInitialized, (newValue) => {
        isInitialized.value = newValue;
      });
      watch(auth.user, (newValue) => {
        user.value = newValue;
      });

    } catch (error) {
      console.warn('Failed to initialize client-side composables:', error);
    }
  }
});

// Navigation handlers with error handling
const goHome = async (): Promise<void> => {
  if (isNavigating.value) return;
  
  try {
    isNavigating.value = true;
    // Go to dashboard for authenticated users, home for unauthenticated
    const destination = isAuthenticated.value ? '/dashboard' : '/';
    await router.push(destination);
    showSuccessToast('Redirected to dashboard');
  } catch (error) {
    console.error('Navigation error:', error);
    showErrorToast('Failed to navigate to home page');
  } finally {
    isNavigating.value = false;
  }
};

// Popular page click handler
const handlePopularPageClick = (page: PopularPage): void => {
  showSuccessToast(`Navigating to ${page.label}`);
};

// Error boundary - catch any component errors
const componentError = ref<string>('');

// Provide error feedback if component fails
const handleComponentError = (error: Error): void => {
  console.error('404 Page Component Error:', error);
  componentError.value = 'An error occurred while loading this page';
  showErrorToast('Page loading error');
};

// Error handling for the component
onErrorCaptured((error: Error) => {
  handleComponentError(error);
  return false; // Prevent the error from propagating
});
</script>

