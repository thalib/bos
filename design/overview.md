## Backend Development Approach: Test-Driven & Design-Driven

The backend follows both **Test-Driven Development (TDD)** and **Design-Driven Development (DDD)** to ensure robust, predictable, and maintainable APIs.

### Test-Driven Development (TDD)

- **Purpose:** Guarantees code meets requirements, prevents regressions, and enforces correct behavior.
- **How:**
  - Write or update a test _before_ implementing any feature or endpoint.
  - Use the `tests/Feature/` directory for comprehensive [PHPUnit](https://laravel.com/docs/12.x/testing) feature tests.
  - Tests should:
    - Assert response structure matches the API design (see `assertJsonStructure`).
    - Assert correct status codes, error codes, and messages.
    - Cover edge cases, validation, and error handling.
  - Only implement or update backend logic after the test fails.

### Design-Driven Development (API-First)

- **Purpose:** Ensures implementation matches a clear, agreed-upon API contract.
- **How:**
  - Each API endpoint has a dedicated design document (e.g., `design/api/index.md` for `index()`, `design/api/store.md` for `store()`, etc.) describing its request/response structure, error handling, and parameters.
  - These endpoint-specific files are the single source of truth for all API endpoints, responses, and error handling.
  - Before coding, review or update the relevant API design file to reflect new requirements or changes.
  - All backend code (controllers, requests, resources) must strictly follow these contracts.

### Continuous Enforcement

- **Tests as Specification:**
  - Tests act as a living specification—if implementation drifts from the design, tests will fail.
- **Change Management:**
  - When the API design changes, update both the relevant design doc and the tests before changing implementation.
- **Code Reviews:**
  - Ensure both test coverage and design compliance during reviews.

### Example Workflow

1. **Design:** Add or update an endpoint/field in the relevant API design file (e.g., `design/api/index.md`, `design/api/store.md`).
2. **Test:** Write a failing test in `tests/Feature/` for the new behavior/response.
3. **Implement:** Update controllers, requests, etc., to make the test pass and match the design.
4. **Refactor:** Clean up code, keeping tests green.
5. **Review:** Confirm both design and tests are up to date.

### Summary

- **TDD and DDD are both essential and complementary.**
- The current setup (feature tests + endpoint-specific design docs) is ideal for this approach.
- This leads to robust, predictable, and maintainable APIs.

follow rules in .github/copilot-instructions.md
rollow api documentations /design/api/index.md

for implmenting tests use /api/v1/products
