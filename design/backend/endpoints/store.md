# POST Resource Creation Endpoint (Store)

Create a new resource.

## Request Structure

### Endpoint Format
```
POST /api/v1/{resource}
```

### Authentication Required
✅ **Yes** - All resource endpoints require `auth:sanctum` middleware

### Headers
```
Content-Type: application/json
Authorization: Bearer {access_token}
```

### Request Body

The request body should contain the necessary fields to create the resource. Each resource has specific validation rules and required fields.

### Example Endpoints
```bash
POST /api/v1/products
POST /api/v1/users
POST /api/v1/estimates
```

### Authentication

```bash
curl -X POST "https://api.example.com/api/v1/products" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{"name": "New Product", "price": 99.99}'
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

### Example Response

```json
{
  "success": true,
  "message": "Resource created successfully",
  "data": {
    "id": 123,
    "name": "New Resource",
    "description": "This is a new resource",
    "status": "active",
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

## Available Resources

This endpoint structure applies to all auto-generated resources in the BOS system:

- **Users** (`POST /api/v1/users`) - Create user accounts
- **Products** (`POST /api/v1/products`) - Create products
- **Estimates** (`POST /api/v1/estimates`) - Create business estimates
- **Test Models** (`POST /api/v1/test-models`) - Create test models

## Validation & Business Rules

- Request body must include all required fields with valid formats
- Missing or invalid fields return `422 Unprocessable Entity` response
- Each resource has specific validation rules defined in `StoreResourceRequest`
- Operations are wrapped in database transactions for data consistency
- All creation operations are logged for audit trails

For resource-specific validation rules and required fields, see:
- [Users Resource](resources/users.md)
- [Products Resource](resources/products.md)
- [Estimates Resource](resources/estimates.md)
