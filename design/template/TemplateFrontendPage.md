# Template for Frontend Page Design Specification

This template serves as a guideline for documenting the design specifications of frontend pages in the project. Follow this structure to ensure consistency and clarity.

**File Location:** `frontend/app/pages/<page-name>.vue`

**Page Structure**

```html
<template>
  <!-- Include the main component or layout here -->
  <MainComponent />
</template>
```

Provide a brief description of the page's purpose and functionality. Mention any key components or layouts used and their roles in the page.

## Constraints to Follow for Documenting

- Always use absolute file paths from the repository base.
- Always use unordered lists for consistency.
- **Test-Driven Development (TDD)**: Follow the [BOS Frontend Rules](design/rules-app.md#test-driven-development-tdd) for detailed guidelines and requirements.
- **Centralized State Management**: Use the Pinia store (`frontend/app/stores/storeResource.ts`) to manage resource list state, including data, columns, loading, error, sort, filters, and pagination.
- **API Integration**: All HTTP requests **must** use the shared API service (`frontend/app/utils/api.ts`). This ensures consistent authentication and error handling. **Never** use direct `$fetch`, `fetch`, or other HTTP clients.
- **Error Handling and Notifications**: Use the shared notification service (`frontend/app/utils/notify.ts`) for all error handling and notifications.
- **URL State Management**: Synchronize child component states with the URL to support browser navigation and allow bookmarkable states.

## References

- [Frontend Rules](design/rules-app.md)
- [GitHub Copilot Instructions](.github/copilot-instructions.md)