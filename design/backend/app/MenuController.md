## Summary

- **Endpoint:** `GET /api/v1/app/menu`
- **Method:** `GET`
- **Authentication:** Required (`auth:sanctum`, Bearer token)
- **Response Format:** JSON (project standard envelope: `success`, `message`, `data`)
- **Controller:** `MenuController.php` (`backend/app/Http/Controllers/Api/MenuController.php`) — this endpoint is an explicit exception: the menu-building logic lives in the controller (not in a separate service) during active development
- **Route Definition:** `routes/api.php` (registered under `/api/v1/app/menu` with `auth:sanctum` middleware)
- **Permissions:** All authenticated users (future: role-based)
- **Caching:** Optional; if added, implement carefully in-controller during development or move to a service when menu stabilizes
- **Error Handling:** Follow project error envelope and logging conventions; do not expose stack traces

## Overview

This document is intentionally lightweight. During active development the menu is composed in the controller by developers and changes frequently; therefore this design does NOT try to define the menu items. Instead it documents the API contract, security expectations, and project-standard behaviors. When the menu stabilizes, move generation to `App\Services\MenuService` and re-introduce a canonical menu schema in documentation.

## Endpoint

### URL
GET /api/v1/app/menu

### Purpose
Return the current application menu for the authenticated user. The controller builds the menu at runtime. Frontends must treat the `data` array as an implementation-driven payload that may change frequently.

## Authentication

- Required: Yes
- Method: Bearer token via `Authorization` header
- Middleware: `auth:sanctum`

## Request

### Method & URL
```
GET /api/v1/app/menu
```

### Headers
```
Authorization: Bearer {access_token}
Accept: application/json
```

### Query Parameters
None

### Request Body
None

## Response (contract)

Provide a stable API envelope while allowing the `data` payload to be flexible:

- success (boolean) — whether the request succeeded
- message (string) — human readable message
- data (array) — menu payload; shape is implementation driven and may change during development

Example envelope (structure only):

```json
{
  "success": true,
  "message": "Menu items retrieved successfully",
  "data": [ /* implementation-driven items */ ]
}
```

Example data format (illustrative only):

```json
[
  { "type": "item", "id": 1, "name": "Home", "path": "/", "icon": "bi-house" },
  {
    "type": "section",
    "title": "List",
    "items": [
      {
        "id": 20,
        "name": "Products",
        "path": "/list/products",
        "icon": "bi-calculator"
      }
    ]
  },
  { "type": "divider" }
]
```

Notes on `data`:
- The controller currently returns an array of heterogeneous entries (items, sections, dividers). Do not hard-code these in docs — they live in code.
- Frontend should be defensive: handle unknown fields, missing optional keys, and new item types.
- IDs should be unique per-item where present. If duplicate IDs are observed, treat IDs as non-guaranteed until stabilized.

## Error responses

Follow the project standard envelope for errors. Examples (structure only):

- Unauthorized: `success: false`, `message: 'Unauthenticated.'`, proper HTTP 401 status
- Generic server error: `success: false`, `message: 'Unable to retrieve menu at this time.'`, HTTP 500

Use existing project error response trait and log failures with context (user id, route).

All errors must be logged server-side. Do not return stack traces or internal debug data.

## Data model guidance (developer-facing)

Because the menu definition is implemented in code and changes often, keep these rules minimal and developer-focused:

- Support at least these logical types: `item`, `section`, `divider`.
- `item` normally contains: `id` (integer), `name` (string), `path` (string), `icon` (string). `mode` is optional.
- `section` contains: `title` (string) and `items` (array of `item`).
- `divider` contains only `type: 'divider'`.

Treat this as guidance, not a fixed schema while development continues.

## Frontend integration

- Consume the `data` array and render based on `type`.
- Be tolerant: unknown `type` values should be ignored or rendered safely.
- Use `mode` or other provider-defined flags opportunistically; do not hard-fail if they are missing.

## Caching and stability

- Caching may be applied during development, but prefer short TTLs and clear caches when menu code changes.
- Cache invalidation policy: prefer short TTLs (for example 60–300 seconds) during development and ensure caches are cleared or rotated on deploys or when feature flags change.
- When menu generation is stable, move caching and generation into `App\Services\MenuService` and update docs with a canonical schema.

## Testing (brief)

- Test filename (Feature tests): `backend/tests/Feature/TestMenuControllerTest.php`.
- Test expectations (in words): unauthenticated requests return 401 and the standard error envelope; authenticated requests return HTTP 200 with the standard success envelope and a `data` array present. Keep tests focused on contract (status and envelope), not on exact menu item contents since those change frequently.

## Notes / Additional Information

- This design intentionally avoids enumerating menu contents because the menu is defined in code and changes often while features are developed.
- The controller currently returns `data` and `message`; prefer aligning to the full envelope (`success`, `message`, `data`) in the controller implementation when convenient.
- Once development stabilizes, extract menu logic to `App\Services\MenuService`, add server-side caching, and update this document with a canonical schema and examples.
