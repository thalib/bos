# GET /api/v1/app/menu

## Overview

Returns the application menu structure for the authenticated user. The menu is organized into logical sections and supports role-based enhancements in future versions.

## Summary 

* **Endpoint:** `GET /api/v1/app/menu`
* **Method:** `GET`
* **Authentication:** Required (`auth:sanctum`, Bearer token)
* **Response Format:** JSON
* **Controller:** [`MenuController.php`](/backend/app/Http/Controllers/Api/MenuController.php)
* **Route Definition:** [`api.php`](/backend/routes/api.php)
* **Permissions:** All authenticated users (future: role-based)
* **Caching:** Recommended (menu is static for most users)
* **Error Handling:** Standard Laravel JSON error response

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
```
### Query Parameters
None
### Request Body
None
### Example Request
```bash
curl -X GET "https://api.example.com/api/v1/app/menu" \
  -H "Authorization: Bearer 1|abc123def456..."
```

## Response
### Success Response
HTTP 200
```json
{
  "data": [
    { "type": "item", "id": 1, "name": "Home", "path": "/", "icon": "bi-house" },
    { "type": "section", "title": "List", "items": [ { "id": 20, "name": "Products", "path": "/list/products", "icon": "bi-calculator" } ] },
    { "type": "divider" },
    { "type": "section", "title": "Sales", "items": [ { "id": 40, "name": "Estimate", "path": "/list/estimates", "icon": "bi-receipt" } ] },
    { "type": "divider" },
    { "type": "section", "title": "Administration", "items": [ { "id": 60, "name": "Users", "path": "/list/users", "icon": "bi-people", "mode": "form" }, { "id": 40, "name": "Estimate", "path": "/list/estimates", "icon": "bi-receipt", "mode": "doc" } ] },
    { "type": "divider" },
    { "type": "item", "id": 90, "name": "Help", "path": "/help", "icon": "bi-question-circle" }
  ],
  "message": "Menu items retrieved successfully"
}
```
### Error Responses
#### Unauthorized (HTTP 401)
```json
{ "message": "Unauthenticated." }
```
Occurs if no/invalid/expired token is provided.

## Data Model
### Properties
#### Item Type
| Field    | Type    | Required | Description                      |
|----------|---------|----------|----------------------------------|
| type     | string  | Yes      | Always "item"                    |
| id       | integer | Yes      | Unique menu item identifier      |
| name     | string  | Yes      | Display name                     |
| path     | string  | Yes      | Navigation path/URL              |
| icon     | string  | Yes      | Bootstrap icon class             |
| mode     | string  | No       | Optional mode (e.g., "form", "doc") |

#### Section Type
| Field    | Type    | Required | Description                      |
|----------|---------|----------|----------------------------------|
| type     | string  | Yes      | Always "section"                 |
| title    | string  | Yes      | Section title                    |
| items    | array   | Yes      | Array of menu items in section   |

#### Divider Type
| Field    | Type    | Required | Description                      |
|----------|---------|----------|----------------------------------|
| type     | string  | Yes      | Always "divider"                 |

## Menu Structure

- **Home**: Main dashboard
- **List Section**: Data management (Products)
- **Sales Section**: Sales-related (Estimates)
- **Administration Section**: Admin functions (Users, Estimate management)
- **Help**: Support and documentation

## Frontend Integration

N/A

## Caching

- Client-side: Cache for user session
- Server-side: Consider caching menu structure
- TTL: Menu changes are infrequent, longer cache times acceptable

## Role-Based Access / Permissions

- Currently static for all authenticated users
- Future: Role-based filtering, permission-based visibility, dynamic generation

## Related Endpoints

- [Authentication Status](../auth/status.md) - Check user authentication
- [User Profile](../resources/users.md) - Get user information for role checking

## Notes / Additional Information

- Uses Bootstrap Icons (`bi-*`) for menu icons
- All endpoints protected by `auth:sanctum` middleware
- See backend/routes/api.php and MenuController for implementation details