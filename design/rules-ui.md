# Frontend Constraints (Nuxt 4)

## Overview

- **Use the `api.ts` service** for all HTTP requests and operations. **DO NOT USE** direct `$fetch`, `fetch`, or other HTTP clients. The service handles authentication headers and error handling automatically.
- **Follow Bootstrap First**: Use Bootstrap 5.3 classes for layout, components, and utilities. Custom CSS should only be used when necessary. Examples include:

  - Layout: `container`, `row`, `col-*`, `d-flex`, `justify-content-*`, `align-items-*`.
  - Components: `btn`, `card`, `form-control`, `table`, `dropdown`.
  - Utilities: `text-*`, `bg-*`, `p-*`, `m-*`, `border-*`, `rounded-*`.

- **Implement Form Validation**: Use reactive patterns with real-time feedback and clear error messages. Leverage libraries like `vee-validate` or `yup` for schema-based validation.

- **Handle Errors Gracefully**: Use the Notification Service for user feedback and error handling. Ensure fallback UI is provided for unexpected errors.

- **Use TypeScript Strictly**: Define interfaces, avoid `any`, and follow Composition API patterns like `ref`, `reactive`, `computed`, and `watch`. Use tools like `tsc` for type checking.

- **Provide Loading States**: Ensure consistent loading indicators for all async operations and manage states properly. Use shared components for spinners or skeleton loaders.

- **Reference Services**: Use the following services for specific operations:

  - [API Service](design/ui/services/Api.md) for API operations.
  - [Authentication Service](design/ui/services/Auth.md) for session management.
  - [Notification Service](design/ui/services/Notification.md) for notifications.

- **Adopt Industry Best Practices**: Ensure accessibility (ARIA roles, keyboard navigation), optimize performance (lazy loading, code splitting, caching), sanitize user inputs, and use HTTPS for API calls. Use tools like Lighthouse for performance and accessibility audits.

- **Align with Nuxt 4 Directory Structure**: Organize your project files under the new `app/` directory structure for better alignment with Nuxt 4. For example:

  ```plaintext
  my-nuxt-app/
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

- **Leverage Auto-Imports**: Nuxt 4 auto-imports components, composables, and utilities. Avoid manual imports unless explicitly required. Use the `#imports` alias for explicit imports when needed.

- **Testing**: Follow Nuxt 4 testing guidelines. Use `@nuxt/test-utils` for unit and end-to-end testing. Ensure tests are written for all critical components and services.

### Relation

```text
Backend <-> `api.ts` <-> `auth.ts` <-> Frontend Components
```

- `api.ts` (API Service Layer)
- `auth.ts` (Authentication Service)

This diagram illustrates the flow of interactions between the backend, `api.ts`, `auth.ts`, and frontend components.

## Frontend Anti-Patterns

❌ Using direct fetch/HTTP calls instead of shared API service: Leads to inconsistent error handling and authentication issues.  
❌ Excessive custom CSS when Bootstrap classes exist: Reduces maintainability and consistency.  
❌ Missing error handling in components: Results in poor user experience during failures.  
❌ Using `any` type without justification: Weakens type safety and increases debugging effort.  
❌ Missing loading states for async operations: Causes confusion for users during delays.  
❌ Ignoring accessibility standards (e.g., ARIA roles, keyboard navigation): Excludes users with disabilities and violates compliance standards.  
❌ Poor performance practices (e.g., no lazy loading, excessive re-renders): Leads to slow page loads and high resource usage.
