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

1. **Design**: Add or update an endpoint/field in the relevant API design file (e.g., `design/api/index.md`, `design/api/store.md`). All implementations must align with the design documentation in `design/api/`.
2. **Test**: Write a failing test in `backend/tests/Feature/` for the new behavior/response.
3. **Implement**: Write the minimum code to make the test pass, delegating business logic to service classes. Update controllers, requests, etc., as needed.
4. **Run the Test Again**: Verify the test passes after implementation.
5. **Refactor**: Clean up code, keeping tests green and ensuring code is clean and adheres to backend constraints (`ApiResourceServiceProvider`, `ApiResponseTrait`, `ApiResourceController`).
6. **Review**: Confirm both design and tests are up to date.
7. **Repeat**: Continue the TDD cycle for each new feature or change.

---

## Core Constraints & Rules

- All backend code must reside in the `backend` directory.
- All API responses must use the standardized format defined in `design/api/` and be returned via `ApiResponseTrait`.
- All endpoints must be protected by the `auth:sanctum` middleware.
- Eloquent models with the `ApiResource` attribute are auto-registered as RESTful resources under `/api/v1/{resource}` via `ApiResourceServiceProvider`.
- Only the `ApiResourceController` is used for resource endpoints, containing ONLY the 5 basic CRUD methods: `index`, `show`, `store`, `update`, `destroy`.
- All query, validation, and business logic must be delegated to dedicated service classes (e.g., `Resource*Service`). No direct database queries are allowed in controllers.
- All requests must be validated using `StoreResourceRequest` and `UpdateResourceRequest`.
- All endpoints and changes must be described in `/design/api/` documentation (e.g., `index.md`, `store.md`).
- Feature tests in `tests/Feature/` must be written/updated before implementation.
- Never expose internal errors or stack traces to the frontend. All errors must be logged, and user-facing messages must be friendly.
- Ensure proper authentication, authorization, and protection against SQL injection, XSS, and other vulnerabilities.
- Monitor API response times and optimize where necessary.
- Confirm proper loading states, error messages, and form validation in the frontend.

---

## Backend Anti-Patterns

❌ Inconsistent API response formats
❌ Exposing internal errors to the frontend
❌ Missing request validation
❌ Direct database queries in controllers
❌ Hardcoded values instead of configuration
❌ Missing error logging
