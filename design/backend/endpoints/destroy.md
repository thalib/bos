# DELETE Resource Endpoint (Destroy)

Delete an existing resource instance.

## Summary

* **Endpoint:** `DELETE /api/v1/{resource}/{id}`
* **Method:** `DELETE`
* **Authentication:** Required (`auth:sanctum`, Bearer token)
* **Response Format:** JSON
* **Controller:** [`ApiResourceController.php`](../../../../backend/app/Http/Controllers/ApiResourceController.php)
* **Route Definition:** [`ApiResourceServiceProvider.php`](../../../../backend/app/Providers/ApiResourceServiceProvider.php)
* **Permissions:** All authenticated users (future: role-based)
* **Caching:** Not recommended (state-changing operation)
* **Error Handling:** Standard error response via `ApiResponseTrait`

## Overview

Removes a single resource identified by its `{id}`. The operation returns a standardized success payload on completion. If the resource is not found or the caller lacks permission, an error response is returned.

## Endpoint

`DELETE /api/v1/{resource}/{id}`

## Authentication

- Required: Yes  
- Middleware: `auth:sanctum`  
- Scheme: Bearer token in `Authorization` header

## Request
### Method & URL
```
DELETE /api/v1/{resource}/{id}
```
### Headers
```
Authorization: Bearer {access_token}
```
### Path Parameters
- `id` (string | integer): Unique identifier of the resource to delete.
### Query Parameters
None
### Request Body
None
### Example Request
```bash
curl -X DELETE "https://api.example.com/api/v1/products/123" \
  -H "Authorization: Bearer 1|abc123def456..."
```

## Response
### Success Response
HTTP 200 (or 204 if no body is returned – current implementation uses 200 with message)
```json
{
  "success": true,
  "message": "Resource deleted successfully"
}
```
### Error Responses
#### Unauthorized (HTTP 401)
```json
{ "success": false, "message": "Unauthenticated.", "error": { "code": "UNAUTHENTICATED", "details": [] } }
```
#### Not Found (HTTP 404)
```json
{ "success": false, "message": "Resource not found", "error": { "code": "NOT_FOUND", "details": [] } }
```
#### Forbidden (HTTP 403)
```json
{ "success": false, "message": "Access denied.", "error": { "code": "FORBIDDEN", "details": [] } }
```
#### Internal Error (HTTP 500)
```json
{ "success": false, "message": "An error occurred while deleting the resource", "error": { "code": "INTERNAL_SERVER_ERROR", "details": [] } }
```
Refer to [error.md](error.md) for the full error format and additional codes.

## Data Model
### Properties
Not applicable for delete operation. See resource definitions in `design/backend/resources/` for model fields.

## Menu Structure
N/A

## Frontend Integration
- Typical UI action: Delete button with confirmation dialog.
- On success: Remove item from local list without full reload.
- Use notifications to display success or mapped error messages.

## Caching
- Do not cache delete responses.
- Invalidate or refresh any cached lists or detail views referencing the deleted resource.

## Role-Based Access / Permissions
- Currently all authenticated users (future: enforce role / ownership checks in policy or middleware).
- Implement fine-grained authorization before exposing delete broadly.

## Related Endpoints
- [List Resources](index.md)
- [Show Resource](show.md)
- [Create Resource](store.md)
- [Update Resource](update.md)
- [Error Codes](error.md)

## Notes / Additional Information
- Registered dynamically via `ApiResourceServiceProvider` for each model with the `#[ApiResource]` attribute.
- Uses the `destroy()` method in `ApiResourceController` – must remain free of business logic per controller rules.
- Response formatting provided by `ApiResponseTrait` (`simpleSuccessResponse` / `errorResponse`).
- Consider soft deletes (Eloquent `SoftDeletes` trait) for recoverability; document per-resource behavior in resource files.
- Always perform id validation and existence check; non-existent id returns 404, not 200.
