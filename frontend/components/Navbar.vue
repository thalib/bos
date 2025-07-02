<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from '#imports';
import { useAuth } from '~/composables/useAuth';
import { useNavigation } from '~/composables/useNavigation';
import Sidebar from '~/components/navbar/Sidebar.vue';

// Define props received from the parent (app.vue)
const props = defineProps({
  isDarkMode: Boolean
});

// Define emits to send events to the parent (app.vue)
const emit = defineEmits(['toggle-dark-mode']);

const handleToggle = () => {
  emit('toggle-dark-mode');
};

// Use navigation composable
const { menuItems, flatMenuItems, isLoading: menuLoading, error: menuError } = useNavigation();

const route = useRoute();
const currentPageName = computed(() => {
  const found = flatMenuItems.value.find(item => item.path === route.path);
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
  <!-- Sidebar component -->
  <Sidebar :menu-items="menuItems" :is-dark-mode="props.isDarkMode" :is-loading="menuLoading" :error="menuError"
    @toggle-dark-mode="handleToggle" />

  <nav v-if="isAuthenticated" class="navbar navbar-expand-lg sticky-top shadow-sm"
    :class="props.isDarkMode ? 'navbar-dark bg-dark' : 'navbar-light bg-light'">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <!-- Replace dropdown with button that triggers the offcanvas -->
        <button class="btn me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas"
          aria-controls="mainMenuOffcanvas" aria-label="Menu" :disabled="menuLoading">
          <!-- Show loading spinner or menu icon -->
          <div v-if="menuLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
          <span v-else class="navbar-toggler-icon"></span>
        </button>

        <!-- Current page name with loading state -->
        <div class="navbar-brand fw-bold mb-0 h1 ms-2">
          <div v-if="menuLoading" class="placeholder-glow">
            <span class="placeholder col-6"></span>
          </div>
          <span v-else>{{ currentPageName }}</span>
        </div>
      </div>
    </div>
  </nav>
</template>
