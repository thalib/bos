# AI Agent Prompt Constraints for Thanzil Project

These constraints ensure consistent implementation patterns, prevent recurring issues, and maintain codebase quality across frontend (Nuxt 3) and backend (Laravel 12) development.

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

## Backend API and Route Constraints

- **Backend API and routes must follow these constraints:**
  1. **API endpoints for resources are auto-registered via `ApiResourceServiceProvider`**. Models with the `ApiResource` attribute are exposed as RESTful endpoints under `/api/v1/{resource}` (e.g., `/api/v1/users`).
  2. **All resource endpoints are protected by `auth:sanctum` middleware**. Only authenticated users can access, create, update, or delete resources.
  3. **API controllers must use the standardized response format** as implemented in `ApiResourceController`:
     - Success: `{ "success": true, "data": ..., "message": ... }`
     - Error: `{ "success": false, "error": { "code": ..., "message": ..., "details": ... } }`
  4. **Supported resource routes** (all require authentication):
     - `GET    /api/v1/{resource}`         → List resources
     - `POST   /api/v1/{resource}`         → Create resource
     - `GET    /api/v1/{resource}/{id}`    → Show resource
     - `PUT    /api/v1/{resource}/{id}`    → Update resource
     - `PATCH  /api/v1/{resource}/{id}`    → Update resource
     - `DELETE /api/v1/{resource}/{id}`    → Delete resource
     - `GET    /api/v1/{resource}/schema`  → Get form schema
     - `GET    /api/v1/{resource}/columns` → Get index columns config
     - `GET    /api/v1/{resource}/filters` → Get available filters
  5. **Validation** is handled via `StoreResourceRequest` and `UpdateResourceRequest` with clear error messages and proper HTTP status codes.
  6. **Never expose internal errors or stack traces to the frontend**. All errors must be logged and user-facing messages must be friendly.
  7. **Do not change the API response format unless explicitly requested.**

## Backend Constraints (Laravel 12)

### 1. API Response Format - Standardized Structure

- **ALWAYS return consistent JSON responses**
- **Use the standardized response format**:

  ```php
  // Success response
  return response()->json([
      'success' => true,
      'data' => $data,
      'message' => 'Operation completed successfully'
  ]);

  // Error response
  return response()->json([
      'success' => false,
      'error' => [
          'code' => 'ERROR_CODE',
          'message' => 'Human readable error message',
          'details' => $details
      ]
  ], $statusCode);
  ```

### 2. Resource Controllers - Follow Conventions

- **Use `ApiResourceController` patterns** for CRUD operations
- **Follow Laravel resource controller conventions**
- **Implement proper model validation**
- **Example endpoint structure**:
  - `GET /api/v1/{resource}` - List resources
  - `POST /api/v1/{resource}` - Create resource
  - `GET /api/v1/{resource}/{id}` - Show resource
  - `PUT /api/v1/{resource}/{id}` - Update resource
  - `DELETE /api/v1/{resource}/{id}` - Delete resource
  - `GET /api/v1/{resource}/filters` - Get available filters

### 3. Model Conventions - Eloquent Best Practices

- **Define fillable fields** explicitly
- **Use proper relationships** (hasMany, belongsTo, etc.)
- **Implement model methods** for business logic
- **Add filter methods** when needed:

```php
public function getApiFilters(): array
{
    return [
        'status' => [
            'label' => 'Status',
            'values' => ['all', 'active', 'inactive'],
            'parameter' => 'status'
        ]
    ];
}
```

### 4. Request Validation - Comprehensive Rules

- **ALWAYS validate incoming requests**
- **Use Form Request classes** for complex validation
- **Provide clear validation messages**
- **Example**:

```php
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email'
]);
```

### 5. Error Handling - Graceful Degradation

- **NEVER expose internal errors to frontend**
- **Log errors appropriately**
- **Return user-friendly error messages**
- **Use proper HTTP status codes**
- **Example**:
  ```php
  try {
      // Operation
  } catch (\Exception $e) {
      Log::error('Operation failed', ['error' => $e->getMessage()]);
      return response()->json([
          'success' => false,
          'error' => [
              'code' => 'OPERATION_FAILED',
              'message' => 'Unable to complete operation'
          ]
      ], 500);
  }
  ```

### 6. Database Migrations - Maintain Integrity

- **ALWAYS use migrations** for schema changes
- **Include proper indexes** for performance
- **Add foreign key constraints** where appropriate
- **Use descriptive migration names**

### 7. Authentication & Authorization - Security First

- **Use Laravel Sanctum** for API authentication
- **Implement proper middleware** for route protection
- **Validate user permissions** before operations
- **NEVER trust frontend data** without server-side validation

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

These constraints should be referenced and enforced during all development tasks to maintain consistency and quality across the Thanzil project.
