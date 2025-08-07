# Estimates Resource API

Complete API documentation for the Estimates resource (`/api/v1/estimates`).

## Resource Overview

The Estimates resource manages business estimates, quotations, and proposals in the BOS system, including customer information, line items, pricing, and document generation.

**Base URL:** `/api/v1/estimates`
**Authentication:** Required (Bearer token)
**Auto-generated:** Yes (via `#[ApiResource]` attribute)

## Database Schema

| Field | Type | Default | Unique | Nullable | Description |
|-------|------|---------|--------|----------|-------------|
| `id` | integer | - | Yes | No | Primary key |
| `name` | string | `null` | Yes | Yes | Estimate name |
| `type` | string | `'ESTIMATE'` | No | No | Document type |
| `number` | string | - | Yes | No | Unique estimate number |
| `date` | date | - | No | No | Estimate date |
| `validity` | integer | `5` | No | No | Validity in days |
| `status` | enum | `'DRAFT'` | No | No | Estimate status |
| `active` | boolean | `true` | No | No | Active status |
| `branch_id` | string | - | No | No | Branch identifier |
| `channel` | string | - | No | No | Sales channel |
| `salesperson` | string | - | No | No | Salesperson name |
| `refrence` | string | `'PO-2025-0010'` | No | No | Reference number |
| `tax_inclusive` | boolean | `false` | No | No | Whether prices are tax inclusive |
| `show_bank_details` | boolean | `true` | No | No | Show bank details in document |
| `bank_id` | string | `null` | No | Yes | Bank identifier |
| `show_signature` | boolean | `true` | No | No | Show signature in document |
| `show_upi_qr` | boolean | `true` | No | No | Show UPI QR code |
| `customer_id` | string | - | No | No | Customer identifier |
| `customer_billing` | json | - | No | No | Customer billing address |
| `customer_shipping` | json | - | No | No | Customer shipping address |
| `items` | json | - | No | No | Estimate line items |
| `subtotal` | decimal(15,2) | `0.00` | No | No | Subtotal amount |
| `total_cost` | decimal(15,2) | `0.00` | No | No | Total cost amount |
| `taxable_amount` | decimal(15,2) | `0.00` | No | No | Taxable amount |
| `total_tax` | decimal(15,2) | `0.00` | No | No | Total tax amount |
| `shipping_charges` | decimal(15,2) | `0.00` | No | No | Shipping charges |
| `other_charges` | decimal(15,2) | `0.00` | No | No | Other charges |
| `adjustment` | decimal(15,2) | `0.00` | No | No | Adjustment amount |
| `round_off` | decimal(15,2) | `0.00` | No | No | Round off amount |
| `grand_total` | decimal(15,2) | `0.00` | No | No | Grand total amount |
| `terms` | text | `null` | No | Yes | Terms and conditions |
| `notes` | text | `null` | No | Yes | Additional notes |
| `created_by` | string | - | No | No | Created by user |
| `updated_by` | string | - | No | No | Updated by user |
| `created_at` | timestamp | - | No | No | Creation timestamp |
| `updated_at` | timestamp | - | No | No | Last update timestamp |

## Enums and Options

### Estimate Types
- `ESTIMATE` - Standard estimate (default)
- `QUOTATION` - Quotation document
- `PROPOSAL` - Business proposal

### Status Options
- `DRAFT` - Draft status (default)
- `SENT` - Sent to customer
- `ACCEPTED` - Accepted by customer
- `REJECTED` - Rejected by customer
- `EXPIRED` - Expired estimate
- `INVOICED` - Converted to invoice

### Channel Options
- `Online` - Online sales channel
- `Offline` - Offline sales channel

## Standard CRUD Operations

### List Estimates
```
GET /api/v1/estimates
```
- **Purpose**: Get paginated list of estimates with search, filtering, and sorting
- **Documentation**: [../index.md](../index.md)
- **Response**: Array of estimate objects with pagination metadata

### Create Estimate
```
POST /api/v1/estimates
```
- **Purpose**: Create a new estimate
- **Documentation**: [../store.md](../store.md)
- **Response**: Created estimate object

### Get Estimate
```
GET /api/v1/estimates/{id}
```
- **Purpose**: Get single estimate by ID
- **Documentation**: [../show.md](../show.md)
- **Response**: Single estimate object

### Update Estimate
```
PUT/PATCH /api/v1/estimates/{id}
```
- **Purpose**: Update existing estimate
- **Documentation**: [../update.md](../update.md)
- **Response**: Updated estimate object

### Delete Estimate
```
DELETE /api/v1/estimates/{id}
```
- **Purpose**: Delete estimate
- **Documentation**: [../destroy.md](../destroy.md)
- **Response**: Success confirmation

## Index Columns Configuration

The following columns are displayed in list views:

```json
[
  {
    "field": "number",
    "label": "Estimate Number",
    "sortable": true,
    "clickable": true,
    "search": true
  },
  {
    "field": "date",
    "label": "Date",
    "sortable": true,
    "format": "date"
  },
  {
    "field": "customer_id",
    "label": "Customer",
    "sortable": true,
    "search": true
  },
  {
    "field": "status",
    "label": "Status",
    "sortable": true,
    "search": true
  },
  {
    "field": "salesperson",
    "label": "Salesperson",
    "sortable": true,
    "search": true
  },
  {
    "field": "grand_total",
    "label": "Total Amount",
    "sortable": true,
    "format": "currency",
    "align": "right"
  },
  {
    "field": "validity",
    "label": "Validity (Days)",
    "sortable": true,
    "format": "number",
    "align": "center"
  },
  {
    "field": "active",
    "label": "Status",
    "sortable": true,
    "format": "boolean",
    "align": "center"
  }
]
```

## API Schema for Forms

The API provides dynamic form schema organized in groups:

### General Information
- **`active`**: Checkbox (default: `true`)
- **`type`**: Select (ESTIMATE/QUOTATION/PROPOSAL, default: ESTIMATE)
- **`number`**: Text input (required, max 50 characters)
- **`date`**: Date input (required)
- **`validity`**: Number input (min: 1, max: 365, default: 30)
- **`status`**: Select (DRAFT/SENT/ACCEPTED/REJECTED/EXPIRED/INVOICED, default: DRAFT)
- **`refrence`**: Text input (max 100 characters)
- **`channel`**: Select (Online/Offline, default: Online)

### Customer Information
- **`customer_id`**: Number input (required, min: 1)
- **`salesperson`**: Text input (max 100 characters)
- **`branch_id`**: Number input (min: 1)
- **`customer_billing`**: Object with properties:
  - `name` (string, required)
  - `address` (string, required)
  - `city` (string, required)
  - `state` (string, required)
  - `pincode` (string, required)
  - `phone` (string, optional)
  - `email` (string, optional)
- **`customer_shipping`**: Object with properties:
  - `name` (string, required)
  - `address` (string, required)
  - `city` (string, required)
  - `state` (string, required)
  - `pincode` (string, required)
  - `phone` (string, optional)

### Items
- **`items`**: Array (required, min 1 item) with properties:
  - `product_id` (number, required)
  - `name` (string, required)
  - `description` (string, optional)
  - `quantity` (number, required, min: 1)
  - `unit_price` (decimal, required, min: 0)
  - `discount` (decimal, optional, min: 0)
  - `tax_rate` (decimal, optional, min: 0, max: 100)
  - `total` (decimal, required, min: 0)

### Totals
- **`subtotal`**: Decimal input (min: 0, default: 0.00)
- **`total_cost`**: Decimal input (min: 0, default: 0.00)
- **`taxable_amount`**: Decimal input (min: 0, default: 0.00)
- **`total_tax`**: Decimal input (min: 0, default: 0.00)
- **`shipping_charges`**: Decimal input (min: 0, default: 0.00)
- **`other_charges`**: Decimal input (min: 0, default: 0.00)
- **`adjustment`**: Decimal input (default: 0.00)
- **`round_off`**: Decimal input (default: 0.00)
- **`grand_total`**: Decimal input (required, min: 0, default: 0.00)

### Options
- **`tax_inclusive`**: Checkbox (default: true)
- **`show_bank_details`**: Checkbox (default: false)
- **`bank_id`**: Number input (min: 1)
- **`show_signature`**: Checkbox (default: false)
- **`show_upi_qr`**: Checkbox (default: false)

### Terms & Notes
- **`terms`**: Textarea (optional)
- **`notes`**: Textarea (optional)

## Available Filters

The estimate resource supports filtering by:

```json
{
  "status": {
    "values": ["DRAFT", "SENT", "ACCEPTED", "REJECTED", "EXPIRED", "INVOICED"]
  },
  "channel": {
    "values": ["Online", "Offline"]
  }
}
```

## Data Structures

### Customer Address Object
```json
{
  "name": "John Doe",
  "address": "123 Main Street, Suite 100",
  "city": "Mumbai",
  "state": "Maharashtra",
  "pincode": "400001",
  "phone": "+91 9876543210",
  "email": "john@example.com"
}
```

### Line Item Object
```json
{
  "product_id": 1,
  "name": "Premium Widget",
  "description": "High-quality widget with advanced features",
  "quantity": 5,
  "unit_price": 299.99,
  "discount": 10.00,
  "tax_rate": 18.00,
  "total": 1499.95
}
```

## Validation Rules

### Create Estimate (POST)
```json
{
  "type": "in:ESTIMATE,QUOTATION,PROPOSAL",
  "number": "required|string|max:50|unique:estimates",
  "date": "required|date",
  "validity": "integer|min:1|max:365",
  "status": "in:DRAFT,SENT,ACCEPTED,REJECTED,EXPIRED,INVOICED",
  "customer_id": "required|integer|min:1",
  "items": "required|array|min:1",
  "items.*.product_id": "required|integer",
  "items.*.name": "required|string",
  "items.*.quantity": "required|numeric|min:1",
  "items.*.unit_price": "required|numeric|min:0",
  "items.*.total": "required|numeric|min:0",
  "grand_total": "required|numeric|min:0"
}
```

### Update Estimate (PUT/PATCH)
Similar to create but with unique validation exclusions for the current estimate.

## Business Logic & Features

### Grand Total Calculation
The `getGrandTotalAttribute()` method provides the calculated grand total from all pricing fields.

### Date Formatting
The `getFormattedDateAttribute()` provides formatted date output for display purposes.

### Audit Trail
- `created_by` and `updated_by` track user actions
- Timestamps track creation and modification times

## Example Requests

### Create Estimate
```bash
curl -X POST "https://api.example.com/api/v1/estimates" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "ESTIMATE",
    "number": "EST-2025-001",
    "date": "2025-01-15",
    "validity": 30,
    "status": "DRAFT",
    "customer_id": "1",
    "salesperson": "John Sales",
    "customer_billing": {
      "name": "ABC Company",
      "address": "123 Business St",
      "city": "Mumbai",
      "state": "Maharashtra",
      "pincode": "400001",
      "phone": "9876543210",
      "email": "contact@abc.com"
    },
    "items": [
      {
        "product_id": 1,
        "name": "Premium Widget",
        "quantity": 5,
        "unit_price": 299.99,
        "discount": 0,
        "tax_rate": 18.00,
        "total": 1499.95
      }
    ],
    "subtotal": 1499.95,
    "total_tax": 269.99,
    "grand_total": 1769.94,
    "terms": "Payment due within 30 days",
    "created_by": "admin",
    "updated_by": "admin"
  }'
```

### Filter Estimates
```bash
curl -X GET "https://api.example.com/api/v1/estimates?filter=status:SENT&sort=date&dir=desc" \
  -H "Authorization: Bearer {token}"
```

### Search Estimates
```bash
curl -X GET "https://api.example.com/api/v1/estimates?search=EST-2025&filter=channel:Online" \
  -H "Authorization: Bearer {token}"
```

## Response Examples

### Single Estimate Response
```json
{
  "success": true,
  "message": "Estimate retrieved successfully",
  "data": {
    "id": 1,
    "name": null,
    "type": "ESTIMATE",
    "number": "EST-2025-001",
    "date": "2025-01-15",
    "validity": 30,
    "status": "DRAFT",
    "active": true,
    "customer_id": "1",
    "salesperson": "John Sales",
    "customer_billing": {
      "name": "ABC Company",
      "address": "123 Business St",
      "city": "Mumbai",
      "state": "Maharashtra",
      "pincode": "400001",
      "phone": "9876543210",
      "email": "contact@abc.com"
    },
    "items": [
      {
        "product_id": 1,
        "name": "Premium Widget",
        "quantity": 5,
        "unit_price": "299.99",
        "total": "1499.95"
      }
    ],
    "subtotal": "1499.95",
    "total_tax": "269.99",
    "grand_total": "1769.94",
    "terms": "Payment due within 30 days",
    "created_by": "admin",
    "updated_by": "admin",
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

## Error Handling

Common error scenarios:

- **422 Validation Error**: Invalid data format or constraint violations
- **404 Not Found**: Estimate ID doesn't exist
- **409 Conflict**: Unique constraint violations (number)
- **401 Unauthorized**: Invalid or missing authentication token

For detailed error response format, see [../error.md](../error.md).