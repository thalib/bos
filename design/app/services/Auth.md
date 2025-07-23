# Authentication Service Design Specification

## Overview

The Authentication Service provides a centralized and reusable mechanism for handling user authentication and authorization in the BOS project. It abstracts API calls related to authentication and ensures consistent handling of authentication states across the application. This service acts as the **single point of truth** for all authentication-related operations in the Nuxt 4 application, ensuring a robust and standalone design.

## Features

- **Authentication API Integration**: Provides methods for login, logout, token refresh, and authentication status checks.
- **Middleware Support**: Ensures routes are protected and redirects unauthenticated users to the login page.
- **Error Handling**: Consistent error handling with user-friendly messages.
- **Notification Integration**: Displays success and error notifications for authentication actions.
- **Token and User Management**: Handles secure storage and retrieval of authentication tokens and user data.
- **Lightweight and Modular**: Focuses on core authentication functionality without imposing unnecessary constraints.
- **Interceptor Support**: Automatically attaches tokens to API requests and handles token refresh on expiration.
- **Leverage Auto-Imports**: Nuxt 4 auto-imports utilities and composables. Use the `#imports` alias for explicit imports when needed.

## Testing

Follow Nuxt 4 testing guidelines. Use `@nuxt/test-utils` for unit and end-to-end testing. Ensure tests are written for all critical authentication methods and middleware.

## Service Interface

The Authentication Service provides the following methods:

### Core Methods

- `login(credentials: LoginCredentials): Promise<ApiResponse<LoginResponse>>`
  - Authenticates a user with their credentials.
- `logout(): Promise<ApiResponse<void>>`
  - Logs out the current user.
- `refreshToken(): Promise<ApiResponse<LoginResponse>>`
  - Refreshes the authentication token using the stored refresh token.
- `checkAuthStatus(): Promise<ApiResponse<{ authenticated: boolean }>>`
  - Checks the current authentication status.

### Utility Methods

- `saveTokens(tokens: AuthTokens): void`
  - Saves authentication tokens securely in local storage.
- `getTokens(): AuthTokens`
  - Retrieves authentication tokens from local storage.
- `saveUser(userData: User | null): void`
  - Saves user data securely in local storage.
- `isAuthenticated: ComputedRef<boolean>`
  - Reactive property indicating whether the user is authenticated.
- `initAuth(): void`
  - Initializes the authentication state by loading stored tokens and user data.

## Backend Integration

The Authentication Service integrates with the backend API endpoints as follows:

### `POST /api/v1/auth/login`

- Authenticates a user and returns an access token.
- Validates the request body using `StoreResourceRequest`.
- Returns `200 OK` on success.
- Refer to [store.md](design/api/store.md) for detailed request and response structure.

### `POST /api/v1/auth/logout`

- Logs out the current user and invalidates the access token.
- Requires authentication via `auth:sanctum` middleware.
- Returns `200 OK` on success.
- Refer to [destroy.md](design/api/destroy.md) for detailed request and response structure.

### `POST /api/v1/auth/refresh`

- Refreshes the authentication token using a valid refresh token.
- Validates the request body using `UpdateResourceRequest`.
- Returns `200 OK` on success.
- Refer to [update.md](design/api/update.md) for detailed request and response structure.

### `GET /api/v1/auth/status`

- Checks the current authentication status of the user.
- Requires authentication via `auth:sanctum` middleware.
- Returns `200 OK` on success.
- Refer to [show.md](design/api/show.md) for detailed request and response structure.


## Middleware

- Ensure only authenticated users can access the app, redirecting unauthenticated users to the login page with their intended destination preserved for post-login redirection.
- Apply the `auth` middleware globally to protect all pages by default.
- Handle token expiration or missing tokens gracefully to maintain a seamless user experience.
- Test middleware to confirm proper redirection and access control.
- Use consistent error handling and notifications for unauthorized access attempts.

### Example Middleware Usage

```typescript
import { useAuthService } from "~/services/auth";
import { navigateTo } from "#app";
import { nextTick } from "vue";

export default defineNuxtRouteMiddleware((to, from) => {
  if (!process.client) {
    return;
  }

  const { isAuthenticated, isInitialized } = useAuthService();

  return new Promise((resolve) => {
    const checkAuth = () => {
      if (isInitialized.value) {
        if (!isAuthenticated.value && to.path !== "/") {
          resolve(
            navigateTo({
              path: "/",
              query: { redirect: to.fullPath },
            })
          );
        } else {
          resolve();
        }
      } else {
        setTimeout(checkAuth, 10);
      }
    };

    nextTick(() => {
      checkAuth();
    });
  });
});
```

## Example Usage

### Login

```typescript
import { useAuthService } from "~/services/auth";
import { useNotificationService } from "~/services/notificationService";

const { login } = useAuthService();
const { addNotification } = useNotificationService();

try {
  const response = await login({
    email: "user@example.com",
    password: "password123",
  });
  addNotification("Login successful!", "success");
} catch (error) {
  addNotification("Login failed. Please check your credentials.", "error");
}
```

### Logout

```typescript
import { useAuthService } from "~/services/auth";
import { useNotificationService } from "~/services/notificationService";

const { logout } = useAuthService();
const { addNotification } = useNotificationService();

try {
  await logout();
  addNotification("Logout successful!", "success");
} catch (error) {
  addNotification("Logout failed. Please try again.", "error");
}
```

### Refresh Token

```typescript
import { useAuthService } from "~/services/auth";
import { useNotificationService } from "~/services/notificationService";

const { refreshToken } = useAuthService();
const { addNotification } = useNotificationService();

try {
  const response = await refreshToken();
  addNotification("Token refreshed successfully!", "success");
} catch (error) {
  addNotification("Failed to refresh token.", "error");
}
```

### Check Authentication Status

```typescript
import { useAuthService } from "~/services/auth";
import { useNotificationService } from "~/services/notificationService";

const { checkAuthStatus } = useAuthService();
const { addNotification } = useNotificationService();

try {
  const response = await checkAuthStatus();
  if (response.data.authenticated) {
    addNotification("User is authenticated.", "success");
  } else {
    addNotification("User is not authenticated.", "info");
  }
} catch (error) {
  addNotification("Failed to check authentication status.", "error");
}
```

## Usage Examples

```typescript
// Import the service
import { useAuthService } from '~/app/utils/auth'

const authService = useAuthService()

// Login
const loginResult = await authService.login({
  email: 'user@example.com',
  password: 'password123'
})

// Check authentication status
console.log(authService.isAuthenticated.value) // reactive boolean

// Get current user
const currentUser = authService.getCurrentUser()

// Logout
await authService.logout()

// Manual token refresh
await authService.refreshToken()
```

## Design Considerations

- **Error Handling**: All errors are logged, and user-friendly messages are displayed using the Notifiy Service.
- **Security**: Authentication tokens are securely managed, and sensitive data is never exposed.
- **Performance**: The service is optimized for minimal overhead and efficient API interactions.
- **Single Responsibility**: The `auth.ts` file is the single point of truth for all authentication-related operations, ensuring consistency and maintainability.

## Implementation Notes

- Use the `useAuthService` function to access the Authentication Service.
- Ensure all authentication-related API interactions adhere to the standardized response format.
- Avoid direct use of `$fetch` or other HTTP clients; always use the shared API service.
- The service is designed to be standalone and modular, making it easy to integrate into other parts of the application.

## Related Specifications

For API handling and integration, refer to the [API Service Layer Specification](design/app/services/api.md).
