# PUT/PATCH Resource Update Endpoint (Update)

Update an existing resource.

## Request Structure

### Endpoint Format
```
PUT /api/v1/{resource}/{id}
PATCH /api/v1/{resource}/{id}
```

### Authentication Required
✅ **Yes** - All resource endpoints require `auth:sanctum` middleware

### Method Differences
- **PUT**: Updates all fields of a resource (full update)
- **PATCH**: Updates only specified fields (partial update)

### Path Parameters
- **`id`** _(string | integer)_: Unique identifier of the resource to update

### Headers
```
Content-Type: application/json
Authorization: Bearer {access_token}
```

### Example Endpoints
```bash
PUT /api/v1/products/{id}
PATCH /api/v1/users/{id}
PUT /api/v1/estimates/{id}
```

### Authentication

```bash
curl -X PATCH "https://api.example.com/api/v1/products/123" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{"price": 129.99, "status": "active"}'
```

### Request Examples

#### PUT Request (Full Update)
```json
{
  "name": "Updated Product Name",
  "description": "Updated product description",
  "status": "active",
  "price": 129.99
}
```

#### PATCH Request (Partial Update)
```json
{
  "price": 129.99,
  "status": "inactive"
}
```

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

### Example Response

```json
{
  "success": true,
  "message": "Resource updated successfully",
  "data": {
    "id": 123,
    "name": "Updated Resource",
    "description": "This is an updated resource",
    "status": "active",
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:45:00.000000Z"
  }
}
```

## Available Resources

This endpoint structure applies to all auto-generated resources in the BOS system:

- **Users** (`PUT/PATCH /api/v1/users/{id}`) - Update user accounts
- **Products** (`PUT/PATCH /api/v1/products/{id}`) - Update products
- **Estimates** (`PUT/PATCH /api/v1/estimates/{id}`) - Update business estimates
- **Test Models** (`PUT/PATCH /api/v1/test-models/{id}`) - Update test models

## Validation & Business Rules

- The `id` must be provided in the URL path and be a valid format
- Request body must include valid fields for update
- Non-existent IDs return `404 Not Found` response
- Operations are wrapped in database transactions for data consistency
- Each resource has specific validation rules defined in `UpdateResourceRequest`
- All update operations are logged for audit trails

For resource-specific validation rules and fields, see:
- [Users Resource](resources/users.md)
- [Products Resource](resources/products.md)
- [Estimates Resource](resources/estimates.md)
