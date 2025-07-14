import { useAuth } from '~/composables/useAuth';
import { navigateTo } from '#app';
import { nextTick } from 'vue';

/**
 * Authentication middleware for BOS project
 * 
 * This middleware:
 * 1. Checks if the user is authenticated
 * 2. Redirects to login if not authenticated
 */
export default defineNuxtRouteMiddleware((to, from) => {
  // Skip auth check during SSR - wait for client-side hydration
  if (!process.client) {
    return;
  }
  
  // Get auth state
  const { isAuthenticated, isInitialized } = useAuth();
  
  // Wait for both hydration and auth initialization to complete before checking auth
  // This prevents the flash of content before auth check
  return new Promise((resolve) => {
    const checkAuth = () => {
      if (isInitialized.value) {
        // If not authenticated and not already on the home page, redirect to home
        if (!isAuthenticated.value && to.path !== '/') {
          // Redirect to home page, preserving the intended destination
          resolve(navigateTo({
            path: '/',
            query: { redirect: to.fullPath }
          }));
        } else {
          resolve();
        }
      } else {
        // Auth not initialized yet, wait a bit and try again
        setTimeout(checkAuth, 10);
      }
    };
    
    nextTick(() => {
      checkAuth();
    });
  });
});
