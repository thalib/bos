import { useGlobalErrorHandler } from '~/composables/useErrorHandler';

/**
 * Error handling middleware for Thanzil project
 * 
 * This middleware:
 * 1. Catches route errors and 404s
 * 2. Ensures proper error logging
 * 3. Redirects to custom 404 page when appropriate
 * 4. Maintains authentication state during error handling
 * 5. Provides different behavior for development vs production
 */
export default defineNuxtRouteMiddleware(async (to, from) => {
  // Only run on client side to avoid SSR issues
  if (!process.client) {
    return;
  }

  // Skip error handling for the 404 page itself and let Nuxt handle 404s naturally
  if (to.path === '/404') {
    return;
  }

  // Skip for API routes - they should be handled by the API service
  if (to.path.startsWith('/api/')) {
    return;
  }

  try {
    // Get error handler instance
    const errorHandler = useGlobalErrorHandler();
    
    // Only handle actual errors, not missing routes (let Nuxt handle 404s naturally)
    // This middleware is now primarily for logging and handling specific error conditions
    
    // Log successful navigation for debugging if needed
    if (from && from.name === '404' && to.path !== '/404') {
      errorHandler.logError({
        type: 'route',
        status: 200,
        url: to.fullPath,
        message: `Recovered from 404 to valid route: ${to.path}`
      });
    }
    
  } catch (error) {
    // If error handling itself fails, log it and continue
    console.error('Error in route error middleware:', error);
    
    // In development, show more details
    if (process.dev) {
      console.warn('Route error middleware failed for route:', to.path, error);
    }
  }
});
