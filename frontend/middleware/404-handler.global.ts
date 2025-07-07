/**
 * Global middleware to handle 404 errors
 * Let Nuxt handle 404s naturally - this middleware is mostly for logging
 */
export default defineNuxtRouteMiddleware((to) => {
  // Skip this middleware for the 404 page itself to prevent infinite loops
  if (to.path === '/404') {
    return;
  }
  
  // Skip for API routes
  if (to.path.startsWith('/api/')) {
    return;
  }
  
  // Let Nuxt handle 404s naturally - no intervention needed
  // The 404.vue page will be shown automatically for non-existent routes
});
