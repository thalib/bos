<template>
  <nav v-if="isAuthenticated" class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <!-- Left Section: Menu Toggle and Page Title -->
      <div class="d-flex align-items-center">
        <button
          type="button"
          class="btn btn-outline-secondary me-3"
          data-testid="menu-toggle"
          aria-label="Toggle menu"
        >
          <i class="bi bi-list"></i>
        </button>
        
        <h1 class="h4 mb-0" data-testid="page-title">
          {{ title }}
        </h1>
      </div>

      <!-- Right Section: User Dropdown -->
      <div class="dropdown">
        <button
          class="btn btn-outline-secondary dropdown-toggle"
          type="button"
          data-bs-toggle="dropdown"
          data-testid="user-dropdown"
          aria-label="User menu"
          aria-expanded="false"
        >
          <i class="bi bi-person-circle"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#" @click.prevent>Profile</a></li>
          <li><a class="dropdown-item" href="#" @click.prevent>Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#" @click.prevent>Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
interface Props {
  title: string
}

// Define props with validation
const props = defineProps<Props>()

// Use authentication service
const authService = useAuthService()
const isAuthenticated = computed(() => authService.isAuthenticated.value)
</script>