# BOS Frontend Rules (Nuxt 4)

> **This document is the canonical source for all frontend development rules for the BOS project. All contributors and AI coding agents must follow these rules strictly.**

---

## Test-Driven Development (TDD) — Mandatory

All frontend code **must** be developed using TDD: no code should be written without a failing test first. Follow Nuxt 4 testing guidelines and use `@nuxt/test-utils` for unit and end-to-end testing. Write tests for all critical components and services. The TDD workflow is:

1. **Write a Test First**: Define the expected behavior in a test file (`frontend/tests/`).
2. **Run the Test**: Confirm it fails, validating the test and indicating the feature is not yet implemented.
3. **Write the Minimum Code**: Implement just enough code to make the test pass, following all rules in this document.
4. **Run the Test Again**: Verify the test passes after implementation.
5. **Refactor the Code**: Ensure the code is clean, modular, and maintains passing tests.
6. **Repeat for Additional Features**: Continue the TDD cycle for each new feature or change.

---

## Directory Structure — Strict Requirement

All application code **must** reside in `frontend/app/` following the Nuxt 4 directory structure. This is required for compatibility and maintainability. Example:

```plaintext
frontend/
├─ app/
│  ├─ assets/
│  ├─ components/
│  ├─ composables/
│  ├─ layouts/
│  ├─ middleware/
│  ├─ pages/
│  ├─ plugins/
│  ├─ utils/
│  ├─ app.vue
│  ├─ app.config.ts
│  └─ error.vue
├─ content/
├─ public/
├─ shared/
├─ server/
└─ nuxt.config.ts
```

---

## Constraints

### API Usage & Service Flow

- All HTTP requests **must** use the shared API service (`frontend/app/utils/api.ts`).
- **Never** use direct `$fetch`, `fetch`, or other HTTP clients—this ensures consistent authentication and error handling.

**Service Interaction Flow:**

```text
Backend <-> api.ts <-> auth.ts <-> Frontend Components
```

- [`api.ts`](design/app/services/Api.md): API Service Layer — handles all API communication and operations
- [`auth.ts`](design/app/services/Auth.md): Authentication Service — manages session/auth state

### Rules

- **Bootstrap First**: Use Bootstrap 5.3 classes for layout, components, and utilities. Custom CSS is only allowed when absolutely necessary.
- **Form Validation**: Use reactive patterns with real-time feedback and clear error messages. Use libraries like `vee-validate` or `yup` for schema-based validation.
- **Error Handling & Notifications**: Use the [Notify Service](design/app/services/Notify.md) (`frontend/app/utils/notify.ts`) for all user feedback, error handling, and notifications—including success messages, warnings, and error alerts. Always provide fallback UI for unexpected errors to ensure a seamless user experience and maintain consistency across the application.
- **TypeScript**: Use strict typing. Define interfaces, avoid `any`, and follow Composition API patterns (`ref`, `reactive`, `computed`, `watch`). Use `tsc` for type checking.
- **Loading States**: Always provide consistent loading indicators for all async operations. Use shared components for spinners or skeleton loaders.
- **Best Practices**: Ensure accessibility (ARIA roles, keyboard navigation), optimize performance (lazy loading, code splitting, caching), sanitize user inputs, and use HTTPS for API calls. Use Lighthouse for audits.
- **Auto-Imports**: Nuxt 4 auto-imports components, composables, and utilities. Avoid manual imports unless explicitly required. Use the `#imports` alias for explicit imports when needed.

## Frontend Anti-Patterns

❌ Using direct fetch/HTTP calls instead of shared API service: inconsistent error handling and authentication issues.
❌ Excessive custom CSS when Bootstrap classes exist: reduces maintainability and consistency.
❌ Missing error handling in components: poor user experience during failures.
❌ Using `any` type without justification: weakens type safety and increases debugging effort.
❌ Missing loading states for async operations: confuses users during delays.
❌ Ignoring accessibility standards (e.g., ARIA roles, keyboard navigation): excludes users with disabilities and violates compliance standards.
❌ Poor performance practices (e.g., no lazy loading, excessive re-renders): slow page loads and high resource usage.
