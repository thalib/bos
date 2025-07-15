# REST API Response for PUT/PATCH/update endpoint

The PUT/PATCH/update endpoint must return JSON responses in the following standardized structure for updating existing resources:

## Response Structure

```json
{
  "success": true/false,
  "message": "<string>",
  "data": { /* updated resource object */ },
  "schema": <array|null>,
  "columns": <array>,
  "notifications": [
    {
      "type": "<string>", // info, warning, success, etc.
      "message": "<string>"
    }
  ] | null,
  "error": { // when present when error happens
    "code": "<ERROR_CODE>",
    "details": [ /* array of error details */ ],
    "validation_errors": { /* field-specific validation errors */ }
  }
}
```

### Response Fields

- **`success`** _(boolean)_: Always `true` for successful PUT/PATCH requests (HTTP 200), `false` for error responses (HTTP 4xx, 5xx)
- **`message`** _(string)_: User-friendly message describing the operation result
- **`data`** _(object)_: The updated resource object with all its properties
- **`schema`** _(array|null)_: Field definitions for dynamic forms, `null` if not available
- **`columns`** _(array)_: Column configuration for tables, always present (falls back to ID column)
- **`notifications`** _(array|null)_: Array of notification objects for user feedback, present only in success responses, `null` if no notifications. Not present in error responses.

## Success Response

```json
{
  "success": true,
  "message": "Resource updated successfully",
  "data": {
    "id": 123,
    "name": "Updated Resource Name",
    "description": "Updated description",
    "status": "active",
    "created_at": "2025-07-15T10:30:00Z",
    "updated_at": "2025-07-15T12:45:00Z"
  },
  "schema": [
    {
      "group": "Basic Information",
      "fields": [
        {
          "field": "name",
          "label": "Name",
          "type": "string",
          "required": true
        }
      ]
    }
  ],
  "columns": [
    {
      "field": "id",
      "label": "ID",
      "sortable": true,
      "clickable": true
    },
    {
      "field": "name",
      "label": "Name",
      "sortable": true,
      "search": true
    }
  ],
  "notifications": null
}
```

## Error Response

```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": [
      "Required field 'name' is missing"
    ],
    "validation_errors": {
      "name": [
        "The name field is required."
      ],
      "email": [
        "The email field must be a valid email address."
      ]
    }
  }
}
```

- **`error.code`** _(string)_: Machine-readable error code
- **`error.details`** _(array)_: Array of detailed error information
- **`error.validation_errors`** _(object)_: Field-specific validation errors (only present for validation errors)

## Notifications

Array of notification objects for user feedback. Present only in success responses. If no notifications, set to `null`. Not present in error responses.

```json
"notifications": [
  {
    "type": "success",
    "message": "Resource updated successfully"
  },
  {
    "type": "info",
    "message": "Additional processing completed"
  },
  {
    "type": "warning",
    "message": "Some fields were automatically adjusted"
  }
] | null
```

### Notification Properties

- **`type`** _(string)_: Notification type (info, warning, success, etc.)
- **`message`** _(string)_: Human-readable notification message

### Notification Types

- **`info`**: Informational messages (e.g., additional processing completed)
- **`warning`**: Non-critical issues (e.g., some fields were automatically adjusted)
- **`success`**: Success confirmations

### Notification Behavior

- Notifications provide feedback about the update process
- Multiple notifications can be present in a single response
- Notifications are used for non-critical issues and success confirmations

### Error Codes for PUT/PATCH/update

| Code                    | HTTP Status | Description                          |
| ----------------------- | ----------- | ------------------------------------ |
| `VALIDATION_ERROR`      | 422         | Request validation failed            |
| `NOT_FOUND`             | 404         | Resource not found                   |
| `UNAUTHORIZED`          | 401         | Authentication required              |
| `FORBIDDEN`             | 403         | Access denied                        |
| `METHOD_NOT_ALLOWED`    | 405         | PUT/PATCH method not supported       |
| `CONFLICT`              | 409         | Update conflict (concurrent update)  |
| `RATE_LIMIT_EXCEEDED`   | 429         | Too many requests                    |
| `INTERNAL_SERVER_ERROR` | 500         | Server error                         |
| `DATABASE_ERROR`        | 500         | Database operation failed            |

## Schema

Array of grouped field definitions for dynamic forms. `null` if model doesn't define `getApiSchema()` method.

```json
"schema": [
  {
    "group": "<string>",
    "fields": [
      {
        "field": "<string>",
        "label": "<string>",
        "type": "<string>",
        "required": <boolean>,
        "placeholder": "<string>",
        "default": "<mixed>",
        "options": ["<array>"],
        "min": <number>,
        "max": <number>
      }
    ]
  }
]
```

### Schema Field Properties

Each schema group contains:

- **`group`** _(string)_: Group name for UI organization. If empty string, fields are rendered without group heading
- **`fields`** _(array)_: Array containing field definitions in the desired order

Each field definition contains:

- **`field`** _(string)_: Field name/identifier
- **`label`** _(string)_: Human-readable field label
- **`type`** _(string)_: Data type (string, number, decimal, boolean, date, text, select, checkbox, textarea, object, array)
- **`required`** _(boolean)_: Whether field is required
- **`placeholder`** _(string)_: Field placeholder text (optional)
- **`default`** _(mixed)_: Default field value (optional)
- **`options`** _(array)_: Available options for select fields (optional)
- **`min`** _(number)_: Minimum value for number fields (optional)
- **`max`** _(number)_: Maximum value for number fields (optional)

## Columns

Array of column objects describing column configuration for resource tables. Always present - falls back to ID column if model doesn't define `getIndexColumns()` method.

```json
"columns": [
  {
    "field": "<string>",
    "label": "<string>",
    "sortable": <boolean>,
    "clickable": <boolean>,
    "search": <boolean>,
    "format": "<string>",
    "align": "<string>"
  }
]
```

### Column Properties

- **`field`** _(string)_: Field name/identifier that maps to database column
- **`label`** _(string)_: Human-readable column header
- **`sortable`** _(boolean)_: Whether column can be sorted (optional, default: false)
- **`clickable`** _(boolean)_: Whether column is clickable for navigation (optional, default: false)
- **`search`** _(boolean)_: Whether column is searchable (optional, default: false)
- **`format`** _(string)_: Display formatter (currency, number, date, datetime, percentage, etc.) (optional, default: text)
- **`align`** _(string)_: Text alignment (left, center, right) (optional, default left)

### Model Implementation

Models should implement the `getIndexColumns()` method returning an array of column objects with `field` property:

```php
public function getIndexColumns(): array
{
    return [
        ['field' => 'id', 'label' => 'ID', 'sortable' => true, 'clickable' => true],
        ['field' => 'name', 'label' => 'Name', 'sortable' => true, 'search' => true],
    ];
}
```

### Default Column Fallback

If model doesn't define `getIndexColumns()` method, the following default is used:

```json
"columns": [
  {
    "field": "id",
    "label": "ID",
    "sortable": true,
    "clickable": true
  }
]
```

## Complete PUT/PATCH/update Success Response Example

```json
{
  "success": true,
  "message": "Product updated successfully",
  "data": {
    "id": 456,
    "name": "Updated Premium Widget",
    "price": 129.99,
    "category": "Electronics",
    "description": "Updated high-quality widget with premium features",
    "status": "active",
    "stock_quantity": 45,
    "created_at": "2025-07-15T10:30:00Z",
    "updated_at": "2025-07-15T12:45:00Z"
  },
  "schema": [
    {
      "group": "Product Information",
      "fields": [
        {
          "field": "name",
          "label": "Product Name",
          "type": "string",
          "required": true,
          "placeholder": "Enter product name"
        },
        {
          "field": "price",
          "label": "Price",
          "type": "decimal",
          "required": true,
          "min": 0
        },
        {
          "field": "description",
          "label": "Description",
          "type": "textarea",
          "required": false,
          "placeholder": "Enter product description"
        }
      ]
    }
  ],
  "columns": [
    {
      "field": "id",
      "label": "ID",
      "sortable": true,
      "clickable": true
    },
    {
      "field": "name",
      "label": "Name",
      "sortable": true,
      "search": true
    },
    {
      "field": "price",
      "label": "Price",
      "sortable": true,
      "format": "currency",
      "align": "right"
    },
    {
      "field": "status",
      "label": "Status",
      "sortable": true
    }
  ],
  "notifications": [
    {
      "type": "success",
      "message": "Product updated successfully"
    },
    {
      "type": "info",
      "message": "Price change will be reflected in all active listings"
    }
  ]
}
```

## Validation Error Response Example

```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "VALIDATION_ERROR",
    "details": [
      "Name field is required",
      "Price must be a positive number"
    ],
    "validation_errors": {
      "name": [
        "The name field is required."
      ],
      "price": [
        "The price field must be a number.",
        "The price field must be at least 0."
      ]
    }
  }
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

## Database Error Response Example

```json
{
  "success": false,
  "message": "Database operation failed while updating the resource",
  "error": {
    "code": "DATABASE_ERROR",
    "details": [
      "Unable to update resource due to database constraint"
    ]
  }
}
```

## HTTP Status Codes for PUT/PATCH/update

- **200 OK**: Resource successfully updated
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Access denied to update resource
- **404 Not Found**: Resource not found
- **405 Method Not Allowed**: PUT/PATCH method not supported for this endpoint
- **409 Conflict**: Update conflict (concurrent modification)
- **422 Unprocessable Entity**: Request validation failed
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error or database operation failed

## URL Parameters

The PUT/PATCH/update endpoint requires the following URL parameter:

### Path Parameters

- **`id`** _(string|integer)_: The unique identifier of the resource to update

### Example URLs

```
PUT /api/v1/products/123
PATCH /api/v1/users/456
PUT /api/v1/orders/789
```

## Request Body

The PUT/PATCH/update endpoint accepts JSON request body with the resource data to be updated:

### PUT Request (Full Update)

```json
{
  "name": "Updated Product Name",
  "price": 129.99,
  "category": "Electronics",
  "description": "Updated product description",
  "status": "active"
}
```

### PATCH Request (Partial Update)

```json
{
  "price": 129.99,
  "description": "Updated product description"
}
```

### Request Validation

- All requests must be validated using `UpdateResourceRequest`
- Required fields must be present and valid for PUT requests
- PATCH requests allow partial updates with only changed fields
- Field types must match the expected data types
- Business rules and constraints must be enforced
- Validation errors should be returned in the standardized format

### Update Behavior

- **PUT**: Replaces the entire resource (all fields must be provided)
- **PATCH**: Updates only the provided fields (partial update)
- Both methods return the complete updated resource in the response
- Timestamps (updated_at) are automatically updated
- Optimistic locking should be considered for concurrent updates

### Security Considerations

- All endpoints require authentication (`auth:sanctum` middleware)
- Input validation is mandatory for all fields
- SQL injection protection through Eloquent ORM
- Mass assignment protection through model fillable fields
- Rate limiting to prevent abuse
- Access control should be enforced based on user permissions
- Audit logging for update operations

### Concurrency Handling

- Implement optimistic locking to handle concurrent updates
- Use version fields or timestamps to detect conflicts
- Return appropriate error responses for update conflicts
- Provide clear messaging for concurrent modification scenarios
