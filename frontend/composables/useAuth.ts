import { ref, computed } from 'vue';

export function useAuth() {
  const config = useRuntimeConfig();
  // Accept both boolean and string for ENABLE_AUTH
  const ENABLE_AUTH = String(config.public.ENABLE_AUTH) !== 'false';
  const LOCAL_STORAGE_KEY = 'auth_user';
  const user = ref(getStoredUser());

  function getStoredUser() {
    if (process.client) {
      const stored = localStorage.getItem(LOCAL_STORAGE_KEY);
      return stored ? JSON.parse(stored) : null;
    }
    return null;
  }

  function saveUser(u: any) {
    if (process.client) {
      if (u) {
        localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(u));
      } else {
        localStorage.removeItem(LOCAL_STORAGE_KEY);
      }
    }
  }
  // Updated to use API endpoint
  async function loginWithDataSource(username: string, password: string) {
    try {
      // Get all users and find matching credentials
      const response = await fetch('http://localhost:4000/users');
      const users = await response.json();
      
      const found = users.find(
        (u: any) => u.username === username && u.password === password
      );
      
      if (found) {
        // Return a clean user object without password
        return { 
          username: found.username,
          name: found.name,
          role: found.role
        };
      }
      return null;
    } catch (error) {
      console.error('Error authenticating user:', error);
      return null;
    }
  }

  async function login(username: string, password: string) {
    if (!ENABLE_AUTH) {
      user.value = { username: 'dev', name: 'Dev User', role: 'dev' };
      saveUser(user.value);
      return true;
    }
    const found = await loginWithDataSource(username, password);
    if (found) {
      user.value = found;
      saveUser(user.value);
      return true;
    }
    return false;
  }

  function logout() {
    user.value = null;
    saveUser(null);
  }

  const isAuthenticated = computed(() => {
    if (!ENABLE_AUTH) return true;
    return !!user.value;
  });

  return {
    user,
    isAuthenticated,
    login,
    logout,
    ENABLE_AUTH,
  };
}
