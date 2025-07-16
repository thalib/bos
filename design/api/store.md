# POST Endpoint Documentation

The `POST` method is used to create a new resource. This document outlines the standardized request and response structure, validation rules, and best practices for implementing `POST` endpoints.

---

## Request Structure

```bash
POST /api/v1/{resource}
```

- `POST` request targets a specific **_resource_**.
- Request body must contain the necessary fields to create the resource.
- **Authentication**: All requests require `auth:sanctum` middleware.
- **Validation**: Requests are validated using `StoreResourceRequest`.
- **Transaction Handling**: Operations are wrapped in transactions to ensure data consistency.

Example URLs

```bash
POST /api/v1/products
POST /api/v1/users
POST /api/v1/orders
```

---

## Response Structure

```json
{
  "success": true/false,
  "message": "<string>",
  "data": { /* created resource object */ },
  "error": { /* Refer to design/api/error.md for detailed structure */ }
}
```

### Success Response (HTTP 201 Created)

```json
{
  "success": true,
  "message": "Resource created successfully.",
  "data": {
    "id": 123,
    "name": "New Resource",
    "description": "This is a new resource",
    "status": "active",
    "created_at": "2025-07-15T10:30:00Z",
    "updated_at": "2025-07-15T10:30:00Z"
  }
}
```

### Error Response Example

Refer to [error.md](../error.md) for detailed error response structure.

---

## Validation & Creation Rules

- Request body must include all required fields with valid formats.
- Missing or invalid fields must return a `422 Unprocessable Entity` response.
- All endpoints require authentication (`auth:sanctum` middleware) and user permission validation before creation.
- Use transactions to ensure data consistency.
- Log all creation operations for compliance and debugging.
- Implement rate limiting to prevent abuse.
- Validate input to prevent injection attacks.
- Always provide clear feedback about the creation result.

---
