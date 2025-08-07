# GET Single Resource Endpoint (Show)

Retrieve a single resource by its identifier.

## Request Structure

### Endpoint Format
```
GET /api/v1/{resource}/{id}
```

### Authentication Required
✅ **Yes** - All resource endpoints require `auth:sanctum` middleware

### Path Parameters

- **`id`** _(string | integer)_: **Path Parameter**, is the unique identifier of the resource to be retrieved.

### Example Endpoints
```bash
GET /api/v1/products/{id}
GET /api/v1/users/{id}
GET /api/v1/estimates/{id}
```

### Authentication

Include the bearer token in the Authorization header:

```bash
curl -X GET "https://api.example.com/api/v1/products/123" \
  -H "Authorization: Bearer {your_token_here}"
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

### Example Response

```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": {
    "id": 123,
    "name": "Example Resource",
    "description": "This is an example resource",
    "status": "active",
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

## Available Resources

This endpoint structure applies to all auto-generated resources in the BOS system:

- **Users** (`/api/v1/users/{id}`) - User accounts and profiles
- **Products** (`/api/v1/products/{id}`) - Product catalog and inventory  
- **Estimates** (`/api/v1/estimates/{id}`) - Business estimates and quotations
- **Test Models** (`/api/v1/test-models/{id}`) - Development testing models

For resource-specific details, see:
- [Users Resource](resources/users.md)
- [Products Resource](resources/products.md)
- [Estimates Resource](resources/estimates.md)

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
