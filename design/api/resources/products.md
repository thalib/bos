# Products Resource API

Complete API documentation for the Products resource (`/api/v1/products`).

## Resource Overview

The Products resource manages product catalog, inventory, pricing, and related product information in the BOS system.

**Base URL:** `/api/v1/products`
**Authentication:** Required (Bearer token)
**Auto-generated:** Yes (via `#[ApiResource]` attribute)

## Database Schema

| Field | Type | Default | Unique | Nullable | Description |
|-------|------|---------|--------|----------|-------------|
| `id` | integer | - | Yes | No | Primary key |
| `name` | string | - | No | No | Product name |
| `slug` | string | - | Yes | No | URL-friendly product identifier |
| `type` | enum | `'simple'` | No | No | Product type |
| `publication_status` | enum | `'draft'` | No | No | Publication status |
| `active` | boolean | `true` | No | No | Product status |
| `description` | text | `null` | No | Yes | Full product description |
| `short_description` | text | `null` | No | Yes | Brief product description |
| `sku` | string | `null` | Yes | Yes | Stock Keeping Unit |
| `barcode` | string | `null` | No | Yes | Product barcode |
| `brand` | string | `'ASENSAR'` | No | No | Product brand |
| `cost` | decimal(10,2) | `0.00` | No | No | Cost price |
| `mrp` | decimal(10,2) | `0.00` | No | No | Maximum Retail Price |
| `price` | decimal(10,2) | `0.00` | No | No | Regular selling price |
| `sale_price` | decimal(10,2) | `0.00` | No | No | Sale/discounted price |
| `taxable` | boolean | `true` | No | No | Whether product is taxable |
| `tax_hsn_code` | string | `null` | No | Yes | HSN code for GST |
| `tax_rate` | decimal(5,2) | `18.00` | No | No | Tax rate percentage |
| `tax_inclusive` | boolean | `true` | No | No | Whether prices include tax |
| `stock_track` | boolean | `false` | No | No | Whether to track stock |
| `stock_quantity` | integer | `0` | No | No | Available stock quantity |
| `stock_low_threshold` | integer | `0` | No | No | Low stock alert threshold |
| `length` | decimal(8,2) | `null` | No | Yes | Product length (cm) |
| `width` | decimal(8,2) | `null` | No | Yes | Product width (cm) |
| `height` | decimal(8,2) | `null` | No | Yes | Product height (cm) |
| `weight` | decimal(8,2) | `null` | No | Yes | Product weight (kg) |
| `unit` | string | `'nos'` | No | No | Unit of measurement |
| `shipping_weight` | decimal(8,2) | `null` | No | Yes | Shipping weight (kg) |
| `shipping_required` | boolean | `true` | No | No | Whether shipping is required |
| `shipping_taxable` | boolean | `true` | No | No | Whether shipping is taxable |
| `shipping_class_id` | integer | `0` | No | No | Shipping class identifier |
| `image` | string | `null` | No | Yes | Featured image URL |
| `images` | json | `null` | No | Yes | Array of image URLs |
| `external_url` | string | `null` | No | Yes | External product URL |
| `categories` | json | `null` | No | Yes | Product categories |
| `tags` | json | `null` | No | Yes | Product tags |
| `attributes` | json | `null` | No | Yes | Product attributes |
| `variations` | json | `null` | No | Yes | Product variations |
| `meta_data` | json | `null` | No | Yes | Additional metadata |
| `related_ids` | json | `null` | No | Yes | Related product IDs |
| `upsell_ids` | json | `null` | No | Yes | Upsell product IDs |
| `cross_sell_ids` | json | `null` | No | Yes | Cross-sell product IDs |
| `created_at` | timestamp | - | No | No | Creation timestamp |
| `updated_at` | timestamp | - | No | No | Last update timestamp |

## Enums and Options

### Product Types
- `simple` - Simple product (default)
- `variable` - Variable product with variations
- `grouped` - Grouped product
- `external` - External/affiliate product

### Publication Status
- `draft` - Draft (not published)
- `published` - Published and visible
- `discontinued` - Discontinued product
- `private` - Private product

### Units
- `nos` - Numbers (default)
- `piece` - Piece
- `kg` - Kilogram
- `gram` - Gram
- `liter` - Liter
- `meter` - Meter

## Standard CRUD Operations

### List Products
```
GET /api/v1/products
```
- **Purpose**: Get paginated list of products with search, filtering, and sorting
- **Documentation**: [../index.md](../index.md)
- **Response**: Array of product objects with pagination metadata

### Create Product
```
POST /api/v1/products
```
- **Purpose**: Create a new product
- **Documentation**: [../store.md](../store.md)
- **Response**: Created product object

### Get Product
```
GET /api/v1/products/{id}
```
- **Purpose**: Get single product by ID
- **Documentation**: [../show.md](../show.md)
- **Response**: Single product object

### Update Product
```
PUT/PATCH /api/v1/products/{id}
```
- **Purpose**: Update existing product
- **Documentation**: [../update.md](../update.md)
- **Response**: Updated product object

### Delete Product
```
DELETE /api/v1/products/{id}
```
- **Purpose**: Delete product
- **Documentation**: [../destroy.md](../destroy.md)
- **Response**: Success confirmation

## Index Columns Configuration

The following columns are displayed in list views:

```json
[
  {
    "field": "name",
    "label": "Product Name",
    "sortable": true,
    "clickable": true,
    "search": true
  },
  {
    "field": "sku",
    "label": "SKU",
    "sortable": true,
    "search": true
  },
  {
    "field": "type",
    "label": "Type",
    "sortable": true,
    "search": true
  },
  {
    "field": "cost",
    "label": "Cost",
    "sortable": true,
    "format": "currency",
    "align": "right"
  },
  {
    "field": "price",
    "label": "Price",
    "sortable": true,
    "format": "currency",
    "align": "right"
  },
  {
    "field": "mrp",
    "label": "MRP",
    "sortable": true,
    "format": "currency",
    "align": "right"
  },
  {
    "field": "stock_quantity",
    "label": "Stock",
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
- **`name`**: Text input (required, max 255 characters)
- **`slug`**: Text input (required, auto-generated from name)
- **`type`**: Select (simple/variable/grouped/external, default: simple)
- **`publication_status`**: Select (draft/published/discontinued/private, default: draft)
- **`sku`**: Text input (optional, max 100 characters)
- **`barcode`**: Text input (optional, max 100 characters)
- **`brand`**: Text input (default: "ASENSAR")
- **`unit`**: Select (nos/piece/kg/gram/liter/meter, default: nos)
- **`categories`**: Multi-select (optional)
- **`image`**: File upload (accept: image/*)
- **`images`**: Multiple file upload (accept: image/*)
- **`external_url`**: URL input (optional, max 500 characters)

### Price & Inventory
- **`cost`**: Decimal input (min: 0, prefix: ₹)
- **`mrp`**: Decimal input (min: 0, prefix: ₹)
- **`price`**: Decimal input (required, min: 0, prefix: ₹)
- **`sale_price`**: Decimal input (min: 0, prefix: ₹)
- **`stock_track`**: Checkbox (default: false)
- **`stock_quantity`**: Number input (min: 0, step: 1)
- **`stock_low_threshold`**: Number input (min: 0, step: 1)

### Tax Information
- **`taxable`**: Checkbox (default: true)
- **`tax_hsn_code`**: Text input (max 20 characters)
- **`tax_rate`**: Number input (min: 0, max: 100, step: 0.01, suffix: %)
- **`tax_inclusive`**: Checkbox (default: true)

### Shipping
- **`length`**: Decimal input (min: 0, step: 0.01, suffix: cm)
- **`width`**: Decimal input (min: 0, step: 0.01, suffix: cm)
- **`height`**: Decimal input (min: 0, step: 0.01, suffix: cm)
- **`weight`**: Decimal input (min: 0, step: 0.01, suffix: kg)
- **`shipping_weight`**: Decimal input (min: 0, step: 0.01, suffix: kg)
- **`shipping_required`**: Checkbox (default: true)
- **`shipping_taxable`**: Checkbox (default: true)
- **`shipping_class_id`**: Number input (min: 0, step: 1)

### Other
- **`description`**: Textarea (rows: 4)
- **`short_description`**: Textarea (rows: 2)
- **`tags`**: Tags input
- **`attributes`**: Array input
- **`variations`**: Array input
- **`related_ids`**: Array input
- **`upsell_ids`**: Array input
- **`cross_sell_ids`**: Array input
- **`meta_data`**: Array input

## Searchable Fields

Products can be searched by these fields:
- `name`
- `description`
- `short_description`
- `sku`
- `barcode`
- `brand`

## Business Logic & Features

### Automatic Slug Generation
- If slug is not provided, it's auto-generated from the product name
- Ensures uniqueness by appending numbers if needed
- Uses Laravel's `Str::slug()` for URL-safe slugs

### Stock Management
- `stock_track` determines if stock is tracked
- `isInStock()` method checks availability
- `isLowStock()` method checks if below threshold
- Stock scopes available: `tracked()`, `inStock()`, `lowStock()`

### Pricing Calculations
- `getEffectivePrice()` returns sale price or regular price
- `getDiscountPercentage()` calculates discount from regular to sale price
- Supports cost, MRP, regular price, and sale price

### Virtual Attributes
- `gst` - Combines HSN code, tax rate, and inclusive flag
- `stock` - Combines tracking, quantity, and threshold
- `dimensions` - Combines length, width, height, weight, and unit
- `shipping` - Combines shipping-related fields

### Scopes
- `active()` - Only active products
- `published()` - Only published products
- `ofType($type)` - Products of specific type
- `tracked()` - Products with stock tracking
- `inStock()` - Products with available stock
- `lowStock()` - Products below threshold

## Validation Rules

### Create Product (POST)
```json
{
  "name": "required|string|max:255",
  "slug": "string|max:255|unique:products",
  "type": "in:simple,variable,grouped,external",
  "publication_status": "in:draft,published,discontinued,private",
  "sku": "string|max:100|unique:products",
  "price": "required|numeric|min:0",
  "cost": "numeric|min:0",
  "mrp": "numeric|min:0",
  "sale_price": "numeric|min:0",
  "tax_rate": "numeric|min:0|max:100",
  "stock_quantity": "integer|min:0",
  "stock_low_threshold": "integer|min:0"
}
```

### Update Product (PUT/PATCH)
Similar to create but with unique validation exclusions for the current product.

## Example Requests

### Create Product
```bash
curl -X POST "https://api.example.com/api/v1/products" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Premium Widget",
    "type": "simple",
    "publication_status": "published",
    "price": 299.99,
    "cost": 150.00,
    "mrp": 349.99,
    "sku": "PW001",
    "brand": "ASENSAR",
    "taxable": true,
    "tax_rate": 18.00,
    "stock_track": true,
    "stock_quantity": 100,
    "active": true
  }'
```

### Search Products
```bash
curl -X GET "https://api.example.com/api/v1/products?search=widget&filter=type:simple&sort=price&dir=asc" \
  -H "Authorization: Bearer {token}"
```

### Filter by Stock Status
```bash
curl -X GET "https://api.example.com/api/v1/products?filter=stock_track:true" \
  -H "Authorization: Bearer {token}"
```

## Response Examples

### Single Product Response
```json
{
  "success": true,
  "message": "Product retrieved successfully",
  "data": {
    "id": 1,
    "name": "Premium Widget",
    "slug": "premium-widget",
    "type": "simple",
    "publication_status": "published",
    "active": true,
    "sku": "PW001",
    "brand": "ASENSAR",
    "cost": "150.00",
    "mrp": "349.99",
    "price": "299.99",
    "sale_price": "0.00",
    "taxable": true,
    "tax_hsn_code": null,
    "tax_rate": "18.00",
    "tax_inclusive": true,
    "stock_track": true,
    "stock_quantity": 100,
    "stock_low_threshold": 0,
    "unit": "nos",
    "gst": {
      "hsn_code": null,
      "gst_rate": "18.00",
      "inclusive": true
    },
    "stock": {
      "track": true,
      "quantity": 100,
      "low_threshold": 0
    },
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

## Error Handling

Common error scenarios:

- **422 Validation Error**: Invalid data format or constraint violations
- **404 Not Found**: Product ID doesn't exist
- **409 Conflict**: Unique constraint violations (slug, SKU)
- **401 Unauthorized**: Invalid or missing authentication token

For detailed error response format, see [../error.md](../error.md).