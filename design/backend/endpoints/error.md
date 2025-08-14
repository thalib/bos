# API Error Response Format

Comprehensive specification of the standardized error response contract used by all BOS API endpoints.

## Summary

* **Endpoint:** Global (applies to all `/api/v1/*` endpoints)
* **Method:** All (`GET|POST|PUT|PATCH|DELETE`)
* **Authentication:** Context-dependent (errors can occur pre/post auth)
* **Response Format:** JSON
* **Controller / Source:** [`ApiResourceController.php`](../../../../backend/app/Http/Controllers/ApiResourceController.php) & any custom controllers
* **Route Definition:** Dynamic via [`ApiResourceServiceProvider.php`](../../../../backend/app/Providers/ApiResourceServiceProvider.php)
* **Permissions:** Error shape consistent across public & protected endpoints
* **Caching:** Not cached (error responses should not be cached)
* **Error Handling:** Standardized via [`ApiResponseTrait.php`](../../../../backend/app/Http/Responses/ApiResponseTrait.php)

## Overview

All API errors follow a single, predictable JSON structure to simplify frontend handling and reduce branching logic. Errors never expose internal stack traces or sensitive implementation details. Validation, authorization, and system errors are normalized through the shared response helpers.

## Endpoint

Global specification – this is not a callable endpoint. Applies to every API route under `/api/v1/*` (auto-generated or custom).

## Authentication

- Some errors (401, 403) depend on auth state.
- Format is identical whether the request is authenticated or not.

## Request
### Method & URL
Not applicable – format applies to all requests.
### Headers / Query / Body
Not applicable – error shape independent of request type.

## Response
### Success Response
Not applicable here (see individual endpoint docs for success formats).

### Error Responses
Canonical structure:
```json
{
  "success": false,
  "message": "Human-readable error message",
  "error": {
    "code": "ERROR_CODE",
    "details": ["array", "of", "contextual", "details"]
  }
}
```
Validation errors MAY include a `validation_errors` object inside `error` (keyed by field names) when applicable.

## Data Model
### Properties
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `success` | boolean | Yes | Always `false` for error responses |
| `message` | string | Yes | Human-friendly summary of the failure |
| `error.code` | string | Yes | Machine-readable classification (UPPER_SNAKE_CASE) |
| `error.details` | array | No | Zero or more strings giving context (empty array omitted) |
| `error.validation_errors` | object | Conditional | Field-level validation messages (422 only) |

## Menu Structure
Not applicable.

## Frontend Integration
Recommended client handling pattern:
```javascript
function handleError(resp) {
  if (resp.success) return resp; // already a success
  const { code, details, validation_errors } = resp.error || {};
  switch (code) {
    case 'UNAUTHORIZED': /* redirect/login */ break;
    case 'FORBIDDEN': /* show access denied */ break;
    case 'NOT_FOUND': /* show 404 message */ break;
    case 'UNPROCESSABLE_ENTITY': /* surface validation_errors */ break;
    default: /* generic fallback */ break;
  }
}
```
Use the top-level `message` for user display; reserve `details` for diagnostics or developer console.

## Caching
- Do NOT cache error responses.
- Client-side retries should implement backoff for `429` & `503`.

## Role-Based Access / Permissions
- Authorization failures (403) do not reveal whether the resource exists.
- Consistent structure prevents information leakage.

## Related Endpoints
- See individual CRUD docs (`index.md`, `show.md`, `store.md`, `update.md`, `destroy.md`)
- Validation rules: resource docs in `design/backend/resources/`

## Notes / Additional Information
- Generated via `errorResponse()` / `paginatedResponse()` / `successResponse()` in `ApiResponseTrait`.
- Query parameter issues (pagination, sorting, filtering, search) DO NOT trigger errors; instead they produce success responses with `notifications` (see `index.md`).
- Internal exceptions are logged server-side with stack traces but never exposed.
- Error codes are stable contract elements – new codes require documentation updates.

## HTTP Status Codes & Error Codes

| HTTP | Error Code | Description | Typical Cause |
|------|------------|-------------|---------------|
| 200 | OK | Successful operation (non-error reference) | Normal success |
| 400 | BAD_REQUEST | Malformed request | Invalid JSON, invalid ID format |
| 401 | UNAUTHORIZED | Authentication required/failed | Missing or invalid bearer token |
| 403 | FORBIDDEN | Authenticated but lacks permission | Role / policy denial |
| 404 | NOT_FOUND | Resource or route not found | Missing ID or route |
| 405 | METHOD_NOT_ALLOWED | Unsupported HTTP method | Wrong method used |
| 409 | CONFLICT | State conflict | Unique constraint violation |
| 422 | UNPROCESSABLE_ENTITY | Validation failure | Input validation errors |
| 429 | TOO_MANY_REQUESTS | Rate limit exceeded | Throttling middleware |
| 500 | INTERNAL_SERVER_ERROR | Unexpected server error | Uncaught exception |
| 503 | SERVICE_UNAVAILABLE | Temporary outage | Maintenance / dependency failure |

## Common Error Examples

### Authentication – Missing Token (401)
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "error": { "code": "UNAUTHORIZED", "details": ["Bearer token is required"] }
}
```
### Authentication – Invalid Token (401)
```json
{
  "success": false,
  "message": "Invalid authentication token.",
  "error": { "code": "UNAUTHORIZED", "details": ["Token has expired or is invalid"] }
}
```
### Authorization – Insufficient Permissions (403)
```json
{
  "success": false,
  "message": "Access denied.",
  "error": { "code": "FORBIDDEN", "details": ["User does not have permission to access this resource"] }
}
```
### Validation – Field Errors (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "error": {
    "code": "UNPROCESSABLE_ENTITY",
    "details": [],
    "validation_errors": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  }
}
```
### Bad Request (400)
```json
{
  "success": false,
  "message": "Invalid request format",
  "error": { "code": "BAD_REQUEST", "details": ["Request body must be valid JSON"] }
}
```
### Resource Not Found (404)
```json
{
  "success": false,
  "message": "Resource not found",
  "error": { "code": "NOT_FOUND", "details": ["User with ID 999 does not exist"] }
}
```
### Conflict (409)
```json
{
  "success": false,
  "message": "Resource conflict",
  "error": { "code": "CONFLICT", "details": ["Email address is already registered"] }
}
```
### Rate Limiting (429)
```json
{
  "success": false,
  "message": "Too many requests",
  "error": { "code": "TOO_MANY_REQUESTS", "details": ["Rate limit exceeded. Try again in 60 seconds."] }
}
```
### Internal Server Error (500)
```json
{
  "success": false,
  "message": "Internal server error",
  "error": { "code": "INTERNAL_SERVER_ERROR", "details": ["An unexpected error occurred. Please try again later."] }
}
```
### Service Unavailable (503)
```json
{
  "success": false,
  "message": "Service temporarily unavailable",
  "error": { "code": "SERVICE_UNAVAILABLE", "details": ["Server is undergoing maintenance. Please try again later."] }
}
```

## Error Handling Best Practices

### For API Clients
1. Check `success` before consuming data.
2. Switch on `error.code` for deterministic handling.
3. Display `message` to users; avoid exposing raw `details` unless safe.
4. For `UNPROCESSABLE_ENTITY`, map `validation_errors` to form fields.
5. Retry with exponential backoff for `TOO_MANY_REQUESTS` / `SERVICE_UNAVAILABLE`.

### Query Parameter Deviation Handling
Invalid pagination, sorting, filtering, or search parameters DO NOT raise errors. Instead:
- Parameters are sanitized / defaulted silently.
- User-facing warnings are emitted via `notifications` in a successful response.

### Logging & Security
- All server-side exceptions logged with trace (internal only).
- Sanitized database errors (via parser services) prevent leakage.
- No stack traces, SQL, or file paths in client responses.

### Stability & Versioning
- Adding new `error.code` values requires updating this document and notifying client teams.
- Existing codes are backward-compatible; deprecations must include migration guidance.
