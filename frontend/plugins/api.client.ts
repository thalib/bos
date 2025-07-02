import { useApiService } from '~/services/api'
import { useAuthService } from '~/services/auth'

export default defineNuxtPlugin(() => {
  // Initialize authentication service first
  const auth = useAuthService()
  
  // Initialize API service with auth interceptors
  const api = useApiService()
  api.initialize()
    // Wait for client-side hydration to complete before doing auth checks
  if (import.meta.client) {
    // Force re-initialization of auth state from localStorage
    auth.initAuth()
  }
})
