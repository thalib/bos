# DELETE Endpoint Documentation

The `DELETE` method is used to remove a resource. This document outlines the standardized request and response structure, validation rules, and best practices for implementing `DELETE` endpoints.

---

## Request Structure

A `DELETE` request targets a specific resource using its identifier in the URL.

### Example URLs

```
DELETE /api/v1/products/{id}
DELETE /api/v1/users/{id}
DELETE /api/v1/orders/{id}
```

### Path Parameters

- **`id`** _(string|integer)_: The unique identifier of the resource to delete.

---

## Response Structure

```json
{
  "success": true/false,
  "message": "<string>",
  "error": { /* Refer to design/api/error.md for detailed structure */ }
}
```

### Success Response (HTTP 200 OK)

```json
{
  "success": true,
  "message": "Resource deleted successfully."
}
```

### Error Response Example

Refer to [error.md](#file:design/api/error.md) for detailed error response structure.

---

## Validation Rules

- ID must be provided in the URL path.
- ID must be a valid format (integer or string depending on the model).
- Non-existent IDs should return a `404 Not Found` response.
- Invalid ID formats should return a `400 Bad Request` response.

---

## Deletion Behavior

- Handle related resources appropriately (e.g., cascade deletion or dependency checks).
- Provide clear error messages if dependencies prevent deletion.
- Use transactions to ensure data consistency.

---

## Security Considerations

- All endpoints require authentication (`auth:sanctum` middleware).
- Validate user permissions before allowing deletion.
- Implement rate limiting to prevent abuse.
- Validate the `id` parameter to prevent injection attacks.
- Log all delete operations for compliance and debugging.

---

## Best Practices

- Validate permissions and input before deletion.
- Provide clear feedback about the deletion result.
- Handle dependencies gracefully and provide guidance for resolution.
- Use transactions to ensure data consistency.
- Consider implementing undo functionality for critical deletions.

---
