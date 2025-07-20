/**
 * Authentication Middleware
 * 
 * Protects routes by ensuring users are authenticated.
 * Redirects unauthenticated users to the login page with return URL.
 */

import { useAuthService } from '~/app/utils/auth'

export default defineNuxtRouteMiddleware((to, from) => {
  // Skip middleware on server-side rendering
  if (!process.client) {
    return
  }

  const { isAuthenticated, isInitialized } = useAuthService()

  return new Promise((resolve) => {
    const checkAuth = () => {
      if (isInitialized.value) {
        if (!isAuthenticated.value && to.path !== '/') {
          resolve(
            navigateTo({
              path: '/',
              query: { redirect: to.fullPath },
            })
          )
        } else {
          resolve()
        }
      } else {
        // Wait for auth initialization
        setTimeout(checkAuth, 10)
      }
    }

    nextTick(() => {
      checkAuth()
    })
  })
})