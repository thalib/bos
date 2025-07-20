# API Service Layer and Authentication Service

This implementation provides a centralized API service layer and authentication service for the Nuxt 4 application, following the specifications outlined in the design documents.

## Services Implemented

### 1. API Service Layer (`app/utils/api.ts`)

A centralized service for handling all HTTP requests with the following features:

- **Generic request method** for customizable API calls
- **CRUD operations**: `fetch`, `get`, `create`, `update`, `delete`
- **Request/Response interceptors** for automatic token management
- **Standardized error handling** with user-friendly messages
- **URL building utilities** with query parameter support
- **Singleton pattern** for consistent usage across the application

#### Usage Example:

```typescript
import { useApiService } from '~/app/utils/api'

const api = useApiService()

// Fetch paginated resources
const users = await api.fetch('users', { page: 1, per_page: 10 })

// Get single resource
const user = await api.get('users', 1)

// Create new resource
const newUser = await api.create('users', { name: 'John', email: 'john@example.com' })

// Update resource
const updatedUser = await api.update('users', 1, { name: 'John Updated' })

// Delete resource
await api.delete('users', 1)
```

### 2. Authentication Service (`app/utils/auth.ts`)

A comprehensive authentication service with the following features:

- **Core methods**: `login`, `logout`, `refreshToken`, `checkAuthStatus`
- **Token management**: Secure storage and retrieval of authentication tokens
- **User management**: Storage and management of user data
- **Reactive properties**: `isAuthenticated`, `currentUser`, `isInitialized`
- **Automatic initialization** from stored authentication data
- **Integration with API service** for automatic token attachment

#### Usage Example:

```typescript
import { useAuthService } from '~/app/utils/auth'

const auth = useAuthService()

// Login
const response = await auth.login({
  email: 'user@example.com',
  password: 'password123'
})

// Check authentication status
const isAuth = auth.isAuthenticated.value
const user = auth.currentUser.value

// Logout
await auth.logout()

// Refresh token
await auth.refreshToken()
```

### 3. Authentication Middleware (`app/middleware/auth.ts`)

Route protection middleware that:

- Redirects unauthenticated users to the login page
- Preserves the intended destination for post-login redirection
- Waits for authentication initialization before checking status

#### Usage:

```vue
<script setup>
definePageMeta({
  middleware: 'auth'
})
</script>
```

## Key Features

### Request/Response Interceptors

Both services integrate seamlessly:

- **Request interceptors** automatically add authentication headers
- **Response interceptors** handle token refresh on expiration
- **Error handling** provides consistent user-friendly messages

### Standardized Response Format

All API responses follow the backend's standardized format:

```typescript
interface ApiResponse<T> {
  success: boolean
  message: string
  data?: T
  pagination?: PaginationMeta
  error?: ApiError
}
```

### Type Safety

Full TypeScript support with:

- Strongly typed interfaces for all service methods
- Generic types for flexible API responses
- Comprehensive error type definitions

## Configuration

The services are configured via `nuxt.config.ts`:

```typescript
export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBase: '/api/v1'  // Base URL for API calls
    }
  }
})
```

## Testing

Basic integration tests are included using Vitest:

```bash
npm run test        # Run tests in watch mode
npm run test:run    # Run tests once
```

## Demo

A comprehensive demo page is available at `/demo` that showcases:

- Authentication functionality (login, logout, token refresh)
- API service usage
- Error handling
- Reactive state management

## Architecture

```
Backend <-> api.ts <-> auth.ts <-> Frontend Components
```

- **Backend**: Laravel API with standardized responses
- **api.ts**: Centralized HTTP request handling
- **auth.ts**: Authentication state management
- **Frontend Components**: Vue components using the services

## Dependencies

- **Nuxt 3.17.7**: Framework
- **@nuxt/test-utils**: Testing utilities
- **Vitest**: Test runner
- **TypeScript**: Type safety
- **Bootstrap 5.3**: UI framework (via CDN)

## File Structure

```
app/
├── utils/
│   ├── api.ts          # API Service Layer
│   └── auth.ts         # Authentication Service
├── middleware/
│   └── auth.ts         # Authentication Middleware
└── pages/
    ├── index.vue       # Home page
    └── demo.vue        # Demo page
tests/
└── utils/
    ├── api.basic.test.ts   # API service tests
    └── auth.basic.test.ts  # Auth service tests
```

This implementation provides a robust foundation for API interactions and authentication management in the Nuxt 4 application.