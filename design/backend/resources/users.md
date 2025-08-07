# Users Resource API

Complete API documentation for the Users resource (`/api/v1/users`).

## Resource Overview

The Users resource manages user accounts, authentication, and user profiles in the BOS system.

**Base URL:** `/api/v1/users`
**Authentication:** Required (Bearer token)
**Auto-generated:** Yes (via `#[ApiResource]` attribute)

## Database Schema

| Field | Type | Default | Unique | Nullable | Description |
|-------|------|---------|--------|----------|-------------|
| `id` | integer | - | Yes | No | Primary key |
| `name` | string | - | No | No | User's full name |
| `username` | string | - | Yes | No | Unique username |
| `email` | string | - | Yes | No | Email address |
| `whatsapp` | string(15) | - | Yes | No | WhatsApp number |
| `active` | boolean | `true` | No | No | Account status |
| `role` | enum | `'user'` | No | No | User role (`admin`, `user`) |
| `email_verified_at` | timestamp | `null` | No | Yes | Email verification date |
| `password` | string | `null` | No | Yes | Hashed password |
| `remember_token` | string | `null` | No | Yes | Remember token |
| `created_at` | timestamp | - | No | No | Creation timestamp |
| `updated_at` | timestamp | - | No | No | Last update timestamp |

## Standard CRUD Operations

### List Users
```
GET /api/v1/users
```
- **Purpose**: Get paginated list of users with search, filtering, and sorting
- **Documentation**: [../index.md](../index.md)
- **Response**: Array of user objects with pagination metadata

### Create User
```
POST /api/v1/users
```
- **Purpose**: Create a new user account
- **Documentation**: [../store.md](../store.md)
- **Response**: Created user object

### Get User
```
GET /api/v1/users/{id}
```
- **Purpose**: Get single user by ID
- **Documentation**: [../show.md](../show.md)
- **Response**: Single user object

### Update User
```
PUT/PATCH /api/v1/users/{id}
```
- **Purpose**: Update existing user
- **Documentation**: [../update.md](../update.md)
- **Response**: Updated user object

### Delete User
```
DELETE /api/v1/users/{id}
```
- **Purpose**: Delete user account
- **Documentation**: [../destroy.md](../destroy.md)
- **Response**: Success confirmation

## Index Columns Configuration

The following columns are displayed in list views:

```json
[
  {
    "field": "name",
    "label": "Name",
    "sortable": true,
    "clickable": true,
    "search": true
  },
  {
    "field": "username",
    "label": "Username",
    "sortable": true,
    "search": true
  },
  {
    "field": "email",
    "label": "Email",
    "sortable": true,
    "search": true
  },
  {
    "field": "whatsapp",
    "label": "WhatsApp",
    "sortable": true,
    "search": true
  },
  {
    "field": "role",
    "label": "Role",
    "sortable": true,
    "search": true
  },
  {
    "field": "active",
    "label": "Status",
    "sortable": true,
    "format": "boolean",
    "align": "center"
  }
]
```

## API Schema for Forms

The API provides dynamic form schema for user creation/editing:

### Basic Information Group
- **`active`**: Checkbox (default: `true`)
- **`name`**: Text input (required, max 255 characters)
- **`username`**: Text input (required, max 255 characters, unique)
- **`email`**: Email input (required, max 255 characters, unique)

### Contact Information Group
- **`whatsapp`**: Text input (required, pattern: `/^[0-9]{10,15}$/`, unique)

### Account Settings Group
- **`role`**: Select (required, options: admin/user, default: user)
- **`password`**: Password input (optional for updates, min 8 characters)

## Validation Rules

### Create User (POST)
```json
{
  "name": "required|string|max:255",
  "username": "required|string|max:255|unique:users",
  "email": "required|email|max:255|unique:users",
  "whatsapp": "required|string|regex:/^[0-9]{10,15}$/|unique:users",
  "role": "required|in:admin,user",
  "password": "required|string|min:8",
  "active": "boolean"
}
```

### Update User (PUT/PATCH)
```json
{
  "name": "string|max:255",
  "username": "string|max:255|unique:users,username,{id}",
  "email": "email|max:255|unique:users,email,{id}",
  "whatsapp": "string|regex:/^[0-9]{10,15}$/|unique:users,whatsapp,{id}",
  "role": "in:admin,user",
  "password": "string|min:8",
  "active": "boolean"
}
```

## Searchable Fields

Users can be searched by these fields:
- `name`
- `username`
- `email`
- `whatsapp`
- `role`

## Business Rules

### Account Activation
- If password is empty/null during creation or update, `active` is automatically set to `false`
- Users with `active = false` cannot login

### Password Handling
- Passwords are automatically hashed using Laravel's Hash facade
- Password field is hidden in API responses
- Password is optional for updates (existing password retained if not provided)

### Unique Constraints
- `username`, `email`, and `whatsapp` must be unique across all users
- Validation enforces uniqueness with appropriate error messages

## Example Requests

### Create User
```bash
curl -X POST "https://api.example.com/api/v1/users" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "whatsapp": "1234567890",
    "role": "user",
    "password": "secretpassword123",
    "active": true
  }'
```

### Update User
```bash
curl -X PATCH "https://api.example.com/api/v1/users/1" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Smith",
    "active": false
  }'
```

### Search Users
```bash
curl -X GET "https://api.example.com/api/v1/users?search=john&filter=role:admin" \
  -H "Authorization: Bearer {token}"
```

## Response Examples

### Single User Response
```json
{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
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

### List Users Response
```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": [
    {
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
  ],
  "pagination": {
    "totalItems": 1,
    "currentPage": 1,
    "itemsPerPage": 15,
    "totalPages": 1
  },
  "columns": [/* column definitions */],
  "schema": [/* form schema */]
}
```

## Error Handling

Common error scenarios:

- **422 Validation Error**: Invalid data format or constraint violations
- **404 Not Found**: User ID doesn't exist
- **409 Conflict**: Unique constraint violations (email, username, whatsapp)
- **401 Unauthorized**: Invalid or missing authentication token
- **403 Forbidden**: Insufficient permissions

For detailed error response format, see [../error.md](../error.md).