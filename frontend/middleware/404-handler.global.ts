/**
 * Global middleware to handle 404 errors
 */
export default defineNuxtRouteMiddleware((to) => {
  // Skip this middleware for API routes
  if (to.path.startsWith('/api/')) {
    return;
  }
  
  // For any route that doesn't exist, throw a 404 error
  // This will trigger Nuxt's error handling system and show error.vue
  if (to.matched.length === 0) {
    throw createError({
      statusCode: 404,
      statusMessage: 'Page Not Found'
    });
  }
});
