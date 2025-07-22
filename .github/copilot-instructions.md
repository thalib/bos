# GitHub Copilot Instructions for BOS Project

## Rules to Follow
- for general project principles, see `design/design-overview.md`.
- For backend-specific rules, see `design/rules-api.md`.
- For frontend-specific rules, see `design/rules-ui.md`.

## General Guidelines
- Follow the structured design principles outlined in `design/design-overview.md`.
- Adhere to Test-Driven Development (TDD) and Design-Driven Development (DDD) workflows.

## Backend Development
- Use Laravel 12 for API-only services.
- Write tests first in `backend/tests/Feature/` before implementing features.
- Ensure all API responses conform to `design/api/` standards.
- Protect all endpoints with `auth:sanctum` middleware.

## Frontend Development
- Use Nuxt 4 with strict TypeScript typing and Vue 3 Composition API, and prioritize Bootstrap 5.3 classes for styling.
- Write tests first in `frontend/tests/` before implementing features.
- Centralize HTTP requests in `frontend/app/utils/api.ts`. for Notification and logging use `frontend/app/utils/notify.ts`.



