
# BOS Project — AI Coding Agent Instructions

## Big Picture Architecture
- **Backend**: Laravel 12, API-only, auto-generates REST endpoints for Eloquent models with `#[ApiResource]` (see `design/rules-api.md`). All business logic is in service classes; controllers are thin and only use 5 CRUD methods. API responses are always in the format defined in `design/backend/endpoints/error.md`.
- **Frontend**: Nuxt 4 (Vue 3, TypeScript, Bootstrap 5.3). All HTTP requests go through `frontend/app/utils/api.ts` (never use direct fetch). Notifications and error handling are centralized in `frontend/app/utils/notify.ts`.
- **API Contract**: The single source of truth for endpoints, request/response formats, and error handling is the design docs in `design/backend/endpoints/` and `design/backend/resources/`. All changes must be reflected in these docs before implementation.

## Critical Developer Workflows
- **TDD/DDD Required**: Write or update tests (backend: `backend/tests/Feature/`, frontend: `frontend/tests/`) before implementing code. No code without a failing test first.
- **Backend**: Use `php artisan serve` to run, `php artisan test` for tests. All endpoints must be protected by `auth:sanctum` and return JSON. Use only the `ApiResourceController` for resources; never add business logic to controllers.
- **Frontend**: Use `npm run dev` (or `build`) in `frontend/`. All API calls must use the shared API service. Use strict TypeScript and Nuxt 4 auto-imports for components/composables.

## Project-Specific Conventions
- **Backend**:
  - All endpoints and models must be documented in `design/backend/resources/` and `design/backend/endpoints/`.
  - Only service classes may contain business logic. Controllers must delegate.
  - Use `StoreResourceRequest`/`UpdateResourceRequest` for validation, with rules defined in the model's `getApiSchema()`.
  - Never expose stack traces or internal errors to the frontend.
- **Frontend**:
  - All HTTP and auth logic is centralized in `frontend/app/utils/api.ts` and `frontend/app/utils/auth.ts`.
  - Use the Notify service for all user feedback (success, error, warning, info).
  - Use Bootstrap classes for layout and components; custom CSS only if necessary.
  - Use Nuxt 4 auto-imports for components, composables, and utils.

## Integration & Data Flow
- **API**: All endpoints are versioned under `/api/v1/`. Auth endpoints: `/api/v1/auth/*`. Resource endpoints: `/api/v1/{resource}`. See `design/backend/README.md` for quick reference.
- **Frontend-Backend Contract**: Request/response formats, error handling, and pagination must match the design docs. See `frontend/app/utils/api.ts` for how responses and errors are handled.
- **Testing**: Backend uses Pest/PHPUnit; frontend uses Nuxt test-utils. Tests must mirror real-world usage and cover all endpoints/components.

## Examples
- To add a new resource: Document in `design/backend/resources/`, update tests in `backend/tests/Feature/`, then implement model/service/controller.
- To add a new frontend feature: Write a test in `frontend/tests/`, update API contract in design docs if needed, then implement using the shared API and Notify services.

## Key References
- Backend rules: `design/rules-api.md`
- Frontend rules: `design/rules-app.md`
- API docs: `design/backend/endpoints/`, `design/backend/resources/`
- Shared API/Notify: `frontend/app/utils/api.ts`, `frontend/app/utils/notify.ts`

---
If any section is unclear or incomplete, please provide feedback for further refinement.

---

## Required Before Each Commit
- All new or changed endpoints must be documented in `design/backend/resources/` and `design/backend/endpoints/` before code is merged.
- All code changes must have corresponding tests (backend: `backend/tests/Feature/`, frontend: `frontend/tests/`).
- Run all tests and ensure they pass: `php artisan test` (backend), `npm run test:run` (frontend).
- Lint and format code: use Laravel Pint for backend, ESLint/Prettier for frontend.
- Update API contract docs if request/response formats change.

## Development Flow
1. **Design**: Update or add documentation in `design/backend/resources/` and `design/backend/endpoints/` for any new feature or change.
2. **Test**: Write a failing test in the appropriate test directory.
3. **Implement**: Write the minimum code to pass the test, following all conventions and using service/controller separation.
4. **Refactor**: Clean up code, keeping tests green and code style consistent.
5. **Review**: Ensure docs, tests, and implementation are all in sync before merging.

## Repository Structure
```
bos/
├── backend/         # Laravel 12 API backend (app, tests, routes, services, etc.)
├── frontend/        # Nuxt 4 frontend (app, utils, tests, etc.)
├── design/          # API contracts, rules, and documentation (see design/README.md)
│   ├── backend/     # Backend endpoint/resource docs
│   ├── frontend/    # Frontend design docs
│   └── shared/      # Shared templates, best practices
└── .github/         # Project instructions, workflows, and agent rules
```

## Key Guidelines
- **Backend**: Only use service classes for business logic. Controllers must remain thin and only use the 5 CRUD methods. All endpoints must be documented and tested before implementation.
- **Frontend**: All HTTP requests must go through `frontend/app/utils/api.ts`. Use the Notify service for all user feedback. Use Nuxt 4 auto-imports and Bootstrap classes for UI. Write tests before code.
- **API Contract**: The design docs in `design/backend/endpoints/` and `design/backend/resources/` are the single source of truth for all endpoints, request/response formats, and error handling. Keep them up to date.
- **Testing**: TDD is mandatory. No code is merged without a failing test first and all tests passing after implementation.
- **Documentation**: All changes must be reflected in the design docs before code is merged. Examples and error handling must be included.



