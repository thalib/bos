# REST API Response for DELETE/destroy endpoint

The DELETE/destroy endpoint must return JSON responses in the following standardized structure for deleting resources:

## Response Structure

```json
{
  "success": true/false,
  "message": "<string>",
  "data": { /* deleted resource object */ } | null,
  "notifications": [
    {
      "type": "<string>", // info, warning, success, etc.
      "message": "<string>"
    }
  ] | null,
  "error": { // when present when error happens
    "code": "<ERROR_CODE>",
    "details": [ /* array of error details */ ]
  }
}
```

### Response Fields

- **`success`** _(boolean)_: Always `true` for successful DELETE requests (HTTP 200), `false` for error responses (HTTP 4xx, 5xx)
- **`message`** _(string)_: User-friendly message describing the operation result
- **`data`** _(object|null)_: The deleted resource object with all its properties, `null` if soft delete or no data to return
- **`notifications`** _(array|null)_: Array of notification objects for user feedback, present only in success responses, `null` if no notifications. Not present in error responses.

## Success Response (Hard Delete)

```json
{
  "success": true,
  "message": "Resource deleted successfully",
  "data": {
    "id": 123,
    "name": "Deleted Resource",
    "description": "This resource has been deleted",
    "status": "deleted",
    "created_at": "2025-07-15T10:30:00Z",
    "updated_at": "2025-07-15T12:45:00Z",
    "deleted_at": "2025-07-15T13:00:00Z"
  },
  "notifications": null
}
```

## Success Response (Soft Delete)

```json
{
  "success": true,
  "message": "Resource deleted successfully",
  "data": null,
  "notifications": [
    {
      "type": "success",
      "message": "Resource has been moved to trash"
    },
    {
      "type": "info",
      "message": "Resource can be restored within 30 days"
    }
  ]
}
```

## Error Response

```json
{
  "success": false,
  "message": "Resource not found",
  "error": {
    "code": "NOT_FOUND",
    "details": [
      "The requested resource does not exist"
    ]
  }
}
```

- **`error.code`** _(string)_: Machine-readable error code
- **`error.details`** _(array)_: Array of detailed error information

## Notifications

Array of notification objects for user feedback. Present only in success responses. If no notifications, set to `null`. Not present in error responses.

```json
"notifications": [
  {
    "type": "success",
    "message": "Resource deleted successfully"
  },
  {
    "type": "warning",
    "message": "Related resources will be affected"
  },
  {
    "type": "info",
    "message": "This action can be undone within 30 days"
  }
] | null
```

### Notification Properties

- **`type`** _(string)_: Notification type (info, warning, success, etc.)
- **`message`** _(string)_: Human-readable notification message

### Notification Types

- **`info`**: Informational messages (e.g., restore options, cleanup schedules)
- **`warning`**: Non-critical issues (e.g., related resources affected)
- **`success`**: Success confirmations

### Notification Behavior

- Notifications provide feedback about the deletion process
- Multiple notifications can be present in a single response
- Notifications are used for informational purposes and warnings about consequences
- Soft delete scenarios should include information about restore options

### Error Codes for DELETE/destroy

| Code                    | HTTP Status | Description                          |
| ----------------------- | ----------- | ------------------------------------ |
| `NOT_FOUND`             | 404         | Resource not found                   |
| `UNAUTHORIZED`          | 401         | Authentication required              |
| `FORBIDDEN`             | 403         | Access denied                        |
| `METHOD_NOT_ALLOWED`    | 405         | DELETE method not supported          |
| `CONFLICT`              | 409         | Cannot delete (dependencies exist)   |
| `RATE_LIMIT_EXCEEDED`   | 429         | Too many requests                    |
| `INTERNAL_SERVER_ERROR` | 500         | Server error                         |
| `DATABASE_ERROR`        | 500         | Database operation failed            |

## Complete DELETE/destroy Success Response Example

```json
{
  "success": true,
  "message": "Product deleted successfully",
  "data": {
    "id": 456,
    "name": "Premium Widget",
    "price": 99.99,
    "category": "Electronics",
    "description": "High-quality widget with premium features",
    "status": "deleted",
    "stock_quantity": 0,
    "created_at": "2025-07-15T10:30:00Z",
    "updated_at": "2025-07-15T12:45:00Z",
    "deleted_at": "2025-07-15T13:00:00Z"
  },
  "notifications": [
    {
      "type": "success",
      "message": "Product deleted successfully"
    },
    {
      "type": "info",
      "message": "All related orders have been updated"
    },
    {
      "type": "warning",
      "message": "This action cannot be undone"
    }
  ]
}
```

## Soft Delete Success Response Example

```json
{
  "success": true,
  "message": "Product moved to trash successfully",
  "data": null,
  "notifications": [
    {
      "type": "success",
      "message": "Product has been moved to trash"
    },
    {
      "type": "info",
      "message": "Product can be restored from trash within 30 days"
    }
  ]
}
```

## Not Found Error Response Example

```json
{
  "success": false,
  "message": "Resource not found",
  "error": {
    "code": "NOT_FOUND",
    "details": [
      "The requested resource with ID 999 does not exist"
    ]
  }
}
```

## Dependency Conflict Error Response Example

```json
{
  "success": false,
  "message": "Cannot delete resource due to existing dependencies",
  "error": {
    "code": "CONFLICT",
    "details": [
      "This resource has 5 associated orders that must be handled first",
      "Delete or reassign dependent resources before proceeding"
    ]
  }
}
```

## Access Denied Error Response Example

```json
{
  "success": false,
  "message": "Access denied",
  "error": {
    "code": "FORBIDDEN",
    "details": [
      "You do not have permission to delete this resource"
    ]
  }
}
```

## Database Error Response Example

```json
{
  "success": false,
  "message": "Database operation failed while deleting the resource",
  "error": {
    "code": "DATABASE_ERROR",
    "details": [
      "Unable to delete resource due to database constraint"
    ]
  }
}
```

## HTTP Status Codes for DELETE/destroy

- **200 OK**: Resource successfully deleted
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Access denied to delete resource
- **404 Not Found**: Resource not found
- **405 Method Not Allowed**: DELETE method not supported for this endpoint
- **409 Conflict**: Cannot delete due to dependencies or constraints
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error or database operation failed

## URL Parameters

The DELETE/destroy endpoint requires the following URL parameter:

### Path Parameters

- **`id`** _(string|integer)_: The unique identifier of the resource to delete

### Example URLs

```
DELETE /api/v1/products/123
DELETE /api/v1/users/456
DELETE /api/v1/orders/789
```

### Parameter Validation

- ID parameter must be provided in the URL path
- ID must be a valid format (integer or string depending on model)
- Non-existent IDs should return 404 Not Found
- Invalid ID formats should return 404 Not Found

## Deletion Behavior

### Hard Delete

- Permanently removes the resource from the database
- Returns the deleted resource data in the response
- Cannot be undone
- Should be used carefully and only when appropriate

### Soft Delete

- Marks the resource as deleted but keeps it in the database
- Sets a `deleted_at` timestamp
- Resource becomes hidden from normal queries
- Can be restored later if needed
- Returns `null` in the data field

### Cascade Behavior

- Related resources should be handled appropriately
- Dependent resources may need to be deleted or updated
- Foreign key constraints should be considered
- Notifications should inform about affected related resources

## Security Considerations

- All endpoints require authentication (`auth:sanctum` middleware)
- Access control should be enforced based on user permissions
- Rate limiting to prevent abuse
- Input validation for ID parameter to prevent injection attacks
- Audit logging for delete operations
- Consider implementing confirmation requirements for critical deletions

## Dependency Management

- Check for dependent resources before deletion
- Provide clear error messages about dependencies
- Offer guidance on how to handle dependencies
- Consider implementing cascade deletion rules
- Validate business rules before allowing deletion

## Restore Functionality (for Soft Deletes)

- Soft deleted resources should be restorable
- Implement restore endpoints if needed
- Provide clear information about restore time limits
- Consider implementing automatic cleanup for old soft deleted resources
- Maintain audit trail for restore operations

## Best Practices

- Always validate permissions before deletion
- Provide clear feedback about the deletion result
- Handle dependencies gracefully
- Use soft deletes for user-facing resources
- Implement audit logging for compliance
- Consider implementing bulk deletion for efficiency
- Provide undo functionality where appropriate
- Use transactions to ensure data consistency
