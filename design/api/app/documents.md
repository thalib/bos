# Document Management APIs

Document generation and template management endpoints for the BOS system.

## Overview

The document management system provides PDF generation, template management, and document preview capabilities. It supports dynamic document generation from templates with data binding.

**Base URL:** `/api/v1/documents`
**Authentication:** Required (Bearer token)

## Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/documents/generate-pdf` | Generate PDF document from template |
| POST | `/documents/preview` | Preview document without generating PDF |
| GET | `/documents/templates` | Get list of available templates |
| GET | `/documents/templates/{template}` | Get specific template information |
| POST | `/documents/validate` | Validate template data |

## Common Features

- **Template-based Generation**: All documents are generated from templates
- **Data Binding**: Templates support dynamic data injection
- **Validation**: Template data can be validated before generation
- **Preview**: HTML preview before PDF generation
- **Metadata**: Template metadata and configuration retrieval

---

## POST /documents/generate-pdf

Generate a PDF document from a template with provided data.

### Request

```bash
POST /api/v1/documents/generate-pdf
```

#### Headers
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

#### Request Body

```json
{
  "template": "string",      // Required: Template name
  "data": {},                // Required: Template data object
  "options": {},             // Optional: PDF generation options
  "filename": "string"       // Optional: Custom filename
}
```

#### Field Validation

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `template` | string | Yes | Name of the template to use |
| `data` | object | Yes | Data to inject into template |
| `options` | object | No | PDF generation options |
| `filename` | string | No | Custom filename (auto-generated if not provided) |

### Response

#### Success Response (HTTP 200)
Returns PDF file as binary data with appropriate headers:

```
Content-Type: application/pdf
Content-Disposition: attachment; filename="document.pdf"
Content-Length: {file_size}
Cache-Control: no-cache, no-store, must-revalidate
```

#### Error Response (HTTP 422/500)

```json
{
  "success": false,
  "message": "PDF generation failed",
  "error": {
    "code": "PDF_GENERATION_ERROR",
    "details": ["Template not found", "Invalid data structure"]
  }
}
```

### Example Request

```bash
curl -X POST "https://api.example.com/api/v1/documents/generate-pdf" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "template": "estimate",
    "filename": "estimate_EST-001.pdf",
    "data": {
      "estimate": {
        "number": "EST-001",
        "date": "2025-01-15",
        "customer": {
          "name": "ABC Company",
          "address": "123 Business St"
        },
        "items": [
          {
            "name": "Product 1",
            "quantity": 5,
            "price": 100.00,
            "total": 500.00
          }
        ],
        "grand_total": 590.00
      }
    },
    "options": {
      "format": "A4",
      "orientation": "portrait"
    }
  }' \
  --output "estimate.pdf"
```

---

## POST /documents/preview

Generate an HTML preview of the document template.

### Request

```bash
POST /api/v1/documents/preview
```

#### Request Body

```json
{
  "template": "string",      // Required: Template name
  "data": {}                 // Required: Template data object
}
```

### Response

#### Success Response (HTTP 200)

```json
{
  "success": true,
  "message": "Preview generated successfully",
  "data": {
    "template": "estimate",
    "preview": "<html>...</html>",
    "generated_at": "2025-01-15T10:30:00.000Z"
  }
}
```

### Example Request

```bash
curl -X POST "https://api.example.com/api/v1/documents/preview" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "template": "estimate",
    "data": {
      "estimate": {
        "number": "EST-001",
        "customer": {
          "name": "ABC Company"
        }
      }
    }
  }'
```

---

## GET /documents/templates

Get list of all available document templates.

### Request

```bash
GET /api/v1/documents/templates
```

### Response

#### Success Response (HTTP 200)

```json
{
  "success": true,
  "message": "Templates retrieved successfully",
  "data": [
    {
      "name": "estimate",
      "title": "Estimate Template",
      "description": "Standard business estimate template",
      "version": "1.0.0",
      "category": "sales",
      "supported_formats": ["pdf", "html"]
    },
    {
      "name": "invoice",
      "title": "Invoice Template",
      "description": "Standard business invoice template",
      "version": "1.2.0",
      "category": "accounting",
      "supported_formats": ["pdf", "html"]
    }
  ]
}
```

### Example Request

```bash
curl -X GET "https://api.example.com/api/v1/documents/templates" \
  -H "Authorization: Bearer {token}"
```

---

## GET /documents/templates/{template}

Get detailed information about a specific template.

### Request

```bash
GET /api/v1/documents/templates/{template}
```

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `template` | string | Yes | Template name |

### Response

#### Success Response (HTTP 200)

```json
{
  "success": true,
  "message": "Template information retrieved successfully",
  "data": {
    "template": "estimate",
    "exists": true,
    "metadata": {
      "name": "estimate",
      "title": "Estimate Template",
      "description": "Standard business estimate template",
      "version": "1.0.0",
      "author": "BOS System",
      "category": "sales"
    },
    "config": {
      "required_fields": [
        "estimate.number",
        "estimate.date", 
        "estimate.customer",
        "estimate.items"
      ],
      "optional_fields": [
        "estimate.terms",
        "estimate.notes"
      ],
      "supported_formats": ["pdf", "html"],
      "default_options": {
        "format": "A4",
        "orientation": "portrait",
        "margins": {
          "top": "20mm",
          "right": "15mm", 
          "bottom": "20mm",
          "left": "15mm"
        }
      }
    }
  }
}
```

#### Error Response (HTTP 404)

```json
{
  "success": false,
  "message": "Template 'unknown' not found",
  "error": {
    "code": "TEMPLATE_NOT_FOUND",
    "details": []
  }
}
```

### Example Request

```bash
curl -X GET "https://api.example.com/api/v1/documents/templates/estimate" \
  -H "Authorization: Bearer {token}"
```

---

## POST /documents/validate

Validate template data before document generation.

### Request

```bash
POST /api/v1/documents/validate
```

#### Request Body

```json
{
  "template": "string",      // Required: Template name
  "data": {}                 // Required: Template data to validate
}
```

### Response

#### Success Response (HTTP 200)

```json
{
  "success": true,
  "message": "Template data validation completed",
  "data": {
    "template": "estimate",
    "valid": true,
    "validation": {
      "valid": true,
      "errors": [],
      "warnings": [],
      "missing_optional": ["estimate.terms"],
      "field_count": {
        "required": 4,
        "optional": 1,
        "provided": 4
      }
    }
  }
}
```

#### Validation Failed Response (HTTP 200)

```json
{
  "success": true,
  "message": "Template data validation completed",
  "data": {
    "template": "estimate",
    "valid": false,
    "validation": {
      "valid": false,
      "errors": [
        "Missing required field: estimate.number",
        "Invalid date format in estimate.date"
      ],
      "warnings": [
        "Missing optional field: estimate.notes"
      ],
      "missing_optional": ["estimate.notes"],
      "field_count": {
        "required": 4,
        "optional": 2,
        "provided": 3
      }
    }
  }
}
```

### Example Request

```bash
curl -X POST "https://api.example.com/api/v1/documents/validate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "template": "estimate",
    "data": {
      "estimate": {
        "number": "EST-001",
        "date": "2025-01-15",
        "customer": {
          "name": "ABC Company"
        },
        "items": []
      }
    }
  }'
```

---

## PDF Generation Options

### Supported Options

```json
{
  "format": "A4|A3|A5|Letter|Legal",
  "orientation": "portrait|landscape", 
  "margins": {
    "top": "20mm",
    "right": "15mm",
    "bottom": "20mm", 
    "left": "15mm"
  },
  "header": {
    "enabled": true,
    "height": "15mm",
    "content": "Header content"
  },
  "footer": {
    "enabled": true,
    "height": "15mm",
    "content": "Footer content"
  },
  "quality": "high|medium|low",
  "compression": true
}
```

## Error Handling

Common error scenarios:

- **404 Template Not Found**: Specified template doesn't exist
- **422 Validation Error**: Invalid template data or missing required fields
- **500 Generation Error**: PDF generation failed due to template or data issues
- **401 Unauthorized**: Invalid or missing authentication token

## Security Considerations

- **Authentication Required**: All endpoints require valid bearer token
- **Template Validation**: Templates are validated before processing
- **Data Sanitization**: Input data is sanitized to prevent injection attacks
- **File Size Limits**: Generated PDFs have size limits to prevent abuse
- **Rate Limiting**: Document generation may be rate-limited

## Related Resources

- [Estimates](../resources/estimates.md) - Estimate data structure for document generation
- [Error Responses](../error.md) - Standard error response format