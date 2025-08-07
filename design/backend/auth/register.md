# POST /api/v1/auth/register

Register a new user account.

## Request

### Method
```
POST /api/v1/auth/register
```

### Headers
```
Content-Type: application/json
```

### Request Body

```json
{
  "name": "string",
  "email": "string",
  "username": "string", 
  "whatsapp": "string",
  "password": "string",
  "password_confirmation": "string"
}
```

#### Field Validation

| Field | Type | Required | Validation Rules | Description |
|-------|------|----------|------------------|-------------|
| `name` | string | Yes | max:255 | User's full name |
| `email` | string | Yes | email, max:255, unique | Email address |
| `username` | string | Yes | max:255, unique | Unique username |
| `whatsapp` | string | Yes | regex:/^[0-9]{10,15}$/, unique | WhatsApp number (10-15 digits) |
| `password` | string | Yes | min:8, confirmed | Password (minimum 8 characters) |
| `password_confirmation` | string | Yes | - | Password confirmation (must match password) |

### Example Request

```bash
curl -X POST "https://api.example.com/api/v1/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "whatsapp": "1234567890",
    "password": "secretpassword123",
    "password_confirmation": "secretpassword123"
  }'
```

## Response

### Success Response (HTTP 201)

```json
{
  "message": "User registered successfully",
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
  "accessToken": "1|abc123def456...",
  "refreshToken": "2|xyz789uvw012..."
}
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `message` | string | Success message |
| `user` | object | Complete user object |
| `accessToken` | string | Bearer token for immediate API access |
| `refreshToken` | string | Token for refreshing access tokens |

### Error Responses

#### Validation Error (HTTP 422)

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "username": ["The username has already been taken."],
    "whatsapp": ["The whatsapp has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

#### Common Validation Errors

- **Email already exists**: Another user has registered with this email
- **Username already exists**: Another user has registered with this username  
- **WhatsApp already exists**: Another user has registered with this WhatsApp number
- **Password too short**: Password must be at least 8 characters
- **Password confirmation mismatch**: Password and confirmation don't match
- **Invalid WhatsApp format**: WhatsApp must be 10-15 digits only

## Default User Settings

New users are created with these default values:

| Field | Default Value | Description |
|-------|---------------|-------------|
| `active` | `true` | Account is active by default |
| `role` | `user` | Standard user role |
| `email_verified_at` | `null` | Email verification not implemented |

## Auto-Login After Registration

Upon successful registration, the user is automatically logged in:
- Access token and refresh token are provided in the response
- No separate login request needed
- Tokens can be used immediately for API authentication

## Usage After Registration

Include the access token in subsequent API requests:

```
Authorization: Bearer {accessToken}
```

## Business Rules

1. **Unique Constraints**: Email, username, and WhatsApp must be unique across all users
2. **Password Security**: Passwords are hashed using Laravel's Hash facade
3. **Account Status**: New accounts are automatically activated
4. **Default Role**: All new users get the "user" role by default

## Rate Limiting

Registration requests may be rate-limited to prevent spam. Check your server configuration for specific limits.

## Security Notes

- All passwords are hashed before storage
- Unique validation prevents duplicate accounts
- WhatsApp format validation ensures proper phone number format
- Tokens are immediately available for authenticated requests