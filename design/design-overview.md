# BOS Project Overview

## General Design Principles

The BOS project follows a structured design approach to ensure robust, predictable, and maintainable APIs and frontend applications. The development process is guided by Test-Driven Development (TDD) and Design-Driven Development (DDD) principles, ensuring that all implementations adhere to predefined standards and best practices.

## Backend: TDD API-First Development with Laravel 12

The backend is designed as an API-only service, strictly following TDD and DDD principles. Its rules are detailed in `design/rules-api.md`.

- **Backend Source Directory**: `backend`
- **Standardized Response Format**: All API responses must adhere to the structure defined in `design/api/`.
- **Authentication**: All endpoints are protected by the `auth:sanctum` middleware.
- **Test-Driven Development (TDD) Workflow**:
  1. **Write a Test First**: Define the expected behavior in a test file (`backend/tests/Feature/`).
  2. **Run the Test**: Execute the test to confirm it fails initially, validating the test and indicating the feature is not yet implemented.
  3. **Write the Minimum Code**: Implement just enough code to make the test pass, adhering to `design/api/` documentation and delegating business logic to service classes.
  4. **Run the Test Again**: Verify the test passes after implementation.
  5. **Refactor the Code**: Ensure the code is clean, adheres to backend constraints (`ApiResourceServiceProvider`, `ApiResponseTrait`), and maintains passing tests.
  6. **Repeat for Additional Features**: Continue the TDD cycle for each new feature or change.
- **Design-Driven Development (DDD)**: All implementations must align with the design documentation in `design/api/`.

## Frontend: Data-Driven Development with Nuxt 4

The frontend is built using Nuxt 4, focusing on data-driven development and optimized UI/UX. It adheres to strict TypeScript typing and modern Vue 3 Composition API patterns, as outlined in `design/rules-ui.md`.

- **Frontend Source Directory**: `frontend`
- **Shared API Service**: All HTTP requests must use the shared API service (`/frontend/services/api.ts`). Authentication headers are automatically handled by the API service.
- **UI Styling**: Prioritize Bootstrap 5.3 classes over custom CSS and use Bootstrap Icons for consistent design.
- **Form Validation**: Use reactive validation patterns and provide real-time feedback.
- **Test-Driven Development (TDD) Workflow**:
  1. **Write a Test First**: Define the expected behavior in a test file (`frontend/tests/`).
  2. **Run the Test**: Execute the test to confirm it fails initially, validating the test and indicating the feature is not yet implemented.
  3. **Write the Minimum Code**: Implement just enough code to make the test pass, adhering to `design/rules-ui.md` documentation.
  4. **Run the Test Again**: Verify the test passes after implementation.
  5. **Refactor the Code**: Ensure the code is clean, adheres to frontend constraints (e.g., modularity, performance), and maintains passing tests.
  6. **Repeat for Additional Features**: Continue the TDD cycle for each new feature or change.
