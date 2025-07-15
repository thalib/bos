/**
 * Auth composable for the BOS project
 * Exposes authentication functionality to components
 */
import { useAuthService } from '~/services/auth'

/**
 * This composable is a wrapper around the auth service
 * It maintains backward compatibility with existing components
 */
export function useAuth() {
  // Use the auth service from our services directory
  const auth = useAuthService()
    // Provide a simpler login function for backward compatibility
  async function login(username: string, password: string) {
    const response = await auth.login({ username, password })
    return !response.error && !!response.data;
  }
  return {
    // Expose user and authentication state
    user: auth.user,
    isAuthenticated: auth.isAuthenticated,
    isInitialized: auth.isInitialized,
    
    // Expose login and logout functions
    login,
    logout: auth.logout,
    
    // Expose other auth service functions
    refreshToken: auth.refreshToken,
    getTokens: auth.getTokens
  }
}
