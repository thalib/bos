import { useAuthService } from '../utils/auth'

/**
 * Authentication middleware to protect routes
 * Redirects unauthenticated users to login page
 */
export default defineNuxtRouteMiddleware((to, from) => {
  // Skip middleware on server side
  if (!process.client) {
    return
  }

  const { isAuthenticated, isInitialized } = useAuthService()

  // Wait for auth initialization before checking authentication
  return new Promise((resolve) => {
    const checkAuth = () => {
      if (isInitialized.value) {
        if (!isAuthenticated.value && to.path !== '/') {
          // Redirect to login page with return URL
          const result = navigateTo({
            path: '/',
            query: { redirect: to.fullPath },
          })
          resolve(result)
        } else {
          // User is authenticated or going to login page
          resolve(undefined)
        }
      } else {
        // Auth not initialized yet, wait a bit
        setTimeout(checkAuth, 10)
      }
    }

    // Start checking after next tick
    nextTick(() => {
      checkAuth()
    })
  })
})