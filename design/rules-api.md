# Backend API Rules for BOS Project

This document outlines the rules and constraints for implementing backend APIs in the BOS project. These rules ensure consistency, maintainability, and adherence to best practices.

## Key Rules

- **API Resource Auto-Registration**: Eloquent models with the `ApiResource` attribute are auto-registered as RESTful resources under `/api/v1/{resource}` via `ApiResourceServiceProvider`.
- **Authentication**: All API endpoints are protected by the `auth:sanctum` middleware.
- **Controller Usage**: Only the `ApiResourceController` is used for resource endpoints, containing ONLY the 5 basic CRUD methods: `index`, `show`, `store`, `update`, `destroy`.
- **Service Classes**: All query, validation, and business logic must be delegated to dedicated service classes (e.g., `Resource*Service`). No direct database queries are allowed in controllers.
- **Validation**: All requests must be validated using `StoreResourceRequest` and `UpdateResourceRequest`.
- **Standardized Responses**: All responses must use the structure provided by `ApiResponseTrait`. For detailed response structure, refer to `/design/api/index.md` and `/design/api/error.md`.
- **TDD and DDD Principles**: API design and implementation must follow TDD and DDD principles. All endpoints and changes must be described in `/design/api/` documentation (e.g., `index.md`, `store.md`).
- **Feature Tests**: Feature tests in `tests/Feature/` must be written/updated before implementation.
- **Error Handling**: Never expose internal errors or stack traces to the frontend. All errors must be logged, and user-facing messages must be friendly.
- **Security**: Verify proper implementation of authentication and authorization. Protect against SQL injection, XSS attacks, and other vulnerabilities.
- **Performance**: Monitor API response times and optimize where necessary.
- **Frontend Integration**: Confirm proper loading states, error messages, and form validation in the frontend.

## Backend Workflow

1. **Design**: Add or update an endpoint/field in the relevant API design file (e.g., `design/api/index.md`, `design/api/store.md`).
2. **Test**: Write a failing test in `tests/Feature/` for the new behavior/response.
3. **Implement**: Update controllers, requests, etc., to make the test pass and match the design.
4. **Refactor**: Clean up code, keeping tests green.
5. **Review**: Confirm both design and tests are up to date.

## Best Practices

- Use the `RefreshDatabase` trait for isolated testing.
- Log errors and provide user-friendly error messages.
- Ensure transaction safety and data consistency.

## Anti-Patterns to Avoid

- ❌ Inconsistent API response formats.
- ❌ Exposing internal errors to the frontend.
- ❌ Missing request validation.
- ❌ Direct database queries in controllers.
- ❌ Hardcoded values instead of configuration.
- ❌ Missing error logging.
