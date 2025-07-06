/**
 * Global Loading Plugin for Route Transitions
 * Integrates with Nuxt navigation hooks to provide loading states
 */
import { useAppLoading } from '~/composables/useAppLoading'

export default defineNuxtPlugin((nuxtApp) => {
  const { startRouteLoading, stopRouteLoading, handleLoadingError } = useAppLoading()

  // Track route loading state
  let routeLoadingId: string | null = null

  // Start loading on route changes
  nuxtApp.hook('page:start', () => {
    if (routeLoadingId) {
      stopRouteLoading()
    }
    routeLoadingId = startRouteLoading('Loading page...')
  })

  // Stop loading when page is ready
  nuxtApp.hook('page:finish', () => {
    if (routeLoadingId) {
      stopRouteLoading()
      routeLoadingId = null
    }
  })

  // Handle loading errors
  nuxtApp.hook('vue:error', (error) => {
    if (routeLoadingId) {
      stopRouteLoading()
      routeLoadingId = null
    }
    handleLoadingError(error as Error, 'loading page')
  })

  // Handle navigation errors
  nuxtApp.hook('app:error', (error) => {
    if (routeLoadingId) {
      stopRouteLoading()
      routeLoadingId = null
    }
    handleLoadingError(error as Error, 'navigating')
  })
})
