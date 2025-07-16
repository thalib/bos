# DELETE Endpoint Documentation

The `DELETE` method is used to remove a resource. This document outlines the standardized request and response structure, validation rules, and best practices for implementing `DELETE` endpoints.

---

## Request Structure

```bash
DELETE /api/v1/{resource}/{id}
```

- `DELETE` request targets a specific **_resource_** using its **_identifier_** in the URL.
- **`id`** _(string &#124; integer)_: **Path Parameter**, is unique identifier of the resource to be deleted.

Example URLs

```bash
DELETE /api/v1/products/{id}
DELETE /api/v1/users/{id}
DELETE /api/v1/orders/{id}
```

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

## Validation & Deletion Rules

- The `id` must be provided in the URL path and be a valid format (integer or string, as required by the model).
- Non-existent IDs must return a `404 Not Found` response.
- Invalid ID formats must return a `400 Bad Request` response.
- All endpoints require authentication (`auth:sanctum` middleware) and user permission validation before deletion.
- Related resources must be handled appropriately (e.g., cascade deletion or dependency checks). If dependencies prevent deletion, provide clear error messages and guidance for resolution.
- Use transactions to ensure data consistency.
- Log all delete operations for compliance and debugging.
- Implement rate limiting to prevent abuse.
- Validate the `id` parameter to prevent injection attacks.
- Always provide clear feedback about the deletion result.
- Consider implementing undo functionality for critical deletions.

---
