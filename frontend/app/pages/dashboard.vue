<template>
  <div class="container mt-5">
    <h1>Dashboard</h1>
    <p>Welcome to the protected dashboard!</p>
    <div class="alert alert-success">
      This page should only be accessible to authenticated users.
    </div>
  </div>
</template>

<script setup lang="ts">
// This page is protected by the auth middleware
definePageMeta({
  middleware: 'auth'
})
</script>