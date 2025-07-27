<template>
  <div class="container mt-5">
    <!-- Row 1: Dashboard Heading -->
    <div class="row">
      <div class="col-12">
        <h1 class="text-center">
          <i class="bi bi-speedometer2"></i> Resource Page
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
const authService = useAuthService();
const menuService = useMenuService();
const route = useRoute();

// Computed user name
const userName = computed(() => {
  const user = authService.getCurrentUser();
  if (!user) return 'Guest';
  return user.name || 'User';
});

// Computed property to get the current menu item
const menuItem = computed(() => {
  const currentPath = route.path; // Ensure this is accessed lazily
  return menuService.getMenuDataByPath(currentPath) || { name: 'Unknown' };
});

// Dynamically set the page meta title
onMounted(() => {
  definePageMeta({
    middleware: 'auth',
    layout: 'default',
    title: 'Thalib'//menuItem.value.name, // Access the computed value
  });
});
</script>

<style scoped></style>