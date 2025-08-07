# POST /api/v1/auth/logout

Logout the authenticated user and revoke all tokens.

## Request

### Method
```
POST /api/v1/auth/logout
```

### Headers
```
Content-Type: application/json
Authorization: Bearer {access_token}
```

### Authentication Required
✅ **Yes** - This endpoint requires authentication

### Request Body
No request body required.

### Example Request

```bash
curl -X POST "https://api.example.com/api/v1/auth/logout" \
  -H "Authorization: Bearer 1|abc123def456..."
```

## Response

### Success Response (HTTP 200)

```json
{
  "message": "Successfully logged out"
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `message` | string | Logout confirmation message |

### Error Responses

#### Unauthorized (HTTP 401)

```json
{
  "message": "Unauthenticated."
}
```

This error occurs when:
- No Authorization header is provided
- Invalid or expired token is provided
- Token format is incorrect

## Logout Behavior

1. **Token Revocation**: All tokens associated with the authenticated user are revoked
   - Access tokens are deleted
   - Refresh tokens are deleted
   - User cannot use any previously issued tokens

2. **Complete Logout**: Unlike selective token revocation, this endpoint performs a complete logout
   - All active sessions are terminated
   - All devices/applications using user tokens will need to re-authenticate

3. **Immediate Effect**: Token revocation is immediate
   - Subsequent requests with revoked tokens will fail
   - User must re-authenticate to access protected endpoints

## Security Implications

- **Session Security**: Ensures complete session termination
- **Multi-Device Logout**: Logs out user from all devices/applications
- **Token Security**: Prevents token reuse after logout

## Usage Patterns

### Standard Logout Flow
```bash
# 1. User initiates logout
POST /api/v1/auth/logout
Authorization: Bearer {token}

# 2. Client receives confirmation
# 3. Client clears stored tokens
# 4. Client redirects to login page
```

### Error Handling
```javascript
// Example frontend handling
try {
  await api.post('/auth/logout');
  // Clear local storage
  localStorage.removeItem('access_token');
  localStorage.removeItem('refresh_token');
  // Redirect to login
  router.push('/login');
} catch (error) {
  // Handle logout errors
  if (error.status === 401) {
    // Token already invalid, clear local storage anyway
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
  }
}
```

## Best Practices

1. **Client-Side Cleanup**: Always clear stored tokens after logout
2. **Redirect Handling**: Redirect user to login page after successful logout
3. **Error Tolerance**: Handle 401 errors gracefully (token might already be invalid)
4. **UI Feedback**: Provide user feedback during logout process

## Rate Limiting

Logout requests are typically not rate-limited since they improve security by terminating sessions.

## Related Endpoints

- [Login](login.md) - Authenticate and obtain new tokens
- [Refresh](refresh.md) - Refresh tokens without full logout
- [Status](status.md) - Check current authentication status