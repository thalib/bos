<template>
  <div>
    <NuxtRouteAnnouncer />
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container">
        <NuxtLink to="/" class="navbar-brand">BOS Project</NuxtLink>
        <div class="navbar-nav">
          <NuxtLink to="/" class="nav-link">Home</NuxtLink>
          <NuxtLink to="/demo" class="nav-link">API Demo</NuxtLink>
        </div>
      </div>
    </nav>
    <main>
      <NuxtPage />
    </main>
  </div>
</template>

<script setup>
// Initialize auth service on app load
import { useAuthService } from '~/app/utils/auth'

// Initialize authentication service
useAuthService()
</script>

<style>
/* Bootstrap CSS will be added via CDN in nuxt.config.ts */
body {
  margin: 0;
  padding: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
}

.navbar-brand, .nav-link {
  text-decoration: none !important;
}

.nav-link:hover {
  opacity: 0.8;
}
</style>
