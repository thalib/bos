# REST API Response for GET/index endpoint

The GET/index endpoint must return JSON responses in the following standardized structure for retrieving lists of resources:

## Response Structure

```json
{
  "success": true/false,
  "message": "<string>",
  "data": [ /* array of resource objects */ ],
  "pagination": {
    "totalItems": <int>,
    "currentPage": <int>,
    "itemsPerPage": <int>,
    "totalPages": <int>,
    "urlPath": "<string>",
    "urlQuery": <string|null>,
    "nextPage": "<string|null>",
    "prevPage": "<string|null>"
  } | null,
  "search": <string|null>,
  "sort": {
    "column": "<string>",
    "dir": "<\"asc\"|\"desc\">"
  } | null,
  "filters": { "applied": <object|null>, "available": <object|null> }|null,
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
    "details": [ /* array of error details */ ]
  }
}
```

### Response Fields

- **`success`** _(boolean)_: Always `true` for successful GET requests (HTTP 200), `false` for error responses (HTTP 4xx, 5xx)
- **`message`** _(string)_: Optional message, user friendly short message
- **`data`** _(array)_: Array of resource objects, always an array even if empty `[]`
- **`pagination`** _(object|null)_: Pagination details for the resource list, `null` if pagination is not used
- **`search`** _(string|null)_: Search query string applied, `null` if no search
- **`sort`** _(object|null)_: Sorting configuration applied, `null` if no sorting
- **`filters`** _(object|null)_: Filter information with `applied` and `available` properties, `null` if no filters available
- **`schema`** _(array|null)_: Field definitions for dynamic forms, `null` if not available
- **`columns`** _(array)_: Column configuration for tables, always present (falls back to ID column)
- **`notifications`** _(array|null)_: Array of notification objects for user feedback, present only in success responses, `null` if no notifications. Not present in error responses.

## Error

```json
{
  "success": false,
  "message": "<User-friendly error message>",
  "error": {
    "code": "<ERROR_CODE>",
    "details": [
      /* array of error details */
    ]
  }
}
```

- **`error.code`** _(string)_: Machine-readable error code
- **`error.details`** _(array)_: Optional array of detailed error information

## Notifications

Array of notification objects for user feedback. Present only in success responses. If no notifications, set to `null`. Not present in error responses.

```json
"notifications": [
  {
    "type": "<string>", // info, warning, success, etc.
    "message": "<string>"
  }
] | null
```

### Notification Properties

- **`type`** _(string)_: Notification type (info, warning, success, etc.)
- **`message`** _(string)_: Human-readable notification message

### Notification Types

- **`info`**: Informational messages (e.g., parameter defaults applied)
- **`warning`**: Non-critical issues (e.g., invalid parameters ignored)
- **`success`**: Success confirmations

### Notification Behavior

- Notifications are used for parameter fallbacks instead of returning errors
- Invalid pagination parameters fall back to defaults with warning notification
- Invalid sort parameters fall back to defaults with warning notification
- Invalid filter parameters are ignored with warning notification
- Invalid search parameters are ignored with warning notification
- Multiple notifications can be present in a single response

### Error Codes for GET/index

| Code                    | HTTP Status | Description                 |
| ----------------------- | ----------- | --------------------------- |
| `UNAUTHORIZED`          | 401         | Authentication required     |
| `FORBIDDEN`             | 403         | Access denied               |
| `NOT_FOUND`             | 404         | Resource endpoint not found |
| `METHOD_NOT_ALLOWED`    | 405         | GET method not supported    |
| `RATE_LIMIT_EXCEEDED`   | 429         | Too many requests           |
| `INTERNAL_SERVER_ERROR` | 500         | Server error                |

**Note**: Invalid request parameters (pagination, sort, filter, search) do not return errors. Instead, the system falls back to default values and includes appropriate notifications in the response.

## Pagination

Pagination details for resource lists. Present when the endpoint returns a paginated list of resources.

```json
"pagination": {
  "totalItems": <int>,
  "currentPage": <int>,
  "itemsPerPage": <int>,
  "totalPages": <int>,
  "urlPath": "<string>",
  "urlQuery": <string|null>,
  "nextPage": "<string|null>",
  "prevPage": "<string|null>"
}
```

## Sort

Sorting configuration applied to the resource list. `null` if no sorting was applied.

```json
"sort": {
  "column": "<string>",
  "dir": "<\"asc\"|\"desc\">"
}
```

## Filters

Information about filters applied and available for the resource.

```json
"filters": {
  "applied": { "field": "<string>", "value": "<mixed>" } | null,
  "available": [
    { "field": "<string>", "label": "<string>", "values": [ /* array of available values */ ] }
  ] | null
}
```

### Filter Behavior

- Only one filter can be active at a time
- Applying a new filter replaces the existing filter
- If model doesn't define `getApiFilters()` method, `filters` should be `null`

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
        "default": <mixed>,
        "options": [ /* array for select fields */ ],
        "maxLength": <int>,
        "min": <number>,
        "max": <number>,
        "pattern": "<string>",
        "unique": <boolean>,
        "properties": <object>,
        "minItems": <int>,
        "maxItems": <int>
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
    "type": "<string>",
    "format": "<string>",
    "width": "<string>",
    "align": "<string>",
    "hidden": <boolean>
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
        [
            'field' => 'name',
            'label' => 'Product Name',
            'sortable' => true,
            'clickable' => true,
            'search' => true,
        ],
        [
            'field' => 'price',
            'label' => 'Price',
            'sortable' => true,
            'format' => 'currency',
            'align' => 'right',
        ],
        // ... more columns
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
    "clickable": true,
    "search": false,
    "format": "text",
    "align": "left"
  }
]
```

## Complete GET/index Response Example

```json
{
  "success": true,
  "message": "Resources retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Sample Product",
      "price": 299.99,
      "status": "active",
      "created_at": "2025-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "totalItems": 50,
    "currentPage": 1,
    "itemsPerPage": 10,
    "totalPages": 5,
    "urlPath": "http://localhost:8000/api/v1/products",
    "urlQuery": null,
    "nextPage": "http://localhost:8000/api/v1/products?page=2",
    "prevPage": null
  },
  "search": null,
  "sort": {
    "column": "name",
    "dir": "asc"
  },
  "filters": {
    "applied": { "field": "status", "value": "active" },
    "available": [
      {
        "field": "status",
        "label": "Status",
        "values": ["active", "inactive"]
      }
    ]
  },
  "notifications": [
    {
      "type": "warning",
      "message": "Invalid sort column 'invalid_column' ignored, defaulted to 'name'"
    },
    {
      "type": "info",
      "message": "Using default page size of 10 items per page"
    }
  ],
  "schema": [
    {
      "group": "General Information",
      "fields": [
        {
          "field": "name",
          "type": "string",
          "label": "Product Name",
          "placeholder": "Enter product name",
          "required": true,
          "maxLength": 255
        },
        {
          "field": "active",
          "type": "checkbox",
          "label": "Active",
          "required": false,
          "default": true
        }
      ]
    },
    {
      "group": "Pricing",
      "fields": [
        {
          "field": "price",
          "type": "decimal",
          "label": "Price",
          "placeholder": "0.00",
          "required": true,
          "min": 0,
          "prefix": "₹"
        }
      ]
    }
  ],
  "columns": [
    {
      "field": "name",
      "label": "Name",
      "sortable": true,
      "clickable": true,
      "search": true
    },
    {
      "field": "price",
      "label": "Price",
      "format": "currency",
      "sortable": true
    }
  ]
}
```

## Error Response Example

```json
{
  "success": false,
  "message": "Access denied",
  "notifications": null,
  "error": {
    "code": "FORBIDDEN",
    "details": ["User does not have permission to access this resource"]
  }
}
```

## Response with No Notifications Example

```json
{
  "success": true,
  "message": "Resources retrieved successfully",
  "data": [],
  "pagination": null,
  "search": null,
  "sort": null,
  "filters": null,
  "schema": null,
  "columns": [
    {
      "field": "id",
      "label": "ID",
      "sortable": true,
      "clickable": true,
      "search": false,
      "format": "text",
      "align": "left"
    }
  ],
  "notifications": null
}
```

## HTTP Status Codes for GET/index

- **200 OK**: Successful GET request with resource list
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Access denied to resource list
- **404 Not Found**: Resource endpoint not found
- **405 Method Not Allowed**: GET method not supported for this endpoint
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

**Note**: Invalid request parameters do not result in 400 Bad Request errors. Instead, the system uses default values and provides notifications about the parameter issues.

## Query Parameters

The `GET/index` endpoint accepts the following query parameters to control the response:

### Pagination Parameters

- **`page`** _(integer)_: Page number to retrieve (default: 1)
- **`per_page`** _(integer)_: Number of items per page (default: 15, max: 100), if not set fallback to default

### Sorting Parameters

- **`sort`** _(string)_: Column name to sort by
- **`dir`** _(string)_: Sort direction, either `asc` or `desc` (default: `asc`)

### Filtering Parameters

- **`filter`** _(string)_: Filter format: `field:value` (only one filter can be active at a time)

### Search Parameters

- **`search`** _(string)_: Search query string to filter results

### Parameter Examples

```
# Basic pagination
?page=2&per_page=20

# Sorting by name in ascending order
?sort=name&dir=asc

# Sorting by created date in descending order
?sort=created_at&dir=desc

# Filtering by status
?filter=status:active

# Search for products containing "mobile"
?search=mobile

# Combined parameters
?page=2&per_page=20&sort=name&dir=asc&filter=status:active&search=mobile
```

### Parameter Validation

Invalid parameters do not result in error responses. Instead, the system falls back to sensible values and provides notifications:

- **`page`**: Invalid values (≤0) fall back to page 1 with warning notification. Values greater than total pages fall back to last available page with warning notification
- **`per_page`**: Values outside 1-100 range fall back to maximum (100) or minimum (1) respectively with warning notification. Values exceeding 100 are capped at 100, values below 1 are set to 1
- **`sort`**: Invalid column names fall back to default sort with warning notification
- **`dir`**: Invalid direction values fall back to 'asc' with warning notification
- **`filter`**: Invalid format or field names are ignored with warning notification
- **`search`**: Invalid search terms (less than 2 characters) are ignored with warning notification

### Notification Examples for Parameter Issues

```json
"notifications": [
  {
    "type": "warning",
    "message": "Invalid page number '0', using page 1"
  },
  {
    "type": "warning",
    "message": "Page number '15' exceeds available pages (7), using last page 7"
  },
  {
    "type": "warning", 
    "message": "Page size '150' exceeds maximum of 100, using maximum 100"
  },
  {
    "type": "warning", 
    "message": "Page size '0' below minimum of 1, using minimum 1"
  },
  {
    "type": "warning",
    "message": "Sort column 'invalid_field' not found, using default 'id'"
  },
  {
    "type": "warning",
    "message": "Sort direction 'invalid' not recognized, using 'asc'"
  },
  {
    "type": "warning",
    "message": "Filter format 'invalid_format' not recognized, filter ignored"
  },
  {
    "type": "warning",
    "message": "Search term too short (minimum 2 characters), search ignored"
  }
]
```

### Default Behavior

When no query parameters are provided, the endpoint returns:

- First page of results
- Default page size (15 items)
- Default sort order (first sortable column from `getIndexColumns()` method, fallback to ID ascending if no sortable columns)
- No filters applied
- No search applied

Example default request:

```bash
GET /api/v1/products
```

Is equivalent to:

```bash
GET /api/v1/products?page=1&per_page=15&sort=id&dir=asc
```
