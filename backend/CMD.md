# Laravel Dynamic API Resources - Test Commands

## Prerequisites

Before running these commands, make sure:

1. **Laravel server is running**:
```powershell
cd backend
php artisan serve
```

2. **Database is migrated**:
```powershell
php artisan migrate
```

3. **Seeders are run** (optional):
```powershell
php artisan db:seed
```

# Run all seeders
php artisan db:seed

# Run only the product seeder
php artisan db:seed --class=ProductSeeder

# Fresh migration with seeders
php artisan migrate:fresh --seed

## User Resource Endpoints

### 1. Get User Schema
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/users/schema" -H "Content-Type: application/json"
```

### 2. List All Users (No Pagination)
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/users" -H "Content-Type: application/json"
```

### 3. List Users with Pagination
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/users?page=1&per_page=5" -H "Content-Type: application/json"
```

### 4. Create New User
```powershell
curl -X POST "http://127.0.0.1:8000/api/v1/users" `
  -H "Content-Type: application/json" `
  -d '{
    "name": "John Doe",
    "username": "johndoe123",
    "email": "john.doe@example.com",
    "whatsapp": "1234567890123",
    "password": "password123"
  }'
```

### 5. Get Specific User
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/users/1" -H "Content-Type: application/json"
```

### 6. Update User (PUT)
```powershell
curl -X PUT "http://127.0.0.1:8000/api/v1/users/1" `
  -H "Content-Type: application/json" `
  -d '{
    "name": "John Smith Updated",
    "email": "john.smith.updated@example.com"
  }'
```

### 7. Update User (PATCH)
```powershell
curl -X PATCH "http://127.0.0.1:8000/api/v1/users/1" `
  -H "Content-Type: application/json" `
  -d '{
    "name": "John Smith Patched"
  }'
```

### 8. Delete User
```powershell
curl -X DELETE "http://127.0.0.1:8000/api/v1/users/1" -H "Content-Type: application/json"
```

## Product Resource Endpoints

### 1. Get Product Schema
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/products/schema" -H "Content-Type: application/json"
```

### 2. List All Products
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/products" -H "Content-Type: application/json"
```

### 3. List Products with Pagination
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/products?page=1&per_page=10" -H "Content-Type: application/json"
```

### 4. Create New Product
```powershell
curl -X POST "http://127.0.0.1:8000/api/v1/products" `
  -H "Content-Type: application/json" `
  -d '{
    "name": "Test Product",
    "slug": "test-product",
    "type": "simple",
    "status": "draft",
    "brand": "TestBrand",
    "price": 29.99,
    "sale_price": 24.99,
    "cost": 15.00,
    "stock_quantity": 100,
    "weight": 1.5,
    "length": 10.0,
    "width": 5.0,
    "height": 3.0
  }'
```

### 5. Get Specific Product
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/products/1" -H "Content-Type: application/json"
```

### 6. Update Product (PUT)
```powershell
curl -X PUT "http://127.0.0.1:8000/api/v1/products/1" `
  -H "Content-Type: application/json" `
  -d '{
    "name": "Updated Test Product",
    "price": 35.99,
    "sale_price": 29.99,
    "stock_quantity": 75
  }'
```

### 7. Update Product (PATCH)
```powershell
curl -X PATCH "http://127.0.0.1:8000/api/v1/products/1" `
  -H "Content-Type: application/json" `
  -d '{
    "price": 39.99
  }'
```

### 8. Delete Product
```powershell
curl -X DELETE "http://127.0.0.1:8000/api/v1/products/1" -H "Content-Type: application/json"
```

## Error Testing Commands

### 1. Test Invalid Model
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/invalidmodel" -H "Content-Type: application/json"
```

### 2. Test Non-Existent Resource
```powershell
curl -X GET "http://127.0.0.1:8000/api/v1/users/99999" -H "Content-Type: application/json"
```

### 3. Test Invalid Data Creation
```powershell
curl -X POST "http://127.0.0.1:8000/api/v1/users" `
  -H "Content-Type: application/json" `
  -d '{
    "name": "",
    "email": "invalid-email",
    "password": "123"
  }'
```

## Batch Testing Script

Create a PowerShell script `test-api.ps1` to run all tests:

```powershell
# Save this as test-api.ps1
Write-Host "Testing Laravel Dynamic API Resources..." -ForegroundColor Green

Write-Host "`n=== User Schema ===" -ForegroundColor Yellow
curl -X GET "http://127.0.0.1:8000/api/v1/users/schema" -H "Content-Type: application/json"

Write-Host "`n=== Product Schema ===" -ForegroundColor Yellow  
curl -X GET "http://127.0.0.1:8000/api/v1/products/schema" -H "Content-Type: application/json"

Write-Host "`n=== List Users ===" -ForegroundColor Yellow
curl -X GET "http://127.0.0.1:8000/api/v1/users" -H "Content-Type: application/json"

Write-Host "`n=== List Products ===" -ForegroundColor Yellow
curl -X GET "http://127.0.0.1:8000/api/v1/products" -H "Content-Type: application/json"

Write-Host "`n=== Create User ===" -ForegroundColor Yellow
curl -X POST "http://127.0.0.1:8000/api/v1/users" -H "Content-Type: application/json" -d '{"name":"Test User","username":"testuser","email":"test@example.com","whatsapp":"1234567890","password":"password123"}'

Write-Host "`nAPI Testing Complete!" -ForegroundColor Green
```

Run the batch script:
```powershell
.\test-api.ps1
```

## Expected Responses

### Schema Response Example
```json
{
  "fields": {
    "name": {
      "type": "text",
      "label": "Full Name",
      "placeholder": "Enter your full name",
      "required": true,
      "maxLength": 255
    },
    "email": {
      "type": "email",
      "label": "Email Address",
      "placeholder": "Enter your email address",
      "required": true,
      "maxLength": 255,
      "unique": true
    },
    "price": {
      "type": "decimal",
      "label": "Price",
      "step": "0.01",
      "min": "0",
      "prefix": "$"
    }
  }
}
```

### Pagination Response Example
```json
{
  "current_page": 1,
  "data": [...],
  "first_page_url": "http://127.0.0.1:8000/api/v1/users?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "http://127.0.0.1:8000/api/v1/users?page=3",
  "links": [...],
  "next_page_url": "http://127.0.0.1:8000/api/v1/users?page=2",
  "path": "http://127.0.0.1:8000/api/v1/users",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 45
}
```

### Error Response Example
```json
{
  "error": "Validation failed",
  "message": "The given data was invalid",
  "errors": {
    "email": ["The email field must be a valid email address."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

## Available Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users` | List all users |
| GET | `/api/v1/users?page=1&per_page=5` | List users with pagination |
| POST | `/api/v1/users` | Create new user |
| GET | `/api/v1/users/{id}` | Get specific user |
| PUT | `/api/v1/users/{id}` | Update user (full) |
| PATCH | `/api/v1/users/{id}` | Update user (partial) |
| DELETE | `/api/v1/users/{id}` | Delete user |
| GET | `/api/v1/users/schema` | Get user form schema |
| GET | `/api/v1/products` | List all products |
| GET | `/api/v1/products?page=1&per_page=10` | List products with pagination |
| POST | `/api/v1/products` | Create new product |
| GET | `/api/v1/products/{id}` | Get specific product |
| PUT | `/api/v1/products/{id}` | Update product (full) |
| PATCH | `/api/v1/products/{id}` | Update product (partial) |
| DELETE | `/api/v1/products/{id}` | Delete product |
| GET | `/api/v1/products/schema` | Get product form schema |
