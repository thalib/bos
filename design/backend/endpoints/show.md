# GET Single Resource Endpoint (Show)

Retrieve a single resource instance by its unique identifier.

## Summary

* **Endpoint:** `GET /api/v1/{resource}/{id}`
* **Method:** `GET`
* **Authentication:** Required (`auth:sanctum`, Bearer token)
* **Response Format:** JSON
* **Controller:** [`ApiResourceController.php`](../../../../backend/app/Http/Controllers/ApiResourceController.php)
* **Route Definition:** [`ApiResourceServiceProvider.php`](../../../../backend/app/Providers/ApiResourceServiceProvider.php)
* **Permissions:** All authenticated users (future: role / policy enforcement)
* **Caching:** Recommended (short-lived) for frequently accessed read-only entities
* **Error Handling:** Standard error response via `ApiResponseTrait`

## Overview

Returns a single resource object resolved by `{id}`. Uses the controller's `show()` method which must remain free of business logic and rely solely on model resolution and response formatting.

## Endpoint

`GET /api/v1/{resource}/{id}`

## Authentication

- Required: Yes
- Middleware: `auth:sanctum`
- Scheme: Bearer token

## Request
### Method & URL
```
GET /api/v1/{resource}/{id}
```
### Headers
```
Authorization: Bearer {access_token}
```
### Path Parameters
- `id` (string | integer): Unique identifier of the resource to retrieve.
### Query Parameters
None
### Request Body
None
### Example Request
```bash
curl -X GET "https://api.example.com/api/v1/products/42" \
  -H "Authorization: Bearer 1|abc123def456..."
```

## Response
### Success Response
HTTP 200
```json
{
  "success": true,
  "message": "Resource retrieved successfully",
  "data": {
    "id": 42,
    "name": "Sample Product",
    "status": "active",
    "created_at": "2025-01-15T10:30:00Z",
    "updated_at": "2025-01-15T10:30:00Z"
  }
}
```
### Error Responses
#### Unauthorized (401)
```json
{ "success": false, "message": "Unauthenticated.", "error": { "code": "UNAUTHENTICATED", "details": [] } }
```
#### Not Found (404)
```json
{ "success": false, "message": "Resource not found", "error": { "code": "NOT_FOUND", "details": [] } }
```
#### Forbidden (403)
```json
{ "success": false, "message": "Access denied.", "error": { "code": "FORBIDDEN", "details": [] } }
```
#### Internal Error (500)
```json
{ "success": false, "message": "An error occurred while fetching the resource", "error": { "code": "INTERNAL_SERVER_ERROR", "details": [] } }
```
Refer to [error.md](error.md) for full error format.

## Data Model
### Properties
See resource definitions in `design/backend/resources/` for field-level descriptions.

## Menu Structure
N/A

## Frontend Integration
- Use for detail views / read panels.
- On 404: redirect to list with notification.
- Avoid redundant fetches—cache in state store when appropriate.

## Caching
- Client-side memoization recommended.
- Server-side HTTP caching optional (ensure auth context isolation).

## Role-Based Access / Permissions
- Future: implement policies to restrict sensitive resources.

## Related Endpoints
- [List Resources](index.md)
- [Create Resource](store.md)
- [Update Resource](update.md)
- [Delete Resource](destroy.md)
- [Error Codes](error.md)

## Notes / Additional Information
- Implemented via `show()` in `ApiResourceController`.
- Uses `simpleSuccessResponse()` from `ApiResponseTrait`.
- Must not include pagination / search metadata.
