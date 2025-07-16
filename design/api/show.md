# GET Endpoint Documentation

The `GET` method is used to retrieve a single resource. This document outlines the standardized request and response structure, validation rules, and best practices for implementing `GET` endpoints.

---

## Request Structure

```bash
GET /api/v1/{resource}/{id}
```

- `GET` request targets a specific **_resource_** using its **_identifier_** in the URL.
- **`id`** _(string | integer)_: **Path Parameter**, is the unique identifier of the resource to be retrieved.

Example URLs

```bash
GET /api/v1/products/{id}
GET /api/v1/users/{id}
GET /api/v1/orders/{id}
```

---

## Response Structure

```json
{
  "success": true/false,
  "message": "<string>",
  "data": { /* single resource object */ },
  "error": { /* Refer to design/api/error.md for detailed structure */ }
}
```

### Success Response (HTTP 200 OK)

```json
{
  "success": true,
  "message": "Resource retrieved successfully.",
  "data": {
    "id": 123,
    "name": "Example Resource",
    "description": "This is an example resource",
    "status": "active",
    "created_at": "2025-07-15T10:30:00Z",
    "updated_at": "2025-07-15T10:30:00Z"
  }
}
```

### Error Response Example

Refer to [error.md](../error.md) for detailed error response structure.

---

## Validation & Retrieval Rules

- The `id` must be provided in the URL path and be a valid format (integer or string, as required by the model).
- Non-existent IDs must return a `404 Not Found` response.
- Invalid ID formats must return a `400 Bad Request` response.
- All endpoints require authentication (`auth:sanctum` middleware) and user permission validation before retrieval.
- Sensitive data must be filtered based on user roles.
- Use eager loading to optimize database queries.
- Log all retrieval operations for compliance and debugging.
- Implement rate limiting to prevent abuse.
- Validate the `id` parameter to prevent injection attacks.
- Always provide clear feedback about the retrieval result.

---
