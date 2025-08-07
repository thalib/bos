# BOS Backend API Rules (Laravel 12)

> **This document is the canonical source for all backend development rules for the BOS project. All contributors and AI coding agents must follow these rules strictly.**

---

## AI Coding Agent Instructions

> **MANDATORY:**
>
> - You MUST always follow the TDD workflow below for any code change or feature.
> - NEVER write or modify code without a failing test first.
> - ALWAYS reference the design documentation in `design/api/` before implementation.
> - All code must be committed with passing tests and must not bypass any workflow step.

---

## Backend Workflow (TDD & DDD) — The Only Allowed Development Process

1. **Design**: Add or update an endpoint/field in the relevant API design file (e.g., `design/api/resources/users.md`, `design/api/resources/products.md`). All implementations must align with the design documentation in `design/api/`.
2. **Test**: Write a failing test in `backend/tests/Feature/` for the new behavior/response.
3. **Implement**: Write the minimum code to make the test pass, delegating business logic to service classes. Update controllers, requests, etc., as needed.
4. **Run the Test Again**: Verify the test passes after implementation.
5. **Refactor**: Clean up code, keeping tests green and ensuring code adheres to backend constraints (`ApiResourceServiceProvider`, `ApiResponseTrait`, `ApiResourceController`).
6. **Review**: Confirm both design and tests are up to date.
7. **Repeat**: Continue the TDD cycle for each new feature or change.

---

## Core Architecture & Constraints

### Auto-Generated Resources
- Eloquent models with the `#[ApiResource]` attribute are auto-registered as RESTful resources under `/api/v1/{resource}` via `ApiResourceServiceProvider`
- Current auto-generated resources: `users`, `products`, `estimates`, `test-models`
- Each resource automatically supports full CRUD operations

### Controller Architecture
- Only the `ApiResourceController` is used for resource endpoints, containing ONLY the 5 basic CRUD methods: `index`, `show`, `store`, `update`, `destroy`
- All query, validation, and business logic MUST be delegated to dedicated service classes
- Controllers must remain under 200 lines total and contain no business logic

### Service Layer
All business logic is handled by dedicated service classes:
- **ResourceSearchService** - Search functionality across models
- **ResourceFilterService** - Dynamic filtering based on model properties  
- **ResourcePaginationService** - Pagination logic and metadata
- **ResourceSortingService** - Multi-column sorting with direction control
- **ResourceMetadataService** - Schema and column definitions for UI generation

### API Response Format
- All API responses must use the standardized format defined in `design/api/` and be returned via `ApiResponseTrait`
- Success responses include: `success`, `message`, `data`, optional `pagination`, `schema`, `columns`
- Error responses include: `success`, `message`, `error` with `code` and `details`

### Authentication & Security
- All endpoints must be protected by the `auth:sanctum` middleware
- Authentication handled by dedicated `AuthController` with endpoints: login, register, logout, refresh, status
- User permissions are validated before any CRUD operations

### Request Validation
- All requests must be validated using `StoreResourceRequest` and `UpdateResourceRequest`
- Validation rules defined per resource in model's `getApiSchema()` method
- Database constraints must match validation rules

### Database Constraints
- Unique constraints enforced at database level and validation level
- Foreign key relationships properly defined
- Indexes created for performance on frequently queried fields

---

## Current Resources & Models

### User Model
- **URI**: `/api/v1/users`
- **Fields**: id, name, username, email, whatsapp, active, role, password, etc.
- **Unique Constraints**: username, email, whatsapp
- **Business Rules**: Auto-deactivate if password is empty, default role 'user'

### Product Model  
- **URI**: `/api/v1/products`
- **Fields**: id, name, slug, type, price, cost, stock, tax info, dimensions, etc.
- **Unique Constraints**: slug, sku
- **Business Rules**: Auto-generate slug, stock tracking, pricing calculations

### Estimate Model
- **URI**: `/api/v1/estimates`  
- **Fields**: id, number, date, customer info, items (JSON), totals, status, etc.
- **Unique Constraints**: number, name
- **Business Rules**: Complex pricing calculations, document generation support

---

## API Endpoints Structure

### Auto-Generated Resource Endpoints
Each `#[ApiResource]` model automatically gets:
- `GET /api/v1/{resource}` - List with pagination, search, filtering, sorting
- `POST /api/v1/{resource}` - Create new resource
- `GET /api/v1/{resource}/{id}` - Show single resource
- `PUT/PATCH /api/v1/{resource}/{id}` - Update resource
- `DELETE /api/v1/{resource}/{id}` - Delete resource

### Authentication Endpoints
- `POST /api/v1/auth/login` - User authentication
- `POST /api/v1/auth/register` - User registration  
- `POST /api/v1/auth/logout` - User logout
- `POST /api/v1/auth/refresh` - Token refresh
- `GET /api/v1/auth/status` - Authentication status

### Application Endpoints
- `GET /api/v1/app/menu` - Application menu structure
- `POST /api/v1/documents/generate-pdf` - PDF document generation
- `POST /api/v1/documents/preview` - Document preview
- `GET /api/v1/documents/templates` - Available document templates

---

## Core Constraints & Rules

- All backend code must reside in the `backend` directory
- All endpoints and changes must be described in `/design/api/` documentation
- Feature tests in `tests/Feature/` must be written/updated before implementation
- Never expose internal errors or stack traces to the frontend. All errors must be logged, and user-facing messages must be friendly
- Ensure proper authentication, authorization, and protection against SQL injection, XSS, and other vulnerabilities
- Monitor API response times and optimize where necessary
- No direct database queries are allowed in controllers - use service classes

---

## Backend Anti-Patterns

❌ Direct database queries in controllers
❌ Business logic in controllers  
❌ Inconsistent API response formats
❌ Exposing internal errors to the frontend
❌ Missing request validation
❌ Hardcoded values instead of configuration
❌ Missing error logging
❌ Bypassing the service layer architecture
❌ Not using the auto-generated resource system
❌ Modifying ApiResourceController beyond the 5 CRUD methods
