# POST Resource Creation Endpoint (Store)

Create a new resource instance.

## Summary

* **Endpoint:** `POST /api/v1/{resource}`
* **Method:** `POST`
* **Authentication:** Required (`auth:sanctum`, Bearer token)
* **Response Format:** JSON
* **Controller:** [`ApiResourceController.php`](../../../../backend/app/Http/Controllers/ApiResourceController.php)
* **Route Definition:** [`ApiResourceServiceProvider.php`](../../../../backend/app/Providers/ApiResourceServiceProvider.php)
* **Permissions:** All authenticated users (future: role-based / ownership rules)
* **Caching:** Not recommended (state-changing operation)
* **Error Handling:** Standard error response via `ApiResponseTrait`

## Overview

Creates and persists a new resource record using validated input. Validation logic resides in `StoreResourceRequest`. Controller method `store()` must remain thin and delegate any complex defaults or transformations to model or service layers.

## Endpoint

`POST /api/v1/{resource}`

## Authentication

- Required: Yes
- Middleware: `auth:sanctum`
- Scheme: Bearer token

## Request
### Method & URL
```
POST /api/v1/{resource}
```
### Headers
```
Authorization: Bearer {access_token}
Content-Type: application/json
```
### Query Parameters
None
### Request Body
JSON object containing the fields required by the resource. Required & optional fields vary per model (see resource docs in `design/backend/resources/`).
### Example Request
```bash
curl -X POST "https://api.example.com/api/v1/products" \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Content-Type: application/json" \
  -d '{"name":"New Product","price":99.99,"status":"active"}'
```

## Response
### Success Response
HTTP 201
```json
{
  "success": true,
  "message": "Resource created successfully",
  "data": {
    "id": 321,
    "name": "New Product",
    "price": 99.99,
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
#### Validation Failed (422)
```json
{
  "success": false,
  "message": "The given data was invalid",
  "error": {
    "code": "UNPROCESSABLE_ENTITY",
    "details": [],
    "validation_errors": {
      "name": ["The name field is required."],
      "price": ["The price must be a number."]
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
{ "success": false, "message": "An error occurred while creating the resource", "error": { "code": "INTERNAL_SERVER_ERROR", "details": [] } }
```
Refer to [error.md](error.md) for full format list.

## Data Model
### Properties
See resource-specific documentation for required fields and constraints.

## Menu Structure
N/A

## Frontend Integration
- Use form submission with client-side validation first.
- On success: append new record to in-memory list optimistically.
- Handle 422 by mapping `validation_errors` to form inputs.

## Caching
- Invalidate any cached list views after creation.
- Avoid caching POST responses.

## Role-Based Access / Permissions
- Future: enforce create restrictions (e.g., admin-only resources).

## Related Endpoints
- [List Resources](index.md)
- [Show Resource](show.md)
- [Update Resource](update.md)
- [Delete Resource](destroy.md)
- [Error Codes](error.md)

## Notes / Additional Information
- Implements `store()` in `ApiResourceController`.
- Uses `StoreResourceRequest` for validation (see backend requests directory).
- Database-specific errors parsed via `DatabaseErrorParser` before response.
- Business logic must live outside controller (e.g., services / model events).
