# POST /api/v1/auth/refresh

Refresh access tokens using a refresh token.

## Request

### Method
```
POST /api/v1/auth/refresh
```

### Headers
```
Content-Type: application/json
```

### Authentication Required
❌ **No** - This endpoint uses refresh token instead of access token

### Request Body

```json
{
  "refreshToken": "string"
}
```

#### Field Validation

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `refreshToken` | string | Yes | Valid refresh token obtained from login/register |

### Example Request

```bash
curl -X POST "https://api.example.com/api/v1/auth/refresh" \
  -H "Content-Type: application/json" \
  -d '{
    "refreshToken": "2|xyz789uvw012..."
  }'
```

## Response

### Success Response (HTTP 200)

```json
{
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
  },
  "accessToken": "3|new123access456...",
  "refreshToken": "4|new789refresh012...",
  "message": "Token refreshed successfully"
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `user` | object | Complete user information |
| `accessToken` | string | New bearer token for API authentication |
| `refreshToken` | string | New refresh token for future refreshes |
| `message` | string | Success confirmation message |

### Error Responses

#### Validation Error (HTTP 422)

```json
{
  "message": "Validation failed",
  "errors": {
    "refreshToken": ["The refresh token field is required."]
  }
}
```

#### Invalid Token Format (HTTP 401)

```json
{
  "message": "Invalid refresh token format"
}
```

#### Invalid Refresh Token (HTTP 401)

```json
{
  "message": "Invalid refresh token"
}
```

## Token Refresh Logic

1. **Token Validation**: The refresh token is validated against the database
   - Token must exist in the `personal_access_tokens` table
   - Token must be associated with a valid user
   - Token must have the correct abilities (`refresh`)

2. **Token Rotation**: The old refresh token is revoked and new tokens are generated
   - Old refresh token is deleted from database
   - New access token is created
   - New refresh token is created

3. **Security**: This implements token rotation for enhanced security
   - Each refresh token can only be used once
   - Compromised refresh tokens have limited lifespan

## Token Format

Refresh tokens follow Laravel Sanctum format:
```
{token_id}|{actual_token}
```

The API extracts the actual token part for database lookup.

## Usage Patterns

### Automatic Token Refresh
```javascript
// Example frontend implementation
async function refreshTokens() {
  try {
    const refreshToken = localStorage.getItem('refresh_token');
    const response = await api.post('/auth/refresh', {
      refreshToken: refreshToken
    });
    
    // Store new tokens
    localStorage.setItem('access_token', response.data.accessToken);
    localStorage.setItem('refresh_token', response.data.refreshToken);
    
    return response.data.accessToken;
  } catch (error) {
    // Refresh failed, redirect to login
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    router.push('/login');
    throw error;
  }
}
```

### API Interceptor Pattern
```javascript
// Automatically refresh tokens on 401 responses
api.interceptors.response.use(
  response => response,
  async error => {
    if (error.response?.status === 401) {
      try {
        const newToken = await refreshTokens();
        // Retry original request with new token
        error.config.headers.Authorization = `Bearer ${newToken}`;
        return api.request(error.config);
      } catch (refreshError) {
        // Refresh failed, user needs to login
        return Promise.reject(refreshError);
      }
    }
    return Promise.reject(error);
  }
);
```

## Security Considerations

1. **Single Use**: Each refresh token can only be used once
2. **Token Rotation**: New tokens are issued on each refresh
3. **Database Validation**: Tokens are validated against the database
4. **User Association**: Tokens are tied to specific users

## Best Practices

1. **Proactive Refresh**: Refresh tokens before access tokens expire
2. **Error Handling**: Handle refresh failures by redirecting to login
3. **Secure Storage**: Store refresh tokens securely (HttpOnly cookies recommended)
4. **Token Cleanup**: Clear tokens on refresh failure

## Related Endpoints

- [Login](login.md) - Obtain initial token pair
- [Register](register.md) - Obtain initial token pair
- [Logout](logout.md) - Revoke all tokens
- [Status](status.md) - Check authentication status