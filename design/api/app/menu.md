# GET /api/v1/app/menu

Get the application menu structure for the authenticated user.

## Request

### Method
```
GET /api/v1/app/menu
```

### Headers
```
Authorization: Bearer {access_token}
```

### Authentication Required
✅ **Yes** - This endpoint requires authentication

### Query Parameters
None

### Example Request

```bash
curl -X GET "https://api.example.com/api/v1/app/menu" \
  -H "Authorization: Bearer 1|abc123def456..."
```

## Response

### Success Response (HTTP 200)

```json
{
  "data": [
    {
      "type": "item",
      "id": 1,
      "name": "Home",
      "path": "/",
      "icon": "bi-house"
    },
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
    {
      "type": "divider"
    },
    {
      "type": "section",
      "title": "Sales",
      "items": [
        {
          "id": 40,
          "name": "Estimate",
          "path": "/list/estimates",
          "icon": "bi-receipt"
        }
      ]
    },
    {
      "type": "divider"
    },
    {
      "type": "section",
      "title": "Administration",
      "items": [
        {
          "id": 60,
          "name": "Users",
          "path": "/list/users",
          "icon": "bi-people",
          "mode": "form"
        },
        {
          "id": 40,
          "name": "Estimate",
          "path": "/list/estimates",
          "icon": "bi-receipt",
          "mode": "doc"
        }
      ]
    },
    {
      "type": "divider"
    },
    {
      "type": "item",
      "id": 90,
      "name": "Help",
      "path": "/help",
      "icon": "bi-question-circle"
    }
  ],
  "message": "Menu items retrieved successfully"
}
```

## Menu Item Types

### Item Type
Single menu item that links to a specific page.

```json
{
  "type": "item",
  "id": 1,
  "name": "Home",
  "path": "/",
  "icon": "bi-house"
}
```

#### Properties
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `type` | string | Yes | Always "item" |
| `id` | integer | Yes | Unique menu item identifier |
| `name` | string | Yes | Display name |
| `path` | string | Yes | Navigation path/URL |
| `icon` | string | Yes | Bootstrap icon class |
| `mode` | string | No | Optional mode (e.g., "form", "doc") |

### Section Type
Group of related menu items with a title.

```json
{
  "type": "section",
  "title": "Administration",
  "items": [
    {
      "id": 60,
      "name": "Users",
      "path": "/list/users",
      "icon": "bi-people",
      "mode": "form"
    }
  ]
}
```

#### Properties
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `type` | string | Yes | Always "section" |
| `title` | string | Yes | Section title |
| `items` | array | Yes | Array of menu items in section |

### Divider Type
Visual separator between menu sections.

```json
{
  "type": "divider"
}
```

#### Properties
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `type` | string | Yes | Always "divider" |

## Icon Classes

The menu uses Bootstrap Icons (bi-*) for consistent iconography:

- `bi-house` - Home icon
- `bi-calculator` - Calculator icon (Products)
- `bi-receipt` - Receipt icon (Estimates)
- `bi-people` - People icon (Users)
- `bi-question-circle` - Help icon

## Menu Structure

The application menu is organized into logical sections:

1. **Home** - Main dashboard
2. **List Section** - Data management pages
   - Products catalog
3. **Sales Section** - Sales-related functions
   - Estimates/Quotations
4. **Administration Section** - Admin functions
   - User management
   - Estimate management (with doc mode)
5. **Help** - Support and documentation

## Item Modes

Some menu items include a `mode` property:

- `"form"` - Opens in form/CRUD interface
- `"doc"` - Opens in document management interface

## Frontend Integration

### Vue.js Router Example
```javascript
// Convert menu items to router structure
const menuItems = response.data.data;

menuItems.forEach(item => {
  if (item.type === 'item') {
    // Add route for single items
    routes.push({
      path: item.path,
      name: item.name,
      component: getComponentByPath(item.path)
    });
  } else if (item.type === 'section') {
    // Add routes for section items
    item.items.forEach(sectionItem => {
      routes.push({
        path: sectionItem.path,
        name: sectionItem.name,
        component: getComponentByPath(sectionItem.path, sectionItem.mode)
      });
    });
  }
});
```

### Menu Rendering Example
```vue
<template>
  <nav class="sidebar">
    <div v-for="item in menuItems" :key="item.id || item.type">
      <!-- Single Item -->
      <router-link 
        v-if="item.type === 'item'"
        :to="item.path"
        class="nav-item"
      >
        <i :class="item.icon"></i>
        {{ item.name }}
      </router-link>
      
      <!-- Section with Items -->
      <div v-else-if="item.type === 'section'" class="nav-section">
        <h6 class="nav-title">{{ item.title }}</h6>
        <router-link
          v-for="subItem in item.items"
          :key="subItem.id"
          :to="subItem.path"
          class="nav-item"
        >
          <i :class="subItem.icon"></i>
          {{ subItem.name }}
        </router-link>
      </div>
      
      <!-- Divider -->
      <hr v-else-if="item.type === 'divider'" class="nav-divider">
    </div>
  </nav>
</template>
```

## Error Responses

### Unauthorized (HTTP 401)

```json
{
  "message": "Unauthenticated."
}
```

This error occurs when:
- No Authorization header is provided
- Invalid or expired token is provided

## Caching

Menu data is relatively static and can be cached:
- **Client-side**: Cache for user session
- **Server-side**: Consider caching menu structure
- **TTL**: Menu changes are infrequent, longer cache times acceptable

## Role-Based Menus

Currently, the menu is static for all authenticated users. Future enhancements may include:
- Role-based menu filtering
- Permission-based item visibility
- Dynamic menu generation based on user capabilities

## Related Endpoints

- [Authentication Status](../auth/status.md) - Check user authentication
- [User Profile](../resources/users.md) - Get user information for role checking