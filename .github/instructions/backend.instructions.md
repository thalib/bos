---
description: "Laravel 12 backend coding conventions and guidelines for AI agents and contributors"
applyTo: "backend/**/*.{php,blade.php,json,xml,yml,env,md},design/backend/**/*.md"
---

## Purpose & Scope

This file provides mandatory coding conventions, workflow, and guidelines for AI agents and contributors working within the `backend/` directory (Laravel 12 API-only).

## Core Directives (MANDATORY)

- **Test-Driven Development (TDD):** All behavioral changes require a new, failing Feature test in `backend/tests/Feature/`.
- **Service-Controller Pattern:** Business logic MUST reside in services under `backend/app/Services/`. Controllers must remain thin, primarily handling HTTP requests and delegating to services.
- **API Responses:** Use `App\Http\Responses\ApiResponseTrait` for all API responses. Responses must be standardized as per design documents. Never expose raw stack traces.
- **Design Documents:** Read `design/backend/` before starting a task. If a design document is missing or ambiguous, you MUST stop and request clarification from a developer.
- **File Changes:** You may read from `design/` but are forbidden from making any changes.

## Workflow for AI Agents

1.  **Read & Confirm:** Read the relevant design document(s) in `design/backend/` to confirm the scope.
2.  **Write Failing Test:** Create a new Feature test in `backend/tests/Feature/` that fails according to the design specification.
3.  **Implement Code:** Write the minimum code required in `backend/` to make the new test pass. Ensure new services include unit tests.
4.  **Run Quality Gates:** Execute local tests using `php artisan test` and make sure they pass.
5.  **Deliver:** Present the changes (tests + implementation) and a verification summary, including the output from the quality gates.

## Quality & Pre-commit Actions

- **Test Coverage:** All new features must have a passing Feature test. New services must have unit tests.
- **Required Commands:** Before committing, you MUST run `php artisan test` and `./vendor/bin/pint` from the `backend/` root and ensure they pass without errors.

Exact command sequence to run tests (from repository root):
```pwsh
cd backend
php artisan test --stop-on-failure
```

Exact command sequence to run Pint formatting (from repository root):
```pwsh
cd backend
./vendor/bin/pint
```

## Test file naming

- **Test file naming convention:** Name test classes and files in CamelCase using the pattern `Test{FeatureOrFileName}Test` (example: `TestUserRegistrationTest`). Keep names descriptive and aligned with the feature under test.
- **Create test command:** Use Artisan to generate tests: `php artisan make:test {TestName}` (for example: `php artisan make:test TestUserRegistrationTest`).

## Security & Naming

- **Authentication:** All API endpoints must be protected with `auth:sanctum` unless explicitly marked as public in the design documentation.
- **Secrets:** Never commit secrets, credentials, or environment variables. Use `config()` and environment variables (`.env`).
- **Important Paths:**
  - `backend/app/Http/Controllers/`
  - `backend/app/Services/`
  - `backend/tests/Feature/`
  - `design/backend/`
