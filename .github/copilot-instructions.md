# AI Agent Prompt Constraints for BOS Project


These constraints ensure consistent implementation patterns, prevent recurring issues, and maintain codebase quality across frontend (Nuxt 3) and backend (Laravel 12) development.

## General Backend API Architecture (Laravel 12)

- **API endpoints for Eloquent models are auto-registered**: Any model with the `ApiResource` attribute is automatically exposed as a RESTful resource under `/api/v1/{resource}` via `ApiResourceServiceProvider`.
- **All resource endpoints are protected by `auth:sanctum` middleware** (see `ApiResourceServiceProvider`).
- **Only the `ApiResourceController` is used for these endpoints**. This controller must contain ONLY the 5 basic CRUD methods: `index`, `show`, `store`, `update`, `destroy`. All query, validation, and business logic must be delegated to dedicated service classes (see codebase for `Resource*Service`).
- **Standardized API response format**: All responses must use the structure from `ApiResponseTrait`:
  - Success: `{ "success": true, "data": ..., "message": ..., ... }`
  - Error: `{ "success": false, "message": ..., "error": { "code": ..., "details": ... }, ... }`
  - Additional fields: `pagination`, `search`, `filters`, `schema`, `columns`, `notifications`, `meta` (see `ApiResponseTrait` for details).
- **Validation**: All requests must be validated using `StoreResourceRequest` and `UpdateResourceRequest`.
- **Error handling**: Never expose internal errors or stack traces to the frontend. All errors must be logged and user-facing messages must be friendly.
- **No direct database queries in controllers**; all logic must be in service classes.
- **API design and implementation must follow TDD and DDD**:
  - All endpoints and changes must be described in `/design/` API docs (e.g., `api-index.md`).
  - Feature tests in `tests/Feature/` must be written/updated before implementation, asserting response structure, codes, and error handling as per design docs.
  - Implementation must strictly follow both the design doc and the tests.


## Frontend Constraints (Nuxt 3)

### 1. API Usage - MANDATORY

- **ALWAYS use the shared API service** (`/frontend/services/api.ts`) for ALL HTTP requests
- **NEVER use direct `$fetch`, `fetch`, or any other HTTP client**
- **Pattern**: Import and use `useApiService()` for all API calls
- **Authentication**: The shared API service automatically handles authentication headers
- No SEO needed, code should be optimized for best performing UI/UX.
- **Error Handling**: The shared API service provides standardized error handling
- **Example**:

  ```typescript
  ❌ BAD - Direct fetch usage
  const data = await $fetch('/api/v1/users')

  ✅ GOOD - Shared API service usage
  const { request } = useApiService()
  const response = await request<User[]>('users')
  ```

### 2. CRUD Operations - Use apiCrud Service

- **Use `/frontend/services/apiCrud.ts`** for standard CRUD operations
- **Methods available**: `apiFetch`, `apiGet`, `apiCreate`, `apiUpdate`, `apiDelete`, `apiGetSchema`, `apiGetFilters`
- **Example**:
  ```typescript
  const { apiFetch, apiGet, apiCreate } = useApiCrud();
  const users = await apiFetch<User>("users", { page: 1, per_page: 20 });
  ```

### 3. UI Styling - Bootstrap First Approach

- **ALWAYS prioritize Bootstrap 5.3 classes** over custom CSS
- **Use custom styles ONLY when absolutely essential**
- **Common Bootstrap patterns to use**:
  - Layout: `container`, `row`, `col-*`, `d-flex`, `justify-content-*`, `align-items-*`
  - Components: `btn`, `btn-*`, `card`, `form-control`, `form-select`, `table`, `dropdown`
  - Utilities: `text-*`, `bg-*`, `p-*`, `m-*`, `border-*`, `rounded-*`
- **Example**:

  ```vue
  ❌ BAD - Unnecessary custom styles
  <div class="custom-card">
    <style scoped>
    .custom-card { padding: 1rem; background: white; border-radius: 0.5rem; }
    </style>

  ✅ GOOD - Bootstrap classes
  <div class="card p-3">
  ```

### 4. Error Handling - Comprehensive Coverage

- **ALL components must implement error handling**
- **Use the error handling utilities** from `/frontend/utils/errorHandling.ts`
- **Pattern**: Always provide user feedback for errors
- **Example**:

  ```typescript
  const { showErrorToast, showSuccessToast } = useToast();

  try {
    const response = await apiService.request("users");
    if (response.error) {
      showErrorToast(response.error.message);
      return;
    }
    showSuccessToast("Data loaded successfully");
  } catch (error) {
    showErrorToast("Failed to load data");
  }
  ```

### 5. Form Validation - Required Implementation

- **ALL forms must implement validation**
- **Use reactive validation patterns**
- **Provide real-time feedback**
- **Example**:
  ```typescript
  const errors = ref<Record<string, string>>({});
  const validateField = (field: string, value: any) => {
    if (!value) {
      errors.value[field] = "This field is required";
    } else {
      delete errors.value[field];
    }
  };
  ```

### 6. TypeScript - Strict Typing

- **Use TypeScript interfaces** from `/frontend/types/index.ts`
- **Create new interfaces when needed**
- **NEVER use `any` unless absolutely necessary**
- **Example**:

  ```typescript
  ❌ BAD - Loose typing
  const users: any[] = []

  ✅ GOOD - Proper typing
  const users: User[] = []
  ```

### 7. Vue 3 Composition API - Modern Patterns

- **ALWAYS use `<script setup>` syntax**
- **Use Composition API patterns** (ref, reactive, computed, watch)
- **NEVER use Options API**
- **Example**:

  ```vue
  ❌ BAD - Options API export default { data() { return { count: 0 } } } ✅ GOOD
  - Composition API
  <script setup lang="ts">
  const count = ref(0);
  </script>
  ```

### 8. Loading States - Always Provide Feedback

- **ALL async operations must show loading states**
- **Use consistent loading indicators**
- **Pattern**: Set loading before operation, clear after completion
- **Example**:

  ```typescript
  const loading = ref(false);

  const fetchData = async () => {
    loading.value = true;
    try {
      // API call
    } finally {
      loading.value = false;
    }
  };
  ```


## Backend API and Route Constraints (Auto-Registered Resources)

- **API endpoints for resources are auto-registered** via `ApiResourceServiceProvider` for all models with the `ApiResource` attribute.
- **All resource endpoints require authentication** (`auth:sanctum`).
- **Only the 5 CRUD methods are allowed** in `ApiResourceController` (`index`, `show`, `store`, `update`, `destroy`). No helper/business logic in the controller; all logic must be in service classes.
- **Standardized response format** (see `ApiResponseTrait`):
  - Always include: `success`, `data`, `message`, `pagination`, `search`, `filters`, `schema`, `columns`, `notifications`, `meta` (as appropriate).
  - Error responses must include: `success: false`, `message`, `error: { code, details }`, and optionally `validation_errors`.
- **Supported resource routes** (all require authentication):
  - `GET    /api/v1/{resource}`         → List resources
  - `POST   /api/v1/{resource}`         → Create resource
  - `GET    /api/v1/{resource}/{id}`    → Show resource
  - `PUT    /api/v1/{resource}/{id}`    → Update resource
  - `PATCH  /api/v1/{resource}/{id}`    → Update resource
  - `DELETE /api/v1/{resource}/{id}`    → Delete resource
- **Validation**: Use `StoreResourceRequest` and `UpdateResourceRequest` for all resource requests. Provide clear error messages and proper HTTP status codes.
- **Error handling**: Never expose internal errors or stack traces to the frontend. All errors must be logged and user-facing messages must be friendly.
- **API response format must not be changed unless explicitly requested.**
- **All API changes must be reflected in `/design/` docs and feature tests before implementation.**



## Backend Constraints (Laravel 12)

1. **API Response Format**
   - Always use the standardized response format from `ApiResponseTrait`.
   - All success and error responses must include the required fields (`success`, `data`, `message`, `error`, etc.) and additional metadata fields as appropriate.

2. **Resource Controllers**
   - Only `ApiResourceController` is used for auto-registered resources.
   - Only the 5 CRUD methods are allowed; all logic must be delegated to service classes.
   - Follow Laravel resource controller conventions.

3. **Model Conventions**
   - Define fillable fields explicitly.
   - Use proper Eloquent relationships.
   - Add filter methods as needed (see codebase for `getApiFilters`).

4. **Request Validation**
   - Always validate incoming requests using Form Request classes (`StoreResourceRequest`, `UpdateResourceRequest`).
   - Provide clear validation messages.

5. **Error Handling**
   - Never expose internal errors or stack traces to the frontend.
   - Log all errors appropriately.
   - Return user-friendly error messages and proper HTTP status codes.

6. **Database Migrations**
   - Always use migrations for schema changes.
   - Include proper indexes and foreign key constraints.
   - Use descriptive migration names.

7. **Authentication & Authorization**
   - Use Laravel Sanctum for API authentication.
   - All resource routes are protected by `auth:sanctum` middleware.
   - Validate user permissions before operations.
   - Never trust frontend data without server-side validation.

8. **TDD & DDD Enforcement**
   - All API changes must be described in `/design/` docs and covered by feature tests before implementation.
   - Tests must assert response structure, codes, and error handling as per design docs.
   - Implementation must strictly follow both the design doc and the tests.

## Common Anti-Patterns to Avoid

### Frontend Anti-Patterns

❌ Using direct fetch/HTTP calls instead of shared API service  
❌ Excessive custom CSS when Bootstrap classes exist  
❌ Missing error handling in components  
❌ Using `any` type without justification  
❌ Options API instead of Composition API  
❌ Missing loading states for async operations

### Backend Anti-Patterns

❌ Inconsistent API response formats  
❌ Exposing internal errors to frontend  
❌ Missing request validation  
❌ Direct database queries in controllers  
❌ Hardcoded values instead of configuration  
❌ Missing error logging

## Enforcement Guidelines

1. **Code Review Checklist**: Verify these constraints are followed
2. **Testing Requirements**: Ensure error handling and edge cases are covered
3. **Performance Considerations**: Monitor API response times and frontend loading states
4. **Security Validation**: Verify authentication and authorization implementation
5. **User Experience**: Confirm proper loading states, error messages, and form validation

These constraints should be referenced and enforced during all development tasks to maintain consistency and quality across the BOS project.
