# DELETE Resource Endpoint (Destroy)

Delete a resource.

## Request Structure

### Endpoint Format
```
DELETE /api/v1/{resource}/{id}
```

### Authentication Required
✅ **Yes** - All resource endpoints require `auth:sanctum` middleware

### Path Parameters
- **`id`** _(string | integer)_: Unique identifier of the resource to be deleted

### Example Endpoints
```bash
DELETE /api/v1/products/{id}
DELETE /api/v1/users/{id}
DELETE /api/v1/estimates/{id}
```

### Authentication

```bash
curl -X DELETE "https://api.example.com/api/v1/products/123" \
  -H "Authorization: Bearer {your_token_here}"
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

### Example Response

```json
{
  "success": true,
  "message": "Resource deleted successfully"
}
```

## Available Resources

This endpoint structure applies to all auto-generated resources in the BOS system:

- **Users** (`DELETE /api/v1/users/{id}`) - Delete user accounts
- **Products** (`DELETE /api/v1/products/{id}`) - Delete products
- **Estimates** (`DELETE /api/v1/estimates/{id}`) - Delete business estimates
- **Test Models** (`DELETE /api/v1/test-models/{id}`) - Delete test models

## Validation & Business Rules

- The `id` must be provided in the URL path and be a valid format
- Non-existent IDs return `404 Not Found` response
- Operations may check for dependencies before deletion
- Related resources are handled appropriately (cascade or dependency checks)
- All deletion operations are logged for audit trails
- Some resources may implement "soft delete" instead of permanent deletion

For resource-specific deletion behavior, see:
- [Users Resource](resources/users.md)
- [Products Resource](resources/products.md)
- [Estimates Resource](resources/estimates.md)
