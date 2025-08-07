# Application Endpoints

This directory contains documentation for application-specific endpoints that provide core functionality beyond standard resource CRUD operations.

## Available Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/v1/app/menu` | Get application menu structure | Yes |
| POST | `/api/v1/documents/generate-pdf` | Generate PDF documents | Yes |
| POST | `/api/v1/documents/preview` | Preview document templates | Yes |
| GET | `/api/v1/documents/templates` | Get available templates | Yes |
| GET | `/api/v1/documents/templates/{template}` | Get template information | Yes |
| POST | `/api/v1/documents/validate` | Validate template data | Yes |

## Documentation Files

- **[menu.md](menu.md)** - Application menu structure API
- **[documents.md](documents.md)** - Document generation and template management APIs

## Common Features

### Authentication
All application endpoints require authentication via `auth:sanctum` middleware.

### Response Format
All endpoints follow the standard API response format:

```json
{
  "success": true/false,
  "message": "Human-readable message",
  "data": {}, // Endpoint-specific data
  "error": null // Error details when success is false
}
```

### Error Handling
Application endpoints use the standard error response format defined in [../error.md](../error.md).

## Rate Limiting
Application endpoints may have rate limiting applied to prevent abuse. Check individual endpoint documentation for specific limits.