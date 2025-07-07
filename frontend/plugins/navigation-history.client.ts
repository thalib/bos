/**
 * Navigation History Plugin
 * Initializes global navigation tracking
 */
import { useNavigationHistory } from '~/composables/useNavigationHistory';

export default defineNuxtPlugin({
  name: 'navigation-history',
  parallel: false,
  setup() {
    // Only run on client side
    if (import.meta.client) {
      const { initializeTracking } = useNavigationHistory();
      
      // Initialize navigation tracking
      initializeTracking();
      
      // Provide global access if needed
      return {
        provide: {
          navigationHistory: useNavigationHistory()
        }
      };
    }
  }
});
