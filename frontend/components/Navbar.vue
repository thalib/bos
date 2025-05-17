<script setup lang="ts">

import { computed } from 'vue';
import { useRoute } from '#imports';
import { useAuth } from '~/composables/useAuth';
import { useRouter } from 'vue-router';

// Define props received from the parent (app.vue)
const props = defineProps({
  isDarkMode: Boolean
});

// Define emits to send events to the parent (app.vue)
const emit = defineEmits(['toggle-dark-mode']);

const handleToggle = () => {
  emit('toggle-dark-mode');
};

const menuItems = [
  { name: 'Home', path: '/' },
  { name: 'Estimator', path: '/estimate' },
  { name: 'Todo', path: '/todo' }
  // Add more pages here as needed
];

const route = useRoute();
const currentPageName = computed(() => {
  const found = menuItems.find(item => item.path === route.path);
  return found ? found.name : 'Thanzil';
});

const router = useRouter();
const { isAuthenticated, user, logout: baseLogout } = useAuth();

function logoutAndRedirect() {
  baseLogout();
  router.push('/');
}
</script>

<template>
  <nav v-if="isAuthenticated" class="navbar navbar-expand-lg sticky-top" :class="props.isDarkMode ? 'navbar-dark bg-dark' : 'navbar-light bg-light'">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <div class="dropdown">
          <button class="btn me-2" type="button" id="menuDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
          </button>
          <ul class="dropdown-menu" aria-labelledby="menuDropdown">
            <li v-for="item in menuItems" :key="item.path">
              <NuxtLink class="dropdown-item d-flex align-items-center" :to="item.path">
                {{ item.name }}
              </NuxtLink>
            </li>
          </ul>
        </div>
        <span class="navbar-brand fw-bold mb-0 h1 ms-2">{{ currentPageName }}</span>
      </div>
      <div v-if="isAuthenticated" class="d-flex align-items-center">
        <!-- User Icon Dropdown -->
        <div class="dropdown">
          <button class="btn p-0 me-2" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User Menu">
            <i class="bi bi-person-circle fs-3"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li class="dropdown-item-text fw-bold">{{ user?.name || user?.username }}</li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <button class="dropdown-item d-flex align-items-center" @click="handleToggle">
                <i :class="props.isDarkMode ? 'bi bi-sun-fill me-2' : 'bi bi-moon-stars-fill me-2'"></i>
                {{ props.isDarkMode ? 'Light Mode' : 'Dark Mode' }}
              </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <button class="dropdown-item d-flex align-items-center text-danger" @click="logoutAndRedirect">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>
</template>

<style scoped>
/* Add any component-specific styles here if needed */
.navbar {
  box-shadow: 0 2px 4px rgba(0,0,0,.1);
}
</style>