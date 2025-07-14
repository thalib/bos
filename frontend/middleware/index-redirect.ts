import { useAuth } from '~/composables/useAuth';
import { navigateTo } from '#app';
import { nextTick } from 'vue';

/**
 * Index page redirect middleware for BOS project
 * 
 * This middleware:
 * 1. Allows unauthenticated users to access the index page (shows login)
 * 2. Redirects authenticated users to dashboard/home page
 * 3. Handles proper loading states during auth checks
 */
export default defineNuxtRouteMiddleware((to, from) => {
  // Skip auth check during SSR - wait for client-side hydration
  if (!process.client) {
    return;
  }
  
  // Only apply this middleware to the index page
  if (to.path !== '/') {
    return;
  }
  
  // Get auth state
  const { isAuthenticated, isInitialized } = useAuth();
  
  // Wait for authentication initialization to complete before checking auth
  return new Promise((resolve) => {
    const checkAuth = () => {
      if (isInitialized.value) {
        // If authenticated, redirect to dashboard
        if (isAuthenticated.value) {
          resolve(navigateTo({
            path: '/list/users', // Default dashboard page
            replace: true // Replace in history to prevent back navigation to index
          }));
        } else {
          // Not authenticated, allow access to index page (will show login form)
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
