<template>
  <div v-if="!ready" style="display:none"></div>
  <div v-else class="container py-4">
    <LoginForm v-if="!isAuthenticated" />
    <div v-else class="text-center">
      <h1>Thanzil App</h1>
      <p class="alert alert-success">Welcome {{ user?.name || user?.username }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import LoginForm from '~/components/LoginForm.vue';
import { useAuth } from '~/composables/useAuth';
const { isAuthenticated, user } = useAuth();
const ready = ref(false);

onMounted(() => {
  ready.value = true;
});
</script>

<style scoped>
/* Add styles for the home page here if needed */
</style>
