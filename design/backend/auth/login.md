# POST /api/v1/auth/login

Authenticate a user and obtain access tokens.

## Request

### Method
```
POST /api/v1/auth/login
```

### Headers
```
Content-Type: application/json
```

### Request Body

```json
{
  "username": "string", // Email or username
  "password": "string"
}
```

#### Field Validation

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `username` | string | Yes | User's email address or username |
| `password` | string | Yes | User's password |

### Example Request

```bash
curl -X POST "https://api.example.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "john@example.com",
    "password": "secretpassword"
  }'
```

## Response

### Success Response (HTTP 200)

```json
{
  "access_token": "1|abc123def456...",
  "refresh_token": "2|xyz789uvw012...",
  "token_type": "Bearer",
  "expires_in": null,
  "user": {
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "whatsapp": "1234567890"
  },
  "message": "Successfully logged in"
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `access_token` | string | Bearer token for API authentication |
| `refresh_token` | string | Token for refreshing access tokens |
| `token_type` | string | Always "Bearer" |
| `expires_in` | null | Sanctum tokens don't expire by default |
| `user` | object | Basic user information |
| `message` | string | Success message |

### Error Responses

#### Validation Error (HTTP 422)

```json
{
  "message": "Validation failed",
  "errors": {
    "username": ["The username field is required."],
    "password": ["The password field is required."]
  }
}
```

#### Invalid Credentials (HTTP 401)

```json
{
  "message": "The provided credentials are incorrect."
}
```

#### Account Inactive (HTTP 403)

```json
{
  "message": "Your account is not active. Please contact an administrator."
}
```

## Authentication Logic

1. **Username Resolution**: The API accepts either email or username in the `username` field
   - If the input is a valid email format, it searches by email
   - Otherwise, it searches by username

2. **Account Status**: Users must have `active = true` to login

3. **Token Generation**: 
   - Creates an access token for API authentication
   - Creates a refresh token for token renewal

## Usage After Login

Include the access token in subsequent API requests:

```
Authorization: Bearer {access_token}
```

## Rate Limiting

Login attempts may be rate-limited to prevent brute force attacks. Check your server configuration for specific limits.

## Security Notes

- Passwords are hashed using Laravel's Hash facade
- Tokens are generated using Laravel Sanctum
- Refresh tokens have limited scope (`refresh` ability)
- Failed login attempts should be logged for security monitoring