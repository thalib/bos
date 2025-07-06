<template>
  <div class="container d-flex justify-content-center">
    <div style="width: 400px;">
      <form @submit.prevent="onSubmit">
        <div class="row mb-3">
          <div class="col-12">
            <label for="username" class="form-label">Username</label>
            <input v-model="username" id="username" class="form-control" required />
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-12">
            <label for="password" class="form-label">Password</label>
            <input v-model="password" id="password" type="password" class="form-control" required />
          </div>
        </div>
        <div class="row mb-3" v-if="error">
          <div class="col-12">
            <div class="alert alert-danger">{{ error }}</div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAuth } from '~/composables/useAuth';
import { useRoute, useRouter } from 'vue-router';

// Define emits
const emit = defineEmits<{
  'login-success': []
}>();

const username = ref('');
const password = ref('');
const error = ref('');
const { login } = useAuth();
const route = useRoute();
const router = useRouter();

async function onSubmit() {
  error.value = '';
  const success = await login(username.value, password.value);
  if (!success) {
    error.value = 'Invalid username or password';
  } else {
    // Emit login success event for parent components to handle
    emit('login-success');
    
    // Check if there's a redirect parameter in the URL
    const redirectTo = route.query.redirect as string;
    if (redirectTo) {
      // Navigate to the originally intended page
      await router.push(redirectTo);
    }
    // If no redirect, the parent component will handle showing authenticated content
  }
}
</script>

<style scoped>
/* No custom classes, only Bootstrap grid used */
</style>
