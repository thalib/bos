# GET Resource List Endpoint (Index)

List resources with pagination, search, filtering, and sorting capabilities.

## Summary

* **Endpoint:** `GET /api/v1/{resource}`
* **Method:** `GET`
* **Authentication:** Required (`auth:sanctum`, Bearer token)
* **Response Format:** JSON
* **Controller:** [`ApiResourceController.php`](../../../../backend/app/Http/Controllers/ApiResourceController.php)
* **Route Definition:** [`ApiResourceServiceProvider.php`](../../../../backend/app/Providers/ApiResourceServiceProvider.php)
* **Permissions:** All authenticated users (future: role-based)
* **Caching:** Recommended (resource lists are paginated and can be cached)
* **Error Handling:** Standard error response via `ApiResponseTrait`

## Overview

Returns a paginated, searchable, filterable, and sortable list of resources. Supports notifications for parameter fallbacks and validation issues. Applies to all auto-generated resources in the BOS system.

## Endpoint

`GET /api/v1/{resource}`

## Authentication

- Required: Yes
- Method: Bearer token via `Authorization` header
- Middleware: `auth:sanctum`

## Request
### Method & URL
```
GET /api/v1/{resource}
```
### Headers
```
Authorization: Bearer {access_token}
```
### Query Parameters
- `page` (integer): Page number to retrieve (default: 1)
- `per_page` (integer): Number of items per page (default: 15, max: 100)
- `sort` (string): Column name to sort by
- `dir` (string): Sort direction, either `asc` or `desc` (default: `asc`)
- `filter` (string): Filter format: `field:value`
- `search` (string): Search query string to filter results
### Request Body
None
### Example Request
```bash
curl -X GET "https://api.example.com/api/v1/products?page=2&per_page=20&sort=name&dir=asc" \
  -H "Authorization: Bearer 1|abc123def456..."
```

## Response
### Success Response
HTTP 200
```json
{
  "success": true,
  "message": "Resources retrieved successfully",
  "data": [ /* array of resource objects */ ],
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
    "available": [ { "field": "status", "label": "Status", "values": ["active", "inactive"] } ]
  },
  "schema": [ /* array of field definitions */ ],
  "columns": [ /* array of column configs */ ],
  "notifications": [
    { "type": "warning", "message": "Invalid page number '0', using page 1" }
  ]
}
```
### Error Responses
#### Unauthorized (HTTP 401)
```json
{ "success": false, "message": "Unauthenticated.", "error": { "code": "UNAUTHENTICATED", "details": [] } }
```
#### Validation or Internal Error (HTTP 422/500)
```json
{ "success": false, "message": "The given data was invalid", "error": { "code": "INTERNAL_SERVER_ERROR", "details": [] } }
```

## Data Model
### Properties
- See resource-specific documentation in `design/backend/resources/`

## Menu Structure
N/A

## Frontend Integration
- Standard paginated table, supports search, filter, sort, and notifications

## Caching
- Client-side: Cache for user session
- Server-side: Consider caching resource lists
- TTL: Resource changes are infrequent, longer cache times acceptable

## Role-Based Access / Permissions
- Currently static for all authenticated users
- Future: Role-based filtering, permission-based visibility, dynamic generation

## Related Endpoints
- [Show Resource](show.md)
- [Create Resource](store.md)
- [Update Resource](update.md)
- [Delete Resource](destroy.md)
- [Error Codes](error.md)

## Notes / Additional Information
- All endpoints protected by `auth:sanctum` middleware
- See `ApiResourceController.php`, `ApiResourceServiceProvider.php`, and `ApiResponseTrait.php` for implementation details
- Parameter validation uses notifications, not error responses, for fallbacks
- Response format is standardized for all resources
