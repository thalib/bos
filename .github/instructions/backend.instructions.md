---
description: 'Laravel 12 backend coding conventions and guidelines'
applyTo: 'backend/**/*.{php,blade.php,json,xml,yml,env,md}'
---

# Laravel 12 & PHP Backend Best Practices

## General Principles
- Follow [Laravel 12 Documentation](https://laravel.com/docs/12.x/) and [Contribution Guide](https://laravel.com/docs/12.x/contributions)
- Adhere to [PSR-12](https://www.php-fig.org/psr/psr-12/) and [PSR-4](https://www.php-fig.org/psr/psr-4/) standards
- Use strict types and type-hinting everywhere possible
- Use PHP 8.2+ features (readonly, enums, attributes, etc.)
- Use dependency injection for all services and repositories
- Use Eloquent ORM for data access, prefer query scopes and resource controllers
- Use API Resource Controllers (`Route::apiResource`) for REST endpoints
- All endpoints must be stateless, authenticated (e.g., Sanctum), and return JSON
- Use Form Requests for validation
- Use Resource classes for API responses
- Use Service classes for business logic
- Use Repository pattern for data access abstraction (where appropriate)
- Use custom exceptions for error handling
- Use middleware for cross-cutting concerns (auth, logging, rate limiting)
- Use environment variables for configuration

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

## API-First REST Standards
- Use RESTful resource naming and HTTP verbs
- Use plural resource names in URIs (e.g., `/users`)
- Use consistent versioning (e.g., `/api/v1/...`)
- Return proper HTTP status codes
- Use pagination, filtering, and sorting for collections
- Document all endpoints (OpenAPI/Swagger recommended)

## Testing
- Use Pest or PHPUnit for all tests
- Write feature tests for all endpoints in `tests/Feature/`
- Write unit tests for business logic in `tests/Unit/`
- Use factories and seeders for test data
- Aim for high code coverage and logical coverage

## Code Style & Quality
- Use PHPDoc for all public methods and classes
- Use Laravel Pint for code formatting
- Use static analysis tools (PHPStan, Psalm)
- Use meaningful variable and method names
- Keep classes and methods small and focused (SRP)
- Avoid business logic in controllers and models

## Security
- Validate all input using Form Requests
- Use Laravel's built-in authentication and authorization
- Protect against SQL injection, XSS, CSRF, and other common vulnerabilities
- Never commit secrets or credentials to source control

## References
- [Laravel 12 Documentation](https://laravel.com/docs/12.x/)
- [PSR-12: Extended Coding Style Guide](https://www.php-fig.org/psr/psr-12/)
- [PSR-4: Autoloader](https://www.php-fig.org/psr/psr-4/)

