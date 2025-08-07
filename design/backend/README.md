# BOS API Documentation

This directory contains comprehensive API documentation for the BOS (Business Operations System) backend.

## Structure

### Core API Endpoints
- **[index.md](index.md)** - GET endpoints for listing resources (with pagination, search, filtering)
- **[show.md](show.md)** - GET endpoints for retrieving single resources
- **[store.md](store.md)** - POST endpoints for creating new resources
- **[update.md](update.md)** - PUT/PATCH endpoints for updating existing resources
- **[destroy.md](destroy.md)** - DELETE endpoints for removing resources
- **[error.md](error.md)** - Error response structure and codes

### Authentication & Authorization
- **[auth/](auth/)** - Authentication endpoints and flows
  - **[login.md](auth/login.md)** - User login
  - **[register.md](auth/register.md)** - User registration
  - **[logout.md](auth/logout.md)** - User logout
  - **[refresh.md](auth/refresh.md)** - Token refresh
  - **[status.md](auth/status.md)** - Authentication status

### Resource-Specific Documentation
- **[resources/](resources/)** - Detailed documentation for each model/resource
  - **[users.md](resources/users.md)** - User resource API
  - **[products.md](resources/products.md)** - Product resource API
  - **[estimates.md](resources/estimates.md)** - Estimate resource API

### Application Features
- **[app/](app/)** - Application-specific endpoints
  - **[menu.md](app/menu.md)** - Application menu structure
  - **[documents.md](app/documents.md)** - Document generation services

## API Base URL

```
{base_url}/api/v1
```

## Authentication

All API endpoints require authentication using Laravel Sanctum tokens, except for:
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`
- `GET /api/v1/auth/status`

Include the token in requests:
```
Authorization: Bearer {token}
```

## Common Response Format

All API responses follow a standardized format:

```json
{
  "success": true/false,
  "message": "Human-readable message",
  "data": {}, // Response data
  "error": null // Error details (when success is false)
}
```

## Auto-Generated Resources

Models with the `#[ApiResource]` attribute are automatically registered as RESTful resources:

- `User` → `/api/v1/users`
- `Product` → `/api/v1/products`
- `Estimate` → `/api/v1/estimates`
- `TestModel` → `/api/v1/test-models`

Each resource supports full CRUD operations:
- `GET /api/v1/{resource}` - List resources
- `POST /api/v1/{resource}` - Create resource
- `GET /api/v1/{resource}/{id}` - Show resource
- `PUT/PATCH /api/v1/{resource}/{id}` - Update resource
- `DELETE /api/v1/{resource}/{id}` - Delete resource

## Quick Reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | User login |
| POST | `/auth/register` | User registration |
| GET | `/auth/status` | Check auth status |
| POST | `/auth/logout` | User logout |
| GET | `/app/menu` | Get application menu |
| GET | `/users` | List users |
| POST | `/users` | Create user |
| GET | `/products` | List products |
| POST | `/products` | Create product |
| GET | `/estimates` | List estimates |
| POST | `/estimates` | Create estimate |
| POST | `/documents/generate-pdf` | Generate PDF document |
| GET | `/documents/templates` | Get available templates |

For detailed documentation on each endpoint, refer to the specific files in this directory.