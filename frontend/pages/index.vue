<template>
  <!-- Content for unauthenticated users -->
  <div v-if="!isAuthenticated && isInitialized" class="min-vh-100 d-flex align-items-center">
    <div class="container py-4">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="text-center mb-4">
            <h1 class="display-4 mb-3">Welcome to Thanzil</h1>
            <p class="lead text-muted">Your Business Management Solution</p>
          </div>
          <div class="card shadow">
            <div class="card-header text-center">
              <h3 class="mb-0">Sign In</h3>
            </div>
            <div class="card-body">
              <LoginForm @login-success="handleLoginSuccess" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useAuth } from '~/composables/useAuth';
import { usePageLoading } from '~/composables/usePageLoading';
import { useToast } from '~/utils/errorHandling';
import LoginForm from '~/components/LoginForm.vue';

// Get authentication state and loading helpers
const { isAuthenticated, isInitialized, user } = useAuth();
const { withLoading } = usePageLoading();
const { showSuccessToast } = useToast();

// Set page metadata
useHead({
  title: 'Welcome - Thanzil Business Management',
  meta: [
    {
      name: 'description',
      content: 'Welcome to Thanzil - Your comprehensive business management solution'
    }
  ]
});

// Handle successful login with global loading
const handleLoginSuccess = async () => {
  await withLoading(
    async () => {
      showSuccessToast(`Welcome back, ${user.value?.name || 'User'}!`);
      
      // Small delay to show the success message
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Redirect to dashboard/home page
      await navigateTo('/list/users');
    },
    {
      message: 'Redirecting to dashboard...',
      type: 'page',
      onError: (error) => {
        console.error('Navigation error after login:', error);
      }
    }
  );
};

// Watch for authentication state changes and redirect authenticated users
watch(
  [isAuthenticated, isInitialized], 
  async ([authenticated, initialized]) => {
    if (initialized && authenticated) {
      await withLoading(
        async () => {
          // Small delay to prevent flash
          await new Promise(resolve => setTimeout(resolve, 500));
          
          // Redirect authenticated users to dashboard
          await navigateTo('/list/users', { replace: true });
        },
        {
          message: 'Redirecting to your dashboard...',
          type: 'page',
          onError: (error) => {
            console.error('Auto-redirect error:', error);
          }
        }
      );
    }
  },
  { immediate: true }
);
</script>

<style scoped>
.min-vh-75 {
  min-height: 75vh;
}

.card {
  transition: transform 0.2s ease;
}

.card:hover {
  transform: translateY(-2px);
}
</style>