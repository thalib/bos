<template>
  <div>
    <CommonNavbar :title="pageTitle" />
    <main class="container my-4">
      <slot />
    </main>
    <CommonFooter />
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const pageTitle = computed(() => route.meta.title || 'No Title')
</script>
