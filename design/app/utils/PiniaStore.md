# Pinia Store Documentation

This document outlines the structure, usage, and best practices for implementing Pinia stores in the BOS project.

## File Location

All Pinia stores should be placed in the `frontend/stores/` directory. Each store should be modular and named according to its purpose. For example:

```plaintext
frontend/
├─ stores/
│  ├─ authStore.ts
│  ├─ dataStore.ts
│  └─ userStore.ts
```

## Setting Up Pinia

1. **Install Pinia**:
   ```bash
   npx nuxt module add pinia
   ```

2. **Create a Store**:
   Define a store in the `stores` directory. For example:
   ```typescript
   // stores/dataStore.ts
   import { defineStore } from 'pinia';

   export const useDataStore = defineStore('dataStore', {
     state: () => ({
       data: null,
     }),
     actions: {
       setData(payload) {
         this.data = payload;
       },
       async fetchData() {
         const response = await $fetch('/api/data');
         this.data = response;
       },
     },
   });
   ```

3. **Access the Store**:
   Use the store in any component or page:
   ```typescript
   <script setup>
   import { useDataStore } from '~/stores/dataStore';

   const dataStore = useDataStore();
   dataStore.fetchData();
   </script>
   ```

## Best Practices

- **Modular Stores**: Create separate stores for different features or modules (e.g., `authStore`, `userStore`).
- **TypeScript**: Use TypeScript for strict typing and better developer experience.
- **Avoid Non-Serializable Data**: Do not store non-serializable data (e.g., functions, classes) in the state.
- **SSR Support**: Ensure that the state is properly hydrated on the client side.
- **Testing**: Write unit tests for store actions and getters.

## Example Stores

### Authentication Store
```typescript
// stores/authStore.ts
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('authStore', {
  state: () => ({
    user: null,
    token: null,
  }),
  actions: {
    setUser(user) {
      this.user = user;
    },
    setToken(token) {
      this.token = token;
    },
    async login(credentials) {
      const response = await $fetch('/api/login', { method: 'POST', body: credentials });
      this.setUser(response.user);
      this.setToken(response.token);
    },
  },
});
```

### Data Store
```typescript
// stores/dataStore.ts
import { defineStore } from 'pinia';

export const useDataStore = defineStore('dataStore', {
  state: () => ({
    data: null,
  }),
  actions: {
    setData(payload) {
      this.data = payload;
    },
    async fetchData() {
      const response = await $fetch('/api/data');
      this.data = response;
    },
  },
});
```

## References

- [Pinia Documentation](https://pinia.vuejs.org/)
- [Nuxt Pinia Module](https://pinia.vuejs.org/ssr/nuxt.html)
