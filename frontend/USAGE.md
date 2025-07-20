# Service Usage Examples

## API Service Usage

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

## Authentication Service Usage

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

## Middleware Usage

```typescript
// In pages/dashboard.vue or similar protected page
export default definePageMeta({
  middleware: 'auth'
})
```

## Integration Example

The services work together automatically. When you login, all API calls will include the authentication header automatically:

```typescript
// Login first
await authService.login(credentials)

// All subsequent API calls include auth headers automatically
const protectedData = await apiService.fetch('protected-resources')
```