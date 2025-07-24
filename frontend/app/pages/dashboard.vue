<template>
  <div class="container mt-5">
    <!-- Row 1: Dashboard Heading -->
    <div class="row">
      <div class="col-12">
        <h1 class="text-center">
          <i class="bi bi-speedometer2"></i> Dashboard
        </h1>
      </div>
    </div>

    <!-- Row 2: Welcome Message -->
    <div class="row mt-4">
      <div class="col-12 col-md-8 col-lg-6 mx-auto">
        <div class="card">
          <div class="card-body text-center py-5">
            <h3 class="card-title mb-3">
              <i class="bi bi-house-heart"></i>
            </h3>
            <p class="card-text fs-4 text-center" data-testid="welcome-message">
              <ClientOnly fallback="Welcome to the Guest!">
                Welcome to the {{ userName }}!
              </ClientOnly>
            </p>
            <p class="text-muted">
              You have successfully accessed your dashboard.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">

// This page is protected by the auth middleware
definePageMeta({
  middleware: 'auth',
  layout: 'admin',
  title: 'Dashboard',
})

// Services
const authService = useAuthService()

// Computed user name
const userName = computed(() => {
  const user = authService.getCurrentUser()
  if (!user) return 'Guest'
  return user.name || 'User'
})

</script>