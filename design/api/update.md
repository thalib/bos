# PUT/PATCH Endpoint Documentation

The `PUT` and `PATCH` methods are used to update an existing resource. This document outlines the standardized request and response structure, validation rules, and best practices for implementing `PUT` and `PATCH` endpoints.

---

## Request Structure

```bash
PUT /api/v1/{resource}/{id}
PATCH /api/v1/{resource}/{id}
```

- `PUT` request updates all fields of a specific **_resource_** using its **_identifier_** in the URL.
- `PATCH` request updates only specified fields of a specific **_resource_** using its **_identifier_** in the URL.
- **`id`** _(string | integer)_: **Path Parameter**, is the unique identifier of the resource to be updated.
- **Authentication**: All requests require `auth:sanctum` middleware.
- **Validation**: Requests are validated using `UpdateResourceRequest`.
- **Transaction Handling**: Operations are wrapped in transactions to ensure data consistency.

Example URLs

```bash
PUT /api/v1/products/{id}
PATCH /api/v1/users/{id}
PUT /api/v1/orders/{id}
```

### Request Data Structure

The `PUT` and `PATCH` endpoints accept JSON request bodies with the fields to be updated. Ensure all required fields are included for `PUT` requests, while `PATCH` requests allow partial updates.

#### Example JSON for `PUT` Request

```json
{
  "name": "Updated Product Name",
  "description": "Updated product description",
  "status": "active",
  "price": 129.99
}
```

#### Example JSON for `PATCH` Request

```json
{
  "price": 129.99,
  "status": "inactive"
}
```

#### Notes

- **Field Validation**: All fields must match the expected data types.
- **Required Fields**: `PUT` requests must include all required fields, while `PATCH` requests only need the fields being updated.
- **Security**: Input validation is mandatory to prevent injection attacks.
- **Business Rules**: Ensure updates comply with business rules and constraints.

---

## Response Structure

```json
{
  "success": true/false,
  "message": "<string>",
  "data": { /* updated resource object */ },
  "error": { /* Refer to design/api/error.md for detailed structure */ }
}
```

### Success Response (HTTP 200 OK)

```json
{
  "success": true,
  "message": "Resource updated successfully.",
  "data": {
    "id": 123,
    "name": "Updated Resource",
    "description": "This is an updated resource",
    "status": "active",
    "created_at": "2025-07-15T10:30:00Z",
    "updated_at": "2025-07-15T10:45:00Z"
  }
}
```

### Error Response Example

Refer to [error.md](../error.md) for detailed error response structure.

---

## Validation & Update Rules

- The `id` must be provided in the URL path and be a valid format (integer or string, as required by the model).
- Request body must include valid fields for update.
- Non-existent IDs must return a `404 Not Found` response.
- Invalid ID formats must return a `400 Bad Request` response.
- All endpoints require authentication (`auth:sanctum` middleware) and user permission validation before update.
- Use transactions to ensure data consistency.
- Log all update operations for compliance and debugging.
- Implement rate limiting to prevent abuse.
- Validate input to prevent injection attacks.
- Always provide clear feedback about the update result.

---
