/**
 * Navigation History Utility
 * Tracks user navigation to provide better back navigation functionality
 */
import { ref, computed } from 'vue';
import { useRouter, useRoute } from '#app';

// Navigation history state
const navigationHistory = ref<string[]>([]);
const maxHistorySize = 10;

// Current route tracking
let currentRoute = '';

/**
 * Navigation history composable
 */
export const useNavigationHistory = () => {
  const router = useRouter();
  const route = useRoute();

  // Add route to history
  const addToHistory = (path: string): void => {
    // Don't add duplicate consecutive entries
    if (navigationHistory.value[navigationHistory.value.length - 1] === path) {
      return;
    }
    
    // Don't add error pages to history
    if (path === '/404' || path === '/error') {
      return;
    }
    
    navigationHistory.value.push(path);
    
    // Limit history size
    if (navigationHistory.value.length > maxHistorySize) {
      navigationHistory.value = navigationHistory.value.slice(-maxHistorySize);
    }
  };

  // Get the previous valid route
  const getPreviousRoute = (): string | null => {
    // Filter out current route and error pages
    const validHistory = navigationHistory.value.filter(path => 
      path !== route.path && 
      path !== '/404' && 
      path !== '/error'
    );
    
    return validHistory[validHistory.length - 1] || null;
  };

  // Check if we can go back
  const canGoBack = computed(() => {
    return getPreviousRoute() !== null || window.history.length > 1;
  });

  // Smart go back function
  const goBack = async (): Promise<void> => {
    const previousRoute = getPreviousRoute();
    
    if (previousRoute) {
      // Navigate to the previous valid route from our history
      await router.push(previousRoute);
    } else if (window.history.length > 1) {
      // Fall back to browser history
      window.history.back();
    } else {
      // Fall back to home page
      await router.push('/');
    }
  };

  // Go to a specific route and update history
  const navigateTo = async (path: string): Promise<void> => {
    addToHistory(route.path); // Add current route before navigating
    await router.push(path);
  };

  // Initialize navigation tracking
  const initializeTracking = (): void => {
    // Add current route to history if not already there
    if (route.path && route.path !== '/404' && route.path !== '/error') {
      addToHistory(route.path);
    }
    
    // Track route changes
    router.afterEach((to, from) => {
      if (to.path && to.path !== '/404' && to.path !== '/error') {
        addToHistory(to.path);
      }
    });
  };

  // Clear history (useful for logout, etc.)
  const clearHistory = (): void => {
    navigationHistory.value = [];
  };

  // Get formatted history for debugging
  const getHistory = (): string[] => {
    return [...navigationHistory.value];
  };

  return {
    canGoBack,
    goBack,
    navigateTo,
    addToHistory,
    getPreviousRoute,
    initializeTracking,
    clearHistory,
    getHistory,
    
    // Computed properties
    historyLength: computed(() => navigationHistory.value.length),
    lastVisitedRoute: computed(() => getPreviousRoute())
  };
};
