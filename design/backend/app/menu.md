## Summary

* **Endpoint:** `GET /api/v1/app/menu`
* **Method:** `GET`
* **Authentication:** Required (`auth:sanctum`, Bearer token)
* **Response Format:** JSON (standard project envelope)
* **Controller:** `MenuController.php` (`backend/app/Http/Controllers/Api/MenuController.php`)
* **Route Definition:** `routes/api.php` (registered under `/api/v1/app/menu` with `auth:sanctum`)
* **Permissions:** All authenticated users (future: role-based)
* **Caching:** Recommended (server-side cache with configurable TTL)
* **Error Handling:** Standard project JSON error envelope (`success`, `message`, `error`)

## Overview

Returns the application menu structure for the authenticated user. The menu is organized into logical sections and supports role-based enhancements in future versions. Implementation should follow project conventions: thin controller, business logic in a service (e.g., `App\Services\MenuService`).

## Endpoint

### URL
GET /api/v1/app/menu

### Purpose
Retrieve the menu structure for the current authenticated user. The response uses the project's standard API envelope and should not expose internal errors.

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
### Example Request
```bash
curl -X GET "https://api.example.com/api/v1/app/menu" \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Accept: application/json"
```

## Response

All responses follow the project envelope: `success` (bool), `message` (string), `data` (payload) and on error `error` (object).

### Success Response (HTTP 200)
```json
{
  "success": true,
  "message": "Menu items retrieved successfully",
  "data": [
    { "type": "item", "id": 1, "name": "Home", "path": "/", "icon": "bi-house" },
    { "type": "section", "title": "List", "items": [ { "id": 20, "name": "Products", "path": "/list/products", "icon": "bi-calculator" } ] },
    { "type": "divider" },
    { "type": "section", "title": "Sales", "items": [ { "id": 40, "name": "Estimate", "path": "/list/estimates", "icon": "bi-receipt" } ] },
    { "type": "divider" },
    { "type": "section", "title": "Administration", "items": [ { "id": 60, "name": "Users", "path": "/list/users", "icon": "bi-people", "mode": "form" }, { "id": 40, "name": "Estimate", "path": "/list/estimates", "icon": "bi-receipt", "mode": "doc" } ] },
    { "type": "divider" },
    { "type": "item", "id": 90, "name": "Help", "path": "/help", "icon": "bi-question-circle" }
  ]
}
```

Optionally a `schema` object may be included in responses or documentation to describe the shape of `data` (see Data Model).

### Error Responses

#### Unauthorized (HTTP 401)
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "error": { "code": "auth.unauthenticated", "details": null }
}
```

#### Generic Error (HTTP 500)
```json
{
  "success": false,
  "message": "Unable to retrieve menu at this time.",
  "error": { "code": "menu.fetch_failed", "details": "Logged on server" }
}
```

Errors must be logged server-side. Do not return stack traces to the client.

## Data Model

The menu `data` is an array of heterogeneous items. Each item has a `type` that determines its other fields. The main kinds are `item`, `section`, and `divider`.

### Item
| Field | Type | Required | Description |
|---|---:|:---:|---|
| type | string | Yes | Always `item` |
| id | integer | Yes | Unique menu item identifier |
| name | string | Yes | Display name |
| path | string | Yes | Navigation path/URL |
| icon | string | Yes | Bootstrap icon class (e.g., `bi-house`) |
| mode | string | No | Optional mode (e.g., `form`, `doc`) |

### Section
| Field | Type | Required | Description |
|---|---:|:---:|---|
| type | string | Yes | Always `section` |
| title | string | Yes | Section title |
| items | array | Yes | Array of `item` objects within the section |

### Divider
| Field | Type | Required | Description |
|---|---:|:---:|---|
| type | string | Yes | Always `divider` |

### JSON Schema (example)
```json
{
  "type": "array",
  "items": {
    "oneOf": [
      {
        "type": "object",
        "required": ["type","id","name","path","icon"],
        "properties": {
          "type": { "const": "item" },
          "id": { "type": "integer" },
          "name": { "type": "string" },
          "path": { "type": "string" },
          "icon": { "type": "string" },
          "mode": { "type": "string" }
        }
      },
      {
        "type": "object",
        "required": ["type","title","items"],
        "properties": {
          "type": { "const": "section" },
          "title": { "type": "string" },
          "items": { "type": "array" }
        }
      },
      {
        "type": "object",
        "required": ["type"],
        "properties": { "type": { "const": "divider" } }
      }
    ]
  }
}
```

## Menu Structure

- **Home**: Main dashboard
- **List Section**: Data management (Products)
- **Sales Section**: Sales-related (Estimates)
- **Administration Section**: Admin functions (Users, Estimate management)
- **Help**: Support and documentation

## Frontend Integration

Frontend should consume the `data` array and render items according to `type`. Use `mode` to decide whether to open a form, document viewer, or route. Frontend is responsible for client-side caching and rendering; server should supply the canonical menu.

## Caching

Server-side caching is recommended to avoid rebuilding the static menu on every request. Implement caching in the service layer (not the controller). Example pattern:

```php
// inside MenuService
use Illuminate\Support\Facades\Cache;

public function getMenuForUser(User $user): array
{
    $ttl = config('app.menu_ttl', 3600); // seconds
    $key = "menu:v1:user:{$user->id}";

    return Cache::remember($key, $ttl, function () use ($user) {
        // build menu items (role filtering, dynamic entries, etc.)
        return $this->buildMenu($user);
    });
}
```

Use role-based cache keys if menus differ by role: `menu:v1:role:{role}`.

## Role-Based Access / Permissions

Currently the menu is static for all authenticated users. Future implementations should filter items by user permissions/roles. The `MenuService` should accept the user and apply permission checks before returning items.

## Testing (TDD)

Per project policy, add failing feature tests first. Suggested tests:

- `backend/tests/Feature/AppMenuTest.php` (feature test)
  - unauthenticated request returns 401 with standard error envelope
  - authenticated request returns 200 with `success: true` and `data` array

Example PHPUnit test skeleton:
```php
<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class AppMenuTest extends TestCase
{
    public function test_unauthenticated_returns_401()
    {
        $this->getJson('/api/v1/app/menu')
             ->assertStatus(401)
             ->assertJson([ 'success' => false, 'message' => 'Unauthenticated.' ]);
    }

    public function test_authenticated_returns_menu()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/v1/app/menu')
             ->assertStatus(200)
             ->assertJsonStructure(['success','message','data']);
    }
}
```

Follow TDD: write the test first (it should fail), implement the `MenuService` and controller, then run `php artisan test` and ensure tests pass.

## Related Endpoints

- `GET /api/v1/auth/status` — check user authentication and roles
- `GET /api/v1/users/{id}` — user profile (for role/permission checks)

## Notes / Additional Information

- Uses Bootstrap Icons (`bi-*`) for menu icons
- All endpoints protected by `auth:sanctum` middleware
- Implementation hint: keep controller thin and delegate to `MenuService`; cache menu results; log and handle errors via project standard error handling.
