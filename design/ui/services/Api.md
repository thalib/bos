# API Service Layer Specification

## Overview

This document outlines the design and implementation details for `api.ts` (`frontend\app\utils\api.ts`) service layer. The purpose of this layer is to abstract API handling calls, providing a flexible and dynamic interface for interacting with backend services. The service layer is designed to evolve with the backend, making no assumptions about the data structure or endpoints.

## Key Features

- **Dynamic API Handling**: Adapts to changes in backend data structures and endpoints without assuming fixed formats.
- **Standardized Response Handling**: Ensures all responses follow the backend's standardized format for consistency.
- **Extensibility**: Allows adding custom interceptors and extending functionalities for specific use cases.
- **Error Handling**: Provides consistent error handling, logs all errors, and ensures user-friendly messages. Internal errors or stack traces are included in the `details` field for debugging purposes but are not exposed to the frontend UI.
- **Lightweight and Modular**: Focuses on core API handling with minimal overhead and efficient interactions.
- **CRUD Operations**: Includes generic methods for Create, Read, Update, and Delete operations.
- **Performance**: Optimized for efficient API interactions while maintaining flexibility.
- **Shared Access**: Use the `useApiService` function to access the API service layer, avoiding direct use of `$fetch` or other HTTP clients.
- **Adherence to Standards**: Ensures all API interactions comply with the standardized response format defined by the backend.
- **Leverage Auto-Imports**: Nuxt 4 auto-imports utilities and composables. Use the `#imports` alias for explicit imports when needed.

## Testing

Follow Nuxt 4 testing guidelines. Use `@nuxt/test-utils` for unit and end-to-end testing. Ensure tests are written for all critical API methods and integrations.

## Service Interface

The API service layer provides the following methods:

### Core Methods

- `request<T>(url: string, options?: RequestOptions): Promise<ApiResponse<T>>`
  - Performs a generic API request with customizable options.
- `addRequestInterceptor(interceptor: RequestInterceptor): () => void`
  - Adds a request interceptor and returns a function to remove it.
- `addResponseInterceptor(interceptor: ResponseInterceptor): () => void`
  - Adds a response interceptor and returns a function to remove it.

### CRUD Methods

- `fetch<T>(resource: string, params?: PaginationParams): Promise<ApiResponse<PaginatedResponse<T>>>`
  - Fetches a list of resources with optional pagination and filtering.
- `get<T>(resource: string, id: string | number): Promise<ApiResponse<T>>`
  - Fetches a single resource by its ID.
- `create<T, D>(resource: string, data: D): Promise<ApiResponse<T>>`
  - Creates a new resource.
- `update<T, D>(resource: string, id: string | number, data: D): Promise<ApiResponse<T>>`
  - Updates an existing resource.
- `delete<T>(resource: string, id: string | number): Promise<ApiResponse<T>>`
  - Deletes a resource by its ID.

### Utility Methods

- `buildUrl(resource: string, params?: Record<string, any>): { url: string; notifications: Notification[] }`
  - Constructs a URL with query parameters and validates them. Adds notifications for invalid parameters.
- `handleError(error: any): ApiError`
  - Processes and formats API errors. Includes stack traces in the `details` field for debugging purposes.

## Backend Integration

The API service layer is designed to integrate seamlessly with the backend architecture, which includes the following components:

### Standardized Endpoints

#### `GET /api/v1/{resource}`

- Retrieves a paginated list of resources.
- Supports query parameters for pagination, sorting, filtering, and searching.
- Refer to [index.md](design/api/index.md) for detailed request and response structure.

#### `GET /api/v1/{resource}/{id}`

- Retrieves a single resource by its ID.
- Validates the `id` parameter and returns `404 Not Found` for non-existent resources.
- Refer to [show.md](design/api/show.md) for detailed request and response structure.

#### `POST /api/v1/{resource}`

- Creates a new resource.
- Validates the request body using `StoreResourceRequest`.
- Returns `201 Created` on success.
- Refer to [store.md](design/api/store.md) for detailed request and response structure.

#### `PUT /api/v1/{resource}/{id}`

- Updates an existing resource by its ID.
- Validates the request body using `UpdateResourceRequest`.
- Returns `200 OK` on success.
- Refer to [update.md](design/api/update.md) for detailed request and response structure.

#### `DELETE /api/v1/{resource}/{id}`

- Deletes a resource by its ID.
- Validates the `id` parameter and handles related dependencies appropriately.
- Returns `200 OK` on success.
- Refer to [destroy.md](design/api/destroy.md) for detailed request and response structure.

## Request Options

The `RequestOptions` interface allows customization of API requests with the following properties:

- `method`: HTTP method (e.g., 'GET', 'POST').
- `headers`: Request headers.
- `body`: Request payload.
- `params`: Query parameters.
- `signal`: Abort signal for request cancellation.
- `responseType`: Expected response type (e.g., 'json', 'text').

## Response Format

All responses follow the standardized format defined by the backend:

**Success**:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": ...,
  "pagination": { ... },
  ...
}
```

**Error**:

```json
{
  "success": false,
  "message": "Error occurred",
  "error": {
    "code": "ERROR_CODE",
    "details": "Detailed error message"
  }
}
```

## References Specifications

- [Authentication Service Specification](design/ui/services/Auth.md).
- [Notifiy Service Specification](design/ui/services/Notify.md).
- [Nuxt 4 Testing Guide](https://nuxt.com/docs/4.x/getting-started/testing).

## Usage Examples

```typescript
// Import the service
import { useApiService } from '~/app/utils/api'

const apiService = useApiService()

// Fetch paginated resources
const users = await apiService.fetch('users', { page: 1, limit: 10 })

// Get single resource
const user = await apiService.get('users', 1)

// Create new resource
const newUser = await apiService.create('users', {
  name: 'John Doe',
  email: 'john@example.com'
})

// Update resource
const updatedUser = await apiService.update('users', 1, {
  name: 'John Smith'
})

// Delete resource
await apiService.delete('users', 1)

// Custom request
const response = await apiService.request('/api/custom-endpoint', {
  method: 'POST',
  body: { customData: 'value' }
})
```

This API service layer provides a flexible and dynamic interface for interacting with backend services, ensuring adherence to best practices and project constraints.
