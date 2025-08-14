# PUT/PATCH Resource Update Endpoint (Update)

Modify an existing resource instance (full or partial update).

## Summary

* **Endpoint:** `PUT /api/v1/{resource}/{id}` & `PATCH /api/v1/{resource}/{id}`
* **Method:** `PUT | PATCH`
* **Authentication:** Required (`auth:sanctum`, Bearer token)
* **Response Format:** JSON
* **Controller:** [`ApiResourceController.php`](../../../../backend/app/Http/Controllers/ApiResourceController.php)
* **Route Definition:** [`ApiResourceServiceProvider.php`](../../../../backend/app/Providers/ApiResourceServiceProvider.php)
* **Permissions:** All authenticated users (future: role-based / ownership)
* **Caching:** Not recommended (state-changing operation)
* **Error Handling:** Standard error response via `ApiResponseTrait`

## Overview

Updates a resource identified by `{id}`. `PUT` expects a complete replacement of updatable fields; `PATCH` allows partial modification. Validation logic is centralized in `UpdateResourceRequest`. Controller `update()` must remain thin and delegate any transformation or domain logic outside.

## Endpoint

`PUT /api/v1/{resource}/{id}`  
`PATCH /api/v1/{resource}/{id}`

## Authentication

- Required: Yes
- Middleware: `auth:sanctum`
- Scheme: Bearer token

## Request
### Method & URL
```
PUT /api/v1/{resource}/{id}
PATCH /api/v1/{resource}/{id}
```
### Headers
```
Authorization: Bearer {access_token}
Content-Type: application/json
```
### Path Parameters
- `id` (string | integer): Unique identifier of the resource to update.
### Query Parameters
None
### Request Body
JSON object containing fields to update. For `PUT`, supply all required editable fields. For `PATCH`, include only the fields to change.
### Example Request (PATCH)
```bash
curl -X PATCH "https://api.example.com/api/v1/products/42" \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Content-Type: application/json" \
  -d '{"price":129.99,"status":"active"}'
```

## Response
### Success Response
HTTP 200
```json
{
  "success": true,
  "message": "Resource updated successfully",
  "data": {
    "id": 42,
    "name": "Updated Product",
    "price": 129.99,
    "status": "active",
    "created_at": "2025-01-15T10:30:00Z",
    "updated_at": "2025-01-15T11:05:00Z"
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
#### Validation Failed (422)
```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "UNPROCESSABLE_ENTITY",
    "details": [],
    "validation_errors": {
      "price": ["The price must be numeric."],
      "status": ["The selected status is invalid."]
    }
  }
}
```
#### Conflict (409)
```json
{ "success": false, "message": "Resource conflict", "error": { "code": "CONFLICT", "details": ["Duplicate value"] } }
```
#### Internal Error (500)
```json
{ "success": false, "message": "An error occurred while updating the resource", "error": { "code": "INTERNAL_SERVER_ERROR", "details": [] } }
```
Refer to [error.md](error.md) for full error catalog.

## Data Model
### Properties
See resource-specific documentation for permissible fields and constraints.

## Menu Structure
N/A

## Frontend Integration
- Use optimistic UI only if rollback strategy exists.
- Surface `validation_errors` inline for forms.
- Refresh or patch in-memory entity after success.

## Caching
- Invalidate or refresh caches containing the updated record.
- Do not cache update responses.

## Role-Based Access / Permissions
- Future: enforce ownership / role checks (e.g., only creator or admin can modify).

## Related Endpoints
- [List Resources](index.md)
- [Show Resource](show.md)
- [Create Resource](store.md)
- [Delete Resource](destroy.md)
- [Error Codes](error.md)

## Notes / Additional Information
- Implements `update()` in `ApiResourceController` (supports both PUT & PATCH).
- Uses `UpdateResourceRequest` for validation.
- Database errors parsed (e.g., constraint violations) before response.
- Business logic must not live in controller; extract to services/model events.
