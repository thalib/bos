


# REST API Response Template

All REST API endpoints must return JSON responses in the following structure:

```json
{
  "success": true,
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
  },
  "search": <string|null>,
  "sort": <string|null>,
  "filters": {
    "applied": <object|null>,
    "availableOptions": <array|null>
  },
  "schema": <array|null>,
  "columns": { /* object of column configs, optional */ }
}
```

## Field Descriptions

- `success` (boolean): Indicates if the request was successful.
- `message` (string): Human-readable status message.
- `data` (array): List of resource objects (e.g., products).
- `pagination` (object): Pagination details for the resource list.
- `search` (string|null): The search query, if any.
- `sort` (string|null): The sort parameter, if any.
- `filters` (object): Filter information, including applied and available options.
- `schema` (array): Field definitions for dynamic forms (optional, if requested).
- `columns` (object): Column configuration for resource tables (optional).



## success

Indicates if the request was successful.

Example:
```json
"success": true
```

- `true` for successful requests (HTTP 200, 201, etc.)
- `false` for error responses (HTTP 4xx, 5xx)

---


## message

Human-readable status message describing the result of the request.

Example:
```json
"message": "Products fetched successfully."
```

- Present on all responses (success and error)
- May be `null` in rare cases if not set by the controller

---


## pagination

Pagination details for the resource list. Present only for endpoints that return a list of resources (e.g., index endpoints).

Example:
```json
"pagination": {
  "totalItems": 121,
  "currentPage": 1,
  "itemsPerPage": 15,
  "totalPages": 9,
  "urlPath": "http://localhost:8000/api/v1/products",
  "urlQuery": null,
  "nextPage": "http://localhost:8000/api/v1/products?page=2",
  "prevPage": null
}
```

- Will be `null` or omitted for single resource endpoints (e.g., show, create, update, delete)
- `nextPage` and `prevPage` are `null` if there is no next/previous page

---


## search

The search query string used for filtering results.

Example:
```json
"search": null
```
or
```json
"search": "laptop"
```

- Will be a string if a search was performed (e.g., `?search=foo`)
- Will be `null` if no search was applied

---


## sort

The `sort` field describes the sorting applied to the resource list in the API response. It provides both the column being sorted and the direction of sorting.

**Format:**

```json
"sort": {
  "column": "<string>",
  "dir": "<\"asc\"|\"desc\">"
}
```

- `sort` is `null` if no sorting was applied.
- If sorting is applied, `sort` is an object with:
  - `column`: the name of the column being sorted (string)
  - `dir`: the direction of sorting, either `"asc"` or `"desc"`


**Request Examples:**

Sort by name ascending:

```json
{"sort": {"column": "name", "dir": "asc"}}
```

Sort by created_at descending:

```json
{"sort": {"column": "created_at", "dir": "desc"}}
```

Sort by id (defaults to asc when dir is omitted):

```json
{"sort": {"column": "id"}}
```

No sorting (don't include sort parameter or set it to null):

```json
{}
```


**Error Responses:**

Invalid sort format:

```json
{
  "success": false,
  "error": {
    "code": "INVALID_SORT_FORMAT",
    "message": "Sort parameter must be an object with \"column\" and optional \"dir\" properties",
    "details": {
      "expected_format": {
        "column": "string",
        "dir": "asc|desc"
      }
    }
  }
}
```

Invalid sort field:

```json
{
  "success": false,
  "error": {
    "code": "INVALID_SORT_FIELD",
    "message": "Sort field 'invalid_field' is not allowed",
    "details": {
      "allowed_fields": ["id", "name", "created_at", "updated_at", "..."]
    }
  }
}
```

Invalid sort direction:

```json
{
  "success": false,
  "error": {
    "code": "INVALID_SORT_DIRECTION",
    "message": "Sort direction 'invalid' is not allowed",
    "details": {
      "allowed_directions": ["asc", "desc"]
    }
  }
}
```

### Response Examples

**No sort applied:**

```json
"sort": null
```

**Sort applied (by name ascending):**

```json
"sort": {
  "column": "name",
  "dir": "asc"
}
```


## filters

Information about filters applied and available for the resource.

Example (no filters applied, none available):
```json
"filters": {
  "applied": null,
  "availableOptions": null
}
```
Example (filters applied and available):
```json
"filters": {
  "applied": { "field": "brand", "value": "Brand C" },
  "availableOptions": [
    { "field": "brand", "value": ["Brand C", "ASENSAR"] },
    { "field": "active", "value": [true, false] }
  ]
}
```

- `applied` is an object if filters are active, otherwise `null`
- `availableOptions` is an array of filter options if the model defines `getApiFilters()` method, otherwise `null`
- If the model does not define `getApiFilters()` or returns null/empty, then both `applied` and `availableOptions` will be `null`

---


## schema

Array of field definitions for dynamic forms (optional, only present if the model defines `getApiSchema()` method).

Example:
```json
"schema": [
  { "field": "name", "type": "string", "required": true },
  { "field": "price", "type": "decimal", "required": true }
]
```
or
```json
"schema": null
```

- Will be an array if the model defines `getApiSchema()` method and returns a valid schema
- Will be `null` if the model does not define `getApiSchema()` method or returns null/empty
- No auto-generation is performed if the model doesn't define the schema

---


## columns

Object describing column configuration for resource tables (optional, only present if the model defines `getIndexColumns()` method).

Example:
```json
"columns": {
  "name": { "search": true },
  "cost": { "label": "Cost", "formatter": "currency" },
  "price": { "label": "Price", "formatter": "currency" },
  "mrp": { "label": "MRP", "formatter": "currency" },
  "stock_quantity": { "label": "Stock", "formatter": "number" }
}
```
or
```json
"columns": {
  "id": { "label": "ID", "sortable": true, "clickable": true, "search": true }
}
```

- Will be an object if the model defines `getIndexColumns()` method and returns valid columns configuration
- Will default to ID column configuration if the model does not define `getIndexColumns()` or returns null/empty:

  ```json
  {
    "id": { "label": "ID", "sortable": true, "clickable": true, "search": true }
  }
  ```

---

# Example: GET /api/v1/products

```json
{
  "success": true,
  "message": "Products fetched successfully.",
  "data": [
    {
      "id": 1,
      "name": "Et Quas",
      "slug": "et-quas-247",
      "type": "simple",
      "publication_status": "private",
      "active": true,
      "description": "Quas id et delectus sunt. Cum deserunt labore quo nobis voluptate inventore omnis voluptatem. Voluptatem ad quo aut iste dolor sit dignissimos consequatur.\n\nVoluptas autem fuga qui quibusdam veritatis sint qui ullam. Dolores delectus ea molestiae quia. Ad est quis eum veritatis voluptatem quia dolor. Ea enim quam et ut incidunt illum.",
      "short_description": null,
      "sku": "SKU-004po93",
      "barcode": "2174547979025",
      "brand": "Brand C",
      "cost": "435.85",
      "mrp": "880.42",
      "price": "809.98",
      "sale_price": "680.39",
      "taxable": true,
      "tax_hsn_code": "7324.07",
      "tax_rate": "12.00",
      "tax_inclusive": false,
      "stock_track": true,
      "stock_quantity": 565,
      "stock_low_threshold": 48,
      "length": "92.34",
      "width": "17.42",
      "height": "44.08",
      "weight": "7.55",
      "unit": "kg",
      "shipping_weight": "25.95",
      "shipping_required": true,
      "shipping_taxable": true,
      "shipping_class_id": 1,
      "image": "https://via.placeholder.com/640x480.png/00dd22?text=products+minima",
      "images": [
        "https://via.placeholder.com/640x480.png/00eebb?text=products+animi"
      ],
      "external_url": "http://donnelly.org/architecto-et-blanditiis-occaecati-voluptatum-molestiae",
      "categories": [5, 4],
      "tags": null,
      "attributes": null,
      "variations": null,
      "meta_data": [{ "key": "featured", "value": true }],
      "related_ids": null,
      "upsell_ids": null,
      "cross_sell_ids": null,
      "created_at": "2025-07-09T07:58:05.000000Z",
      "updated_at": "2025-07-09T07:58:05.000000Z"
    }
    // ... more product objects
  ],
  "pagination": {
    "totalItems": 121,
    "currentPage": 1,
    "itemsPerPage": 15,
    "totalPages": 9,
    "urlPath": "http://localhost:8000/api/v1/products",
    "urlQuery": null,
    "nextPage": "http://localhost:8000/api/v1/products?page=2",
    "prevPage": null
  },
  "search": null,
  "sort": null,
  "filters": {
    "applied": null,
    "availableOptions": null
  },
  "schema": [
    // ... schema field definitions (if requested)
  ],
  "columns": {
    "name": { "search": true },
    "cost": { "label": "Cost", "formatter": "currency" },
    "price": { "label": "Price", "formatter": "currency" },
    "mrp": { "label": "MRP", "formatter": "currency" },
    "stock_quantity": { "label": "Stock", "formatter": "number" }
  }
}
```


## Error Response Example

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Authentication required.",
    "details": null,
    "meta": {
      "filters": null,
      "pagination": null
    }
  }
}
```

## Error Response Example

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Authentication required.",
    "details": null,
    "meta": {
      "filters": null,
      "pagination": null
    }
  }
}
```

