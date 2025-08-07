---
description: 'Nuxt4 & Vue 3 frontend coding conventions and guidelines'
applyTo: 'frontend/**/*.{vue,ts,js,md,json,scss,css,yml}'
---

# Nuxt4 & Vue 3 Best Practices

## General Principles
- Follow [Vue.js Style Guide](https://vuejs.org/style-guide/) (Priority A & B rules are mandatory, C recommended)
- Follow [Nuxt Directory Structure](https://nuxt.com/docs/guide/directory-structure) and [Nuxt Best Practices](https://nuxt.com/docs/guide/concepts/best-practices)
- Use Composition API (`<script setup lang="ts">`) for all new components
- Prefer TypeScript for all logic and components
- Use PascalCase for component filenames (e.g., `MyComponent.vue`)
- Use kebab-case for composables and utility files (e.g., `use-foo.ts`)
- Use `.vue` for components/pages/layouts, `.ts` for composables, stores, utils, and config
- Use `.md` for documentation, `.json` for config, `.scss`/`.css` for styles, `.yml` for workflows

## Component Guidelines
- One component per file
- Use `<template>`, `<script setup lang="ts">`, and `<style scoped>` blocks
- Keep components small and focused (SRP)
- Use props with explicit types and default values
- Use emits for events, define with `defineEmits`
- Use `defineProps` and `defineEmits` for type safety
- Prefer composables for logic reuse

## Directory Structure
- Place components in `frontend/app/components/`
- Place pages in `frontend/app/pages/`
- Place composables in `frontend/app/composables/`
- Place stores in `frontend/app/stores/`
- Place utils in `frontend/app/utils/`
- Place layouts in `frontend/app/layouts/`

## Testing
- Place tests in `frontend/tests/` mirroring the source structure
- Use Vitest for unit/integration tests
- Use Playwright or Cypress for E2E tests

## Linting & Formatting
- Use ESLint with Vue/TypeScript plugins
- Use Prettier for code formatting

## References
- [Vue Style Guide](https://vuejs.org/style-guide/)
- [Nuxt Best Practices](https://nuxt.com/docs/guide/concepts/best-practices)
- [Nuxt Directory Structure](https://nuxt.com/docs/guide/directory-structure)