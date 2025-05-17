import { useAuth } from '~/composables/useAuth';
import { useRuntimeConfig, navigateTo } from '#app';

export default defineNuxtRouteMiddleware((to, from) => {
  const { isAuthenticated, ENABLE_AUTH } = useAuth();
  if (!ENABLE_AUTH) return;
  if (!isAuthenticated.value && to.path !== '/') {
    return navigateTo('/');
  }
});
