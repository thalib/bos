# GET /api/v1/auth/status

Check the current authentication status of the request.

## Request

### Method
```
GET /api/v1/auth/status
```

### Headers
```
Authorization: Bearer {access_token} // Optional
```

### Authentication Required
❌ **No** - This endpoint works with or without authentication

### Query Parameters
None

### Example Requests

#### Without Authentication
```bash
curl -X GET "https://api.example.com/api/v1/auth/status"
```

#### With Authentication
```bash
curl -X GET "https://api.example.com/api/v1/auth/status" \
  -H "Authorization: Bearer 1|abc123def456..."
```

## Response

### Authenticated User Response (HTTP 200)

```json
{
  "authenticated": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "whatsapp": "1234567890",
    "active": true,
    "role": "user",
    "email_verified_at": null,
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

### Unauthenticated Response (HTTP 200)

```json
{
  "authenticated": false,
  "user": null
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `authenticated` | boolean | `true` if valid token provided, `false` otherwise |
| `user` | object\|null | Complete user object if authenticated, `null` otherwise |

## Use Cases

### Authentication State Check
- **Frontend Apps**: Check if user is logged in on app startup
- **Route Guards**: Verify authentication before accessing protected pages
- **Session Validation**: Confirm token validity without making other API calls

### User Profile Sync
- **Profile Updates**: Get current user data after updates
- **Permission Checks**: Verify user role and permissions
- **Account Status**: Check if account is still active

### Token Validation
- **Silent Validation**: Check token validity without side effects
- **Session Monitoring**: Monitor authentication status in background
- **Error Prevention**: Prevent 401 errors by checking status first

## Implementation Patterns

### Frontend Route Guard
```javascript
// Vue.js route guard example
router.beforeEach(async (to, from, next) => {
  if (to.meta.requiresAuth) {
    try {
      const status = await api.get('/auth/status');
      if (status.data.authenticated) {
        next();
      } else {
        next('/login');
      }
    } catch (error) {
      next('/login');
    }
  } else {
    next();
  }
});
```

### Application Initialization
```javascript
// Check auth status on app startup
async function initializeApp() {
  try {
    const response = await api.get('/auth/status');
    if (response.data.authenticated) {
      // User is logged in
      store.commit('setUser', response.data.user);
      store.commit('setAuthenticated', true);
    } else {
      // User is not logged in
      store.commit('setAuthenticated', false);
      localStorage.removeItem('access_token');
      localStorage.removeItem('refresh_token');
    }
  } catch (error) {
    // Handle error - assume not authenticated
    store.commit('setAuthenticated', false);
  }
}
```

### Periodic Status Check
```javascript
// Check status periodically to detect token expiration
setInterval(async () => {
  if (localStorage.getItem('access_token')) {
    try {
      const response = await api.get('/auth/status');
      if (!response.data.authenticated) {
        // Token expired or invalid
        store.commit('setAuthenticated', false);
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
        router.push('/login');
      }
    } catch (error) {
      // Network error or server issue
      console.warn('Status check failed:', error);
    }
  }
}, 60000); // Check every minute
```

## Response Behavior

1. **Valid Token**: Returns user information and `authenticated: true`
2. **Invalid Token**: Returns `authenticated: false` and `user: null`
3. **No Token**: Returns `authenticated: false` and `user: null`
4. **Expired Token**: Returns `authenticated: false` and `user: null`

## Performance Considerations

- **Lightweight**: Fast endpoint that only checks authentication state
- **No Side Effects**: Doesn't modify any data or state
- **Cacheable**: Response can be cached briefly for performance
- **Low Cost**: Minimal database queries required

## Security Notes

- **Safe for Public Use**: No sensitive information exposed
- **Token Validation**: Properly validates tokens without leaking details
- **Error Handling**: Gracefully handles invalid tokens
- **No Authentication Required**: Safe to call without credentials

## Related Endpoints

- [Login](login.md) - Authenticate user
- [Logout](logout.md) - End authentication session
- [Refresh](refresh.md) - Refresh authentication tokens
- [Register](register.md) - Create new user account