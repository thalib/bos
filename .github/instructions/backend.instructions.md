---
description: 'Laravel 12 backend coding conventions and guidelines (merged with canonical API rules)'
applyTo: 'backend/**/*.{php,blade.php,json,xml,yml,env,md}'
---

## Laravel 12 & PHP Backend Best Practices

- Follow Laravel 12 docs and PSR standards.
- Use strict types, type-hinting, and PHP 8.2+ features where appropriate.
- Prefer dependency injection for services and repositories.
- Use Eloquent for data access; prefer query scopes and resource controllers.
- Use API Resource Controllers (`Route::apiResource`) for REST endpoints.
- All endpoints must be stateless, authenticated (Sanctum), and return JSON.
- Use Form Requests for validation and Resource classes for API responses.
- Delegate business logic to Service classes; keep controllers thin.
- Use custom exceptions and middleware for cross-cutting concerns.
- Use environment variables for configuration and never commit secrets.

## File & Directory Structure

- Controllers: `app/Http/Controllers/`
- Models: `app/Models/`
- Requests: `app/Http/Requests/`
- Resources: `app/Http/Resources/`
- Services: `app/Services/`
- Repositories: `app/Repositories/`
- Middleware: `app/Http/Middleware/`
- Migrations: `database/migrations/`
- Seeders: `database/seeders/`
- Factories: `database/factories/`
- Tests: `tests/Feature/`, `tests/Unit/`

## AI Coding Agent Instructions (MANDATORY)

- MANDATORY: You MUST always follow the TDD workflow below for any code change or feature.
- NEVER write or modify code without a failing test first.
- ALWAYS reference the design documentation in `design/backend/`, `design/backend/endpoints/`, and `design/backend/resources/` before implementation.
- All code must be committed with passing tests and must not bypass any workflow step.

## Backend Workflow (TDD & DDD) — The Only Allowed Development Process

1. Design: Add or update an endpoint/field in the relevant API design file (e.g., `design/backend/resources/users.md`). Implementations must align with design docs.
2. Test: Write a failing test in `backend/tests/Feature/` (or `tests/Feature/`) for the new behavior/response.
3. Implement: Write minimum code to make the test pass. Delegate business logic to service classes. Controllers remain thin.
4. Run the Test Again: Verify the test passes after implementation.
5. Refactor: Clean up code while keeping tests green; adhere to `ApiResourceServiceProvider`, `ApiResponseTrait`, and `ApiResourceController`.
6. Review: Ensure design docs and tests are up to date.
7. Repeat the TDD cycle for each change.

## Core Architecture & Constraints

### Auto-Generated Resources
- Eloquent models with `#[ApiResource]` are auto-registered as RESTful resources under `/api/v1/{resource}` via `ApiResourceServiceProvider`.
- Current auto-generated resources: `users`, `products`, `estimates`, `test-models`.
- Each resource supports CRUD operations automatically.

### Controller Architecture
- Only the `ApiResourceController` is used for resource endpoints, containing ONLY `index`, `show`, `store`, `update`, `destroy`.
- All query, validation, and business logic MUST be delegated to service classes.
- Controllers must remain under 200 lines and contain no business logic.

### Service Layer
- Business logic must live in service classes:
	- ResourceSearchService — search functionality
	- ResourceFilterService — dynamic filtering
	- ResourcePaginationService — pagination and metadata
	- ResourceSortingService — multi-column sorting
	- ResourceMetadataService — schema and column definitions
- Services must be small, testable, and injected via DI.

## API Response Format & Error Handling

- All API responses must use the standardized format via `ApiResponseTrait`.
- Success response shape: `success`, `message`, `data`, optional `pagination`, `schema`, `columns`.
- Error response shape: `success`, `message`, `error` with `code` and `details`.
- Never expose stack traces or internal errors to the frontend. Log details server-side and return friendly messages.

## Authentication & Security

- All endpoints protected by `auth:sanctum`.
- Authentication handled by a dedicated `AuthController` with endpoints: login, register, logout, refresh, status.
- Validate user permissions before CRUD actions.
- Protect against SQL injection, XSS, CSRF. Validate input via Form Requests.

## Request Validation & Database Constraints

- Use `StoreResourceRequest` and `UpdateResourceRequest` for validation.
- Validation rules should map to `Model::getApiSchema()` when present.
- Enforce unique constraints both at DB and validation levels.
- Define foreign keys and indexes for frequently queried columns.

## Current Resources & Models (summary)

- User (`/api/v1/users`): id, name, username, email, whatsapp, active, role, password; unique: username, email, whatsapp; default role 'user'.
- Product (`/api/v1/products`): id, name, slug, type, price, cost, stock, tax, dimensions; unique: slug, sku; auto-generate slug and maintain stock.
- Estimate (`/api/v1/estimates`): id, number, date, customer info, items JSON, totals, status; unique: number, name; supports document generation.

## API Endpoints Structure

- Auto endpoints per `#[ApiResource]`:
	- GET /api/v1/{resource} — list with pagination, search, filter, sort
	- POST /api/v1/{resource} — create
	- GET /api/v1/{resource}/{id} — show
	- PUT/PATCH /api/v1/{resource}/{id} — update
	- DELETE /api/v1/{resource}/{id} — delete
- Auth endpoints under `/api/v1/auth/*`.
- App endpoints (e.g., `/api/v1/app/menu`) follow design docs.

## Testing

- Use PHPUnit for tests.
- Write feature tests for endpoints in `tests/Feature/`.
- Write unit tests for services in `tests/Unit/`.
- Use factories and seeders for test data.
- TDD required: tests must fail before implementing code and pass after.
- Run `php artisan test` to run backend tests.

## Code Style & Quality

- Use PHPDoc for public methods and classes.
- Use Laravel Pint for formatting.
- Use static analysis (PHPStan, Psalm).
- Use meaningful names and keep classes single-responsibility.
- Avoid business logic in controllers and models.

## Backend Anti-Patterns (Avoid)

- Direct DB queries in controllers
- Business logic in controllers
- Inconsistent API response formats
- Exposing internal errors to frontend
- Missing request validation
- Hardcoded values instead of config
- Missing error logging
- Bypassing the service layer
- Modifying ApiResourceController beyond the 5 CRUD methods

## Integration & Data Flow

- API versioning under `/api/v1/`.
- All HTTP requests from frontend should match design contract; update design docs when changing request/response formats.
- Centralize auth and API response handling in shared services.

## Required Before Each Commit

- Document new/changed endpoints in `design/backend/resources/` and `design/backend/endpoints/`.
- Add/update tests in `backend/tests/Feature/` or `tests/Feature/`.
- Run all tests: `php artisan test`. All must pass.
- Lint/format: Laravel Pint.
- Update design docs if contracts change.

## Development Flow (recap)

1. Design — update design docs.
2. Test — write failing test.
3. Implement — minimal code via service classes.
4. Refactor — keep tests green.
5. Review — ensure docs, tests, implementation are sync'd.

## Key References

- design/rules-api.md
- design/backend/endpoints/
- design/backend/resources/
- frontend/app/utils/api.ts
- frontend/app/utils/notify.ts

## Notes & Edge Cases

- When merging or updating these instructions, preserve mandatory statements verbatim where they enforce security or workflow (e.g., TDD requirement).
- For any ambiguous rule, escalate to project maintainers and prefer stricter validation/security behavior.
- Keep lines reasonably short and use `##`/`###` headings only.

## Changelog

- Merged canonical API rules from `design/rules-api.md` into backend instructions.
- Preserved and emphasized MANDATORY TDD workflow and controller/service constraints.
- Consolidated API response format, authentication rules, and testing requirements.
- Kept file front-matter and `applyTo` directive consistent with existing instruction file.
- Decision rule: where overlaps existed, API-specific rules in `rules-api.md` were preferred for endpoint behavior; general coding style preserved from original backend instructions.

## Requirements coverage

- TDD workflow: Done
- API rules & controller/service constraints: Done
- File/front-matter and applyTo: Done
- Tests & docs requirement: Done
