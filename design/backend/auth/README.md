# Authentication API Documentation

This directory contains documentation for all authentication-related endpoints in the BOS API.

## Available Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/v1/auth/login` | User login | No |
| POST | `/api/v1/auth/register` | User registration | No |
| POST | `/api/v1/auth/logout` | User logout | Yes |
| POST | `/api/v1/auth/refresh` | Refresh token | No |
| GET | `/api/v1/auth/status` | Check authentication status | No |

## Authentication Flow

1. **Login/Register** - Obtain an access token
2. **Use Token** - Include token in Authorization header for protected endpoints
3. **Refresh** - Use refresh endpoint to get new tokens when needed
4. **Logout** - Revoke tokens when user logs out

## Token Usage

Include the token in the Authorization header for all protected endpoints:

```
Authorization: Bearer {your_token_here}
```

## Common Response Structure

All auth endpoints follow the standard API response format:

```json
{
  "success": true/false,
  "message": "Human-readable message",
  "data": {
    // Auth-specific data (user info, tokens, etc.)
  },
  "error": null // Error details when success is false
}
```

## Error Handling

Authentication errors use these common HTTP status codes:

- `400` - Bad Request (missing/invalid parameters)
- `401` - Unauthorized (invalid credentials)
- `422` - Unprocessable Entity (validation errors)
- `429` - Too Many Requests (rate limiting)
- `500` - Internal Server Error

For detailed error response structure, see [../error.md](../error.md).