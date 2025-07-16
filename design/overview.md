# BOS Project Overview

## General Design Principles

The BOS project follows a structured design approach to ensure robust, predictable, and maintainable APIs and frontend applications. The development process is guided by Test-Driven Development (TDD) and Design-Driven Development (DDD) principles, ensuring that all implementations adhere to predefined standards and best practices.

### Key Principles

- **TDD and DDD** are essential and complementary approaches that work together.
- **Design documents and feature tests** create a comprehensive specification system.
- **This approach ensures** robust, predictable, and maintainable APIs and frontend applications.

---

## Backend: TDD API-First Development with Laravel 12

The backend is designed as an API-only service, strictly following TDD and DDD principles. It leverages Laravel 12 to provide auto-registered API resources and standardized response formats.

- Backend source directory: `backend`
- **Standardized Response Format**: design\api\
- **Authentication**: All endpoints are protected by `auth:sanctum` middleware.
- **Auto-Registered API Resources**:
  - All CRUD operations are handled by `backend\app\Http\Controllers\ApiResourceController.php`.
  - Routes are registered in `backend\app\Providers\ApiResourceServiceProvider.php`
  - Standardized response defined in `backend\app\Http\Responses\ApiResponseTrait.php`.
  - Test routes for development testing `backend\routes\test.php`.

### Backend Workflow

- **Design**: Add or update an endpoint/field in the relevant API design file (e.g., `design/api/index.md`, `design/api/store.md`).
- **Test**: Write a failing test in `tests/Feature/` for the new behavior/response.
- **Implement**: Update controllers, requests, etc., to make the test pass and match the design.
- **Refactor**: Clean up code, keeping tests green.
- **Review**: Confirm both design and tests are up to date.

### Backend Best Practices

- Use the `RefreshDatabase` trait for isolated testing.
- Validate all requests using `StoreResourceRequest` and `UpdateResourceRequest`.
- Log all errors and provide user-friendly error messages.
- Ensure transaction safety and data consistency.
- Protect against SQL injection, XSS attacks, and other security vulnerabilities.

---

## Frontend: Data-Driven Development with Nuxt 3

The frontend is built using Nuxt 3, focusing on data-driven development and optimized UI/UX. It adheres to strict TypeScript typing and modern Vue 3 Composition API patterns.

- Backend source directory: `fcontend`
- **Shared API Service**: All HTTP requests must use the shared API service (`/frontend/services/api.ts`). Authentication headers are automatically handled by API service.
- **CRUD Operations**: Use `/frontend/services/apiCrud.ts` for standard CRUD operations.
- **UI Styling**: Prioritize Bootstrap 5.3 classes over custom CSS.
- **Error Handling**: Implement comprehensive error handling using utilities from `/frontend/utils/errorHandling.ts`.
- **Form Validation**: Use reactive validation patterns and provide real-time feedback.

### Frontend Workflow

- **Design**: Define the data structure and API endpoints required for the feature.
- **Implement**: Use Composition API patterns (`ref`, `reactive`, `computed`, `watch`) and strict TypeScript typing.
- **Test**: Ensure all components implement error handling and loading states.
- **Refactor**: Optimize code for performance and maintainability.

### Frontend Best Practices

- Always use `<script setup>` syntax for Vue components.
- Show loading states for all async operations.
- Avoid using `any` type unless absolutely necessary.
- Provide user feedback for errors and successes.
- Use Bootstrap classes for consistent styling.
