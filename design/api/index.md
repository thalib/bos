# REST API Response for GET/index Endpoint

## Request Structure

### Query Parameters

- **`page`** _(integer)_: Page number to retrieve (default: 1).
- **`per_page`** _(integer)_: Number of items per page (default: 15, max: 100).
- **`sort`** _(string)_: Column name to sort by.
- **`dir`** _(string)_: Sort direction, either `asc` or `desc` (default: `asc`).
- **`filter`** _(string)_: Filter format: `field:value`.
- **`search`** _(string)_: Search query string to filter results.

### Parameter Examples

```
?page=2&per_page=20
?sort=name&dir=asc
?filter=status:active
?search=mobile
```

### Parameter Validation

Invalid parameters do not result in error responses. Instead, the system falls back to sensible values and provides notifications:

- **`page`**: Invalid values (≤0) fall back to page 1 with warning notification. Values greater than total pages fall back to last available page with warning notification.
- **`per_page`**: Values outside 1-100 range fall back to maximum (100) or minimum (1) respectively with warning notification. Values exceeding 100 are capped at 100, values below 1 are set to 1.
- **`sort`**: Invalid column names fall back to default sort with warning notification.
- **`dir`**: Invalid direction values fall back to 'asc' with warning notification.
- **`filter`**: Invalid format or field names are ignored with warning notification.
- **`search`**: Invalid search terms (less than 2 characters) are ignored with warning notification.

### Default Behavior

When no query parameters are provided, the endpoint returns:

- First page of results.
- Default page size (15 items).
- Default sort order (first sortable column from `getIndexColumns()` method, fallback to ID ascending if no sortable columns).
- No filters applied.
- No search applied.

Example default request:

```bash
GET /api/v1/products
```

Is equivalent to:

```bash
GET /api/v1/products?page=1&per_page=15&sort=id&dir=asc
```

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

## Response Fields

### Success and Error Fields

- **`success`** _(boolean)_: Always `true` for successful GET requests (HTTP 200), `false` for error responses (HTTP 4xx, 5xx).
- **`message`** _(string)_: Optional user-friendly message describing the operation result.
- **`error`** _(object|null)_: Present only in error responses, contains `code` and `details`. Refer to [Error Codes](../error.md).

### Data and Pagination

- **`data`** _(array)_: Array of resource objects, always an array even if empty `[]`.
- **`pagination`** _(object|null)_: Pagination details for the resource list, `null` if pagination is not used.

#### Pagination Structure

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

### Search, Sort, and Filters

- **`search`** _(string|null)_: Search query string applied, `null` if no search.
- **`sort`** _(object|null)_: Sorting configuration applied, `null` if no sorting.

#### Sort Structure

```json
"sort": {
  "column": "<string>",
  "dir": "<\"asc\"|\"desc\">"
}
```

- **`filters`** _(object|null)_: Filter information with `applied` and `available` properties, `null` if no filters available.

#### Filters Structure

```json
"filters": {
  "applied": { "field": "<string>", "value": "<mixed>" } | null,
  "available": [
    { "field": "<string>", "label": "<string>", "values": [ /* array of available values */ ] }
  ] | null
}
```

### Schema and Columns

- **`schema`** _(array|null)_: Field definitions for dynamic forms, `null` if not available.

#### Schema Structure

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

- **`columns`** _(array)_: Column configuration for tables, always present (falls back to ID column).

#### Columns Structure

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

### Notifications

- **`notifications`** _(array|null)_: Array of notification objects for user feedback, present only in success responses, `null` if no notifications.

#### Notification Structure

```json
"notifications": [
  {
    "type": "<string>", // info, warning, success, etc.
    "message": "<string>"
  }
] | null
```

#### Notification Properties

- **`type`** _(string)_: Notification type (info, warning, success, etc.).
- **`message`** _(string)_: Human-readable notification message.

#### Notification Types and Behavior

- **`info`**: Informational messages (e.g., parameter defaults applied).
- **`warning`**: Non-critical issues (e.g., invalid parameters ignored).
- **`success`**: Success confirmations.

Notifications are used for parameter fallbacks instead of returning errors. Multiple notifications can be present in a single response.

#### Notification Examples for Parameter Issues

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

---

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

---

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

---

## HTTP Status Codes for GET/index

- refer the design\api\error.md
