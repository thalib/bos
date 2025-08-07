# Resource-Specific API Documentation

This directory contains detailed API documentation for each resource (model) in the BOS system.

## Available Resources

All resources follow RESTful conventions and support full CRUD operations:

| Resource | Endpoint Base | Description |
|----------|---------------|-------------|
| [Users](users.md) | `/api/v1/users` | User accounts and authentication |
| [Products](products.md) | `/api/v1/products` | Product catalog management |
| [Estimates](estimates.md) | `/api/v1/estimates` | Business estimates and quotations |

## Standard Resource Operations

Each resource supports these standard endpoints:

| Method | Endpoint | Description | Documentation |
|--------|----------|-------------|---------------|
| GET | `/api/v1/{resource}` | List resources with pagination, search, filtering | [index.md](../index.md) |
| POST | `/api/v1/{resource}` | Create new resource | [store.md](../store.md) |
| GET | `/api/v1/{resource}/{id}` | Get single resource | [show.md](../show.md) |
| PUT/PATCH | `/api/v1/{resource}/{id}` | Update resource | [update.md](../update.md) |
| DELETE | `/api/v1/{resource}/{id}` | Delete resource | [destroy.md](../destroy.md) |

## Auto-Generated Resources

Resources are automatically registered using the `#[ApiResource]` attribute on Eloquent models:

```php
#[ApiResource(uri: 'users', apiPrefix: 'api', version: 'v1')]
class User extends Authenticatable
{
    // Model implementation
}
```

## Common Features

All resources include:

- **Authentication**: Protected by `auth:sanctum` middleware
- **Validation**: Request validation using `StoreResourceRequest` and `UpdateResourceRequest`
- **Pagination**: Automatic pagination for list endpoints
- **Search**: Full-text search across searchable fields
- **Filtering**: Dynamic filtering based on model properties
- **Sorting**: Multi-column sorting with direction control
- **Schema**: Dynamic form schemas for frontend generation

## Resource Metadata

Each resource provides metadata for dynamic UI generation:

- **Index Columns**: Column definitions for data tables
- **API Schema**: Field definitions for dynamic forms
- **Searchable Fields**: Fields available for search
- **Filter Options**: Available filter values

## Business Logic

Business logic is handled by dedicated service classes:

- **Search**: `ResourceSearchService`
- **Filtering**: `ResourceFilterService`
- **Pagination**: `ResourcePaginationService`
- **Sorting**: `ResourceSortingService`
- **Metadata**: `ResourceMetadataService`

## Error Handling

All resource endpoints follow the standard error response format defined in [../error.md](../error.md).

## Authentication & Authorization

- All resource endpoints require authentication via `auth:sanctum`
- Additional authorization rules may apply per resource
- User permissions are checked before any CRUD operations

For detailed information about each resource, see the individual documentation files in this directory.