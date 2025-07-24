# Resource List Page Documentation

The `[resource].vue` page provides a comprehensive interface for managing resources with a data-driven approach. It receives the complete API response and delegates functionality to self-contained components that handle their own logic based on the API data structure.

**File Location:** `frontend/app/pages/list/[resource].vue`

## Relationship

```markdown
Page
├── MasterDetail (design\app\component\master-detail.md)
│ ├── List (design\app\component\list.md)
│ └── {Form (design/app/component/form.md) or Document (design\app\component\document.md)}
├── Pagination (design\app\component\Pagination.md)
└── Header (design\app\component\header.md)
  ├── Filter (design\app\component\filter.md)
  └── Search (design\app\component\search.md)


Form
└── FormField
```

Page: The main page that includes MasterDetail, Pagination, Header.
Header: Depends on Filter and Search components for its functionality.
MasterDetail: Depends on List and either Form or Document based on the context.
Form: Relies on FormField for rendering individual form fields.

Form or Document mode is determined based on the backend menu data provided by `backend\app\Http\Controllers\Api\MenuController.php`.

If the `mode` in the menu data is set to `form`, the page utilizes the **Form** component. Conversely, if the `mode` is set to `doc`, the **Document** component is used. This logic ensures dynamic rendering of the appropriate component based on the backend configuration.

```php
// Excerpt from MenuController.php: Lines 60-80
[
  'type' => 'section',
  'title' => 'Administration',
  'order' => 6,
  'items' => [
      [
          'id' => 60,
          'name' => 'Users',
          'path' => '/list/users',
          'icon' => 'bi-people',
          'mode' => 'form',
      ],
      [
          'id' => 40,
          'name' => 'Estimate',
          'path' => '/list/estimates',
          'icon' => 'bi-receipt',
          'mode' => 'doc',
      ],
  ],
},
```

## Features

- Dynamic resource management based on route parameters.
- Self-contained components that handle API response nodes.
- Integrated search, filtering, and pagination.
- Responsive design for all device types.
- Comprehensive error handling and loading states.
- Real-time data updates with optimistic UI.
- Bootstrap 5.3-based styling.

## API Integration

The page integrates with the standardized API endpoints following the design specification:

- **GET /api/v1/{resource}**: Retrieves resource list with pagination, sorting, filtering, and search.
- **POST /api/v1/{resource}**: Creates new resources.
- **PUT /api/v1/{resource}/{id}**: Updates existing resources.
- **DELETE /api/v1/{resource}/{id}**: Deletes resources.

For document mode implementation, use the following routes:

```bash
GET|HEAD  api/v1/estimates ........................ estimate.index › ApiResourceController@index
POST      api/v1/estimates ........................ estimate.store › ApiResourceController@store
GET|HEAD  api/v1/estimates/{id} ..................... estimate.show › ApiResourceController@show
PUT       api/v1/estimates/{id} ................. estimate.update › ApiResourceController@update
PATCH     api/v1/estimates/{id} .................. estimate.patch › ApiResourceController@update
DELETE    api/v1/estimates/{id} ............... estimate.destroy › ApiResourceController@destroy
```

For form mode implementation, use the following routes:

```bash
GET|HEAD  api/v1/products .......................... product.index › ApiResourceController@index
POST      api/v1/products .......................... product.store › ApiResourceController@store
GET|HEAD  api/v1/products/{id} ....................... product.show › ApiResourceController@show
PUT       api/v1/products/{id} ................... product.update › ApiResourceController@update
PATCH     api/v1/products/{id} .................... product.patch › ApiResourceController@update
DELETE    api/v1/products/{id} ................. product.destroy › ApiResourceController@destroy
```

## Data Flow

The page receives the complete API response and passes specific nodes to components:

```json
{
  "success": true,
  "message": "Resources retrieved successfully",
  "data": [ /* resource items */ ],
  "pagination": { /* pagination metadata */ },
  "search": "query string" | null,
  "sort": { "column": "name", "dir": "asc" } | null,
  "filters": {
    "applied": { "field": "status", "value": "active" },
    "available": [ /* filter options */ ]
  } | null,
  "schema": [ /* form schema */ ] | null,
  "columns": [ /* table columns */ ],
  "notifications": [ /* user notifications */ ] | null,
  "error": { /* error details */ } | null
}
```

## Notes

- The page acts as a coordinator, delegating functionality to self-contained components.
- All API data is passed directly to components as complete nodes.
- Components handle their own logic based on the API response structure.
- Follows the new API specification for consistent data handling.
- Uses Bootstrap 5.3 classes for responsive design.
- Implements proper error handling and loading states.
- Maintains URL state for bookmarking and navigation.
- Provides comprehensive user feedback through `frontend\app\utils\notify.ts` for notifications.
