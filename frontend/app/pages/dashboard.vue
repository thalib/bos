<template>
  <div class="container mt-5">
    <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
    <p>Welcome to the protected dashboard!</p>
    <div class="alert alert-success">
      <i class="bi bi-check-circle"></i> This page should only be accessible to authenticated users.
    </div>
  </div>
</template>

<script setup lang="ts">
// This page is protected by the auth middleware
definePageMeta({
  middleware: 'auth'
})
</script>