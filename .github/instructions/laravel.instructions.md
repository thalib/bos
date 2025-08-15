---
description: "Coding Guidelines for Laravel 12 API-first backend development"
applyTo: "backend/**/*.{php,blade.php,json,xml,yml,env,md},design/**/*.md"
---

## Purpose & Scope

Coding Guidelines for Laravel 12 API-first `backend/` development.

## Golden Rules (MANDATORY)

- Keep all application code in `backend/app/`.
- Use PSR-4 namespaces matching `backend/app/` and Composer autoloading; most subdirectories are optional and created via `php artisan make:*`.
- Keep controllers thin: move business logic into models or service classes under `backend/app/`.
- Do not modify `backend/vendor/` directly — use Composer.
- Use PHP 8.1+ native enums (backed enums for DB/string mapping) for role sets, status codes, etc.  
  Reference: Use enums for things like status codes, roles, and ensure proper casting in Eloquent models (`$casts`, attribute casting).
- Use traits for reusable logic and common behaviors in models, controllers, and other classes (e.g., `HasFactory`, `SoftDeletes`, `Notifiable`).
- Avoid hard coding: use config and environment variables for settings (never call `env()` outside config files).
- Adhere to PSR-12 coding standards and enforce code style using [Laravel Pint](https://laravel.com/docs/12.x/pint).

## API Agent Workflow (MANDATORY)

### Pre-change checklist
- Validate all input data.
- Ensure proper API authentication via Sanctum.
- Check for N+1 query issues and optimize with eager loading.
- Confirm endpoint follows RESTful conventions.

### Allowed actions
- Use `php artisan make:` commands for code generation.
- Implement or refactor code using Laravel’s recommended structures.
- Add or update tests for new or changed features.

### Forbidden actions
- Do not bypass Laravel’s ORM with raw queries unless necessary.
- Do not use `env()` in application code outside config files.
- Do not expose sensitive data in API responses.

### Escalation triggers
- Ambiguous requirements or business logic.
- Critical security, performance, or data integrity concerns.
- Unresolvable coding conflicts or framework limitations.

## API Design & Best Practices

- Use RESTful resource controllers for all endpoints.
- Use Eloquent API Resources for response transformation.
- Implement API versioning via route prefixes (`api/v1/...`).
- Use JSON response format for all API endpoints (`application/json`).
- Handle CORS using Laravel’s built-in middleware.
- Implement rate limiting with Laravel's API rate limiter.
- Validate all incoming requests using Form Request classes.
- Use DTOs (Data Transfer Objects) as needed for request/response shaping.
- Use Laravel’s built-in pagination for list endpoints.
- Secure all endpoints with Laravel Sanctum; allow only authenticated access unless public.
- Sanitize and validate all input data.

## Error Handling & Contracts

- Standardize API error responses using RFC 7807 Problem Details format where possible.
- Use custom exceptions for domain-specific errors.
- Log all critical errors using Laravel’s logging facilities.
- Return proper HTTP status codes for all responses.
- Include helpful error messages and validation details in API responses.

## Traits & Enums

- Use traits to share reusable logic across models, controllers, services (e.g., `HasFactory`, `SoftDeletes`, `Notifiable`).
- Use PHP 8.1+ native backed enums for roles, status codes, and type-safe attribute mapping.
- Reference Laravel model attribute casting to map enums to database values.
- Use enums in validation rules and for method parameter typing.

## Service Providers & DI

- Register custom service providers using `php artisan make:provider`.
- Bind interfaces to implementations in service providers.
- Document custom bindings and configuration in the service provider file.
- Use Laravel’s dependency injection container for all services and controllers.

## Development Workflow (TDD-first)

- Short steps: design → failing test → implement → pass test → refactor → doc.
- Use `php artisan make:test` for creating new tests.
- Prefer feature tests for API endpoints; use HTTP testing helpers (`$this->json()`, `$this->getJson()`, etc.).
- For every new feature, add corresponding tests in the `tests/` directory.
- Use model factories for test data generation.
- Maintain proper separation between unit and feature tests.
- Ensure PHPUnit coverage; aim for high coverage on business logic and API endpoints.

## Directory & Naming (brief)

`backend/` - Laravel 12 backend directory structure.

```text
backend/
├── app/
│   ├── Broadcasting/    # optional (make:channel)
│   ├── Console/         # artisan commands (make:command)
│   │   └── Commands/
│   ├── Enums/
│   ├── Events/          # optional (make:event)
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   ├── Requests/    # Form Request validation classes
│   │   ├── Responses/   # Responsable / custom HTTP response classes
│   │   └── Resources/   # optional (API resources)
│   ├── Jobs/            # optional (make:job)
│   ├── Listeners/       # optional (make:listener)
│   ├── Mail/            # optional (make:mail)
│   ├── Models/
│   ├── Notifications/   # optional (make:notification)
│   ├── Policies/        # optional (make:policy)
│   ├── Providers/
│   └── Rules/           # optional (make:rule)
├── bootstrap/
│   ├── app.php
│   ├── providers.php    # project-specific (optional)
│   └── cache/
├── config/              # configuration files
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   ├── web.php
│   ├── api.php         # optional/recommended for APIs
│   ├── console.php
│   └── channels.php    # optional (broadcasting)
├── storage/
│   ├── app/
│   │   └── public/     # run `php artisan storage:link` -> public/storage
│   ├── framework/
│   │   ├── cache/
│   │   ├── sessions/
│   │   ├── testing/
│   │   └── views/
│   └── logs/
├── tests/
│   ├── Feature/
│   └── Unit/
└── vendor/              # Composer-managed (not committed)
```

Notes:

- OPTIONAL: Create a storage symlink for public uploads: `php artisan storage:link` (maps `storage/app/public` → `public/storage`).

## Coding Standards & Artisan Usage

- Use `php artisan make:middleware <MiddlewareName>` for middleware in `app/Http/Middleware/`.
- In Laravel 12, `app/Console/Kernel.php` does not exist; instead, use `bootstrap/app.php` for registering middleware and exception handlers.
- Use `php artisan make:command <CommandName>` for console commands; files in `app/Console/Commands/` are auto-registered.
- Register service providers in `bootstrap/providers.php`. Create with `php artisan make:provider <ProviderName>`.
- When creating models, also generate factories and seeders. Use `list-artisan-commands` to check options for `php artisan make:model`.
- Define model attribute casts using the `$casts` property; prefer `$casts` for consistency but method-based `casts()` is allowed.
- Use `php artisan make:` commands for new files (migrations, controllers, models, etc.).
- For generic PHP classes, use `artisan make:class`.
- Pass `--no-interaction` to Artisan commands for non-interactive execution.
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.
- Use Laravel's built-in authentication/authorization (gates, policies, Sanctum, etc.).
- Prefer named routes and the `route()` function for URL generation.
- If you receive a Vite manifest error: run `npm run build` or advise the user to run `npm run dev`/`composer run dev`.
- Use Form Request classes for validation, not inline controller validation. Include rules and custom error messages.
- Avoid N+1 queries by eager loading relationships; prefer Eloquent relationships and `Model::query()` over raw `DB::` calls unless necessary.
- Document custom service container bindings in the relevant service provider.

## API & Contract

- APIs must be versioned (e.g., `/api/v1/...`).
- Standardize response shapes; always return JSON.
- Use API Resources for transformation.
- Use Laravel’s built-in pagination defaults for list responses.
- Standardize error contract (see Error Handling & Contracts above).

## Testing & Quality Gates

- All PHPUnit and feature/integration tests go in `backend/tests`.
- Use the correct structure for unit vs. feature tests.
- Add tests for every feature.
- Use model factories for test data; check for custom factory states.
- Use Faker methods as per convention.
- Use `php artisan make:test [options] <name>`; pass `--unit` for unit tests. Most tests should be feature tests.
- Use Laravel HTTP testing helpers for API endpoints (`$this->json()`, `$this->postJson()`, `$this->actingAs()`).
- Aim for high code coverage, especially for API and business logic.

## Database

- When modifying a column, migration must include all previous attributes to prevent attribute loss.
- Use Eloquent’s native limiting for eager loads (`$query->latest()->limit(10)`).
- Always use proper relationship methods with return type hints.
- Prefer Eloquent relationships and `Model::query()` over raw queries.
- Prevent N+1 queries with eager loading.
- Use query scopes for reusable query logic.
- Use model events and observers for lifecycle hooks.
- Use Laravel’s query builder for complex operations.

## API Security

- Use Laravel Sanctum for API authentication.
- Rate limit sensitive endpoints using Laravel’s rate limiter.
- Validate and sanitize all input data before storing or processing.
- Never expose sensitive information in API responses.

## Code Style & Quality

- Adhere to PSR-12 coding standards.
- Use Laravel Pint for automated code formatting.
- Maintain clear and consistent naming conventions.
- Document public methods and classes.
