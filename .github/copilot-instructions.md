# GitHub Copilot Instructions for BOS Project

- Adhere to Test-Driven Development (TDD) and Design-Driven Development (DDD) workflows.

## Backend Development
- For backend-specific rules, see `design/rules-api.md`.
- Use Laravel 12 for API-only services.
- Write tests first in `backend/tests/Feature/` before implementing features.
- Ensure all API responses conform to `design/api/` standards.
- Protect all endpoints with `auth:sanctum` middleware.

## Frontend Development
- For frontend-specific rules, see `design/rules-app.md`.
- Use Nuxt 4 with strict TypeScript typing and Vue 3 Composition API, and prioritize Bootstrap 5.3 classes for styling.
- Write tests first in `frontend/tests/` before implementing features.
- Centralize HTTP requests in `frontend/app/utils/api.ts`. for Notification and logging use `frontend/app/utils/notify.ts`.



