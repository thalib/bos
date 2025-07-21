# Resource List Page Documentation

## Overview

The `[resource].vue` page provides a comprehensive interface for managing resources with a data-driven approach. It receives the complete API response and delegates functionality to self-contained components that handle their own logic based on the API data structure.

## Relationship

```markdown
Page
├── MasterDetail (design\ui\component\master-detail.md)
│   ├── List (design\ui\component\list.md)
│   └── {Form (design/ui/component/form.md) or Document (design\ui\component\document.md)}
├── PaginationS (design\ui\component\paginations.md)
├── Header (design\ui\component\header.md)
│   ├── Filter (design\ui\component\filter.md)
│   └── Search (design\ui\component\search.md)
└── Toast

Form
└── FormField
```

Page: The main page that includes MasterDetail, PaginationS, Header, and Toast.
Header: Depends on Filter and Search components for its functionality.
MasterDetail: Depends on List and either Form or Document based on the context.
Form: Relies on FormField for rendering individual form fields.

## Features

- Dynamic resource management based on route parameters
- Self-contained components that handle API response nodes
- Integrated search, filtering, and pagination
- Responsive design for all device types
- Comprehensive error handling and loading states
- Real-time data updates with optimistic UI
- Bootstrap 5.3 based styling

## API Integration

The page integrates with the standardized API endpoints following the design specification:

- **GET /api/v1/{resource}**: Retrieves resource list with pagination, sorting, filtering, and search
- **POST /api/v1/{resource}**: Creates new resources
- **PUT /api/v1/{resource}/{id}**: Updates existing resources
- **DELETE /api/v1/{resource}/{id}**: Deletes resources

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

- The page acts as a coordinator, delegating functionality to self-contained components
- All API data is passed directly to components as complete nodes
- Components handle their own logic based on the API response structure
- Follows the new API specification for consistent data handling
- Uses Bootstrap 5.3 classes for responsive design
- Implements proper error handling and loading states
- Maintains URL state for bookmarking and navigation
- Provides comprehensive user feedback through notifications
