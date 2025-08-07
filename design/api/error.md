# API Error Response Format

All API endpoints in the BOS system follow a standardized error response format.

## Error Response Structure

```json
{
  "success": false,
  "message": "Human-readable error message",
  "error": {
    "code": "ERROR_CODE",
    "details": ["array", "of", "error", "details"]
  }
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Always `false` for error responses |
| `message` | string | User-friendly error message |
| `error.code` | string | Machine-readable error code |
| `error.details` | array | Optional array of detailed error information |

## HTTP Status Codes & Error Codes

| HTTP | Error Code | Description | When It Occurs |
|------|------------|-------------|----------------|
| 200 | OK | Operation successful | Success responses |
| 400 | BAD_REQUEST | Invalid request parameters | Malformed requests, invalid data types |
| 401 | UNAUTHORIZED | Authentication required | Missing or invalid bearer token |
| 403 | FORBIDDEN | Access denied | Valid token but insufficient permissions |
| 404 | NOT_FOUND | Resource not found | Invalid resource ID or endpoint |
| 405 | METHOD_NOT_ALLOWED | HTTP method not supported | Wrong HTTP method for endpoint |
| 409 | CONFLICT | Resource conflict | Unique constraint violations |
| 422 | UNPROCESSABLE_ENTITY | Validation errors | Field validation failures |
| 429 | TOO_MANY_REQUESTS | Rate limit exceeded | Too many requests from client |
| 500 | INTERNAL_SERVER_ERROR | Server error | Unexpected server-side errors |
| 503 | SERVICE_UNAVAILABLE | Service unavailable | Server maintenance or overload |

## Common Error Examples

### Authentication Errors

#### Missing Token (401)
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "error": {
    "code": "UNAUTHORIZED",
    "details": ["Bearer token is required"]
  }
}
```

#### Invalid Token (401)
```json
{
  "success": false,
  "message": "Invalid authentication token.",
  "error": {
    "code": "UNAUTHORIZED", 
    "details": ["Token has expired or is invalid"]
  }
}
```

#### Insufficient Permissions (403)
```json
{
  "success": false,
  "message": "Access denied.",
  "error": {
    "code": "FORBIDDEN",
    "details": ["User does not have permission to access this resource"]
  }
}
```

### Validation Errors

#### Field Validation (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "error": {
    "code": "UNPROCESSABLE_ENTITY",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."],
      "username": ["The username has already been taken."]
    }
  }
}
```

#### Invalid Data Format (400)
```json
{
  "success": false,
  "message": "Invalid request format",
  "error": {
    "code": "BAD_REQUEST",
    "details": ["Request body must be valid JSON"]
  }
}
```

### Resource Errors

#### Resource Not Found (404)
```json
{
  "success": false,
  "message": "Resource not found",
  "error": {
    "code": "NOT_FOUND",
    "details": ["User with ID 999 does not exist"]
  }
}
```

#### Unique Constraint Violation (409)
```json
{
  "success": false,
  "message": "Resource conflict",
  "error": {
    "code": "CONFLICT",
    "details": ["Email address is already registered"]
  }
}
```

### Rate Limiting (429)
```json
{
  "success": false,
  "message": "Too many requests",
  "error": {
    "code": "TOO_MANY_REQUESTS",
    "details": ["Rate limit exceeded. Try again in 60 seconds."]
  }
}
```

### Server Errors

#### Internal Server Error (500)
```json
{
  "success": false,
  "message": "Internal server error",
  "error": {
    "code": "INTERNAL_SERVER_ERROR",
    "details": ["An unexpected error occurred. Please try again later."]
  }
}
```

#### Service Unavailable (503)
```json
{
  "success": false,
  "message": "Service temporarily unavailable",
  "error": {
    "code": "SERVICE_UNAVAILABLE",
    "details": ["Server is undergoing maintenance. Please try again later."]
  }
}
```

## Error Handling Best Practices

### For API Clients

1. **Always Check Success Field**: Check the `success` boolean before processing data
2. **Handle Common Errors**: Implement specific handling for 401, 403, 404, 422, and 500 errors
3. **Display User-Friendly Messages**: Use the `message` field for user-facing error messages
4. **Log Detailed Errors**: Log the `error.code` and `error.details` for debugging
5. **Implement Retry Logic**: For 429 and 503 errors, implement exponential backoff

### Frontend Error Handling Example

```javascript
async function handleApiRequest(apiCall) {
  try {
    const response = await apiCall();
    
    if (!response.data.success) {
      // Handle API-level errors
      const error = response.data.error;
      
      switch (error.code) {
        case 'UNAUTHORIZED':
          // Redirect to login
          router.push('/login');
          break;
        case 'FORBIDDEN':
          // Show access denied message
          showError('Access denied');
          break;
        case 'UNPROCESSABLE_ENTITY':
          // Show validation errors
          showValidationErrors(error.details);
          break;
        case 'TOO_MANY_REQUESTS':
          // Show rate limit message
          showError('Too many requests. Please wait and try again.');
          break;
        default:
          // Show generic error
          showError(response.data.message);
      }
      return null;
    }
    
    return response.data.data;
  } catch (networkError) {
    // Handle network/HTTP errors
    if (networkError.response?.status === 401) {
      router.push('/login');
    } else {
      showError('Network error. Please check your connection.');
    }
    return null;
  }
}
```

## Special Notes

### Query Parameter Handling

Invalid query parameters (pagination, sort, filter, search) do **NOT** return errors. Instead:
- Invalid values are replaced with sensible defaults
- Warning notifications are included in successful responses
- This ensures consistent API behavior and better user experience

### Error Logging

All errors are logged server-side with:
- User context (if authenticated)
- Request details (endpoint, parameters, body)
- Error stack traces (for server errors)
- Timestamp and request ID for correlation

### Security Considerations

- Internal server errors do not expose sensitive system information
- Database errors are parsed and sanitized before returning to clients
- Stack traces and system paths are never included in API responses
- All error responses are logged for security monitoring
