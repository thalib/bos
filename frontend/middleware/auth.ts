import { useAuth } from '~/composables/useAuth';
import { navigateTo } from '#app';

/**
 * Authentication middleware for Thanzil project
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
  const { isAuthenticated } = useAuth();
  
  // If not authenticated and not already on the home page, redirect to home
  if (!isAuthenticated.value && to.path !== '/') {
    // Redirect to home page, preserving the intended destination
    return navigateTo({
      path: '/',
      query: { redirect: to.fullPath }
    });
  }
});
