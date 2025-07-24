# Pagination Component Design Specification

- The `Pagination` component provides pagination controls for data tables or lists. It receives the complete pagination node from the API response and handles all pagination logic internally.

**File Location:** `frontend/app/components/Resource/Pagination.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Pagination
  :pagination="response.pagination"
  :loading="isLoading"
  @page-change="handlePageChange"
  @per-page-change="handlePerPageChange"
/>
```

- **Props:**
  - `pagination` (object|null): API response's complete pagination node.
  - `loading` (boolean): Indicates if the component is in a loading state.
- **Events:**
  - `page-change`: Triggered when the page is changed. Payload: `{ page: number }`.
  - `per-page-change`: Triggered when the per-page value is changed. Payload: `{ perPage: number }`.

## Child Components (optional)

```txt
Parent
└── Pagination
```

## Features

- Displays current page range and total entries.
- Allows users to navigate between pages.
- Provides per-page selection dropdown (15, 50, 100), default: 15.
- Responsive design for mobile and desktop.
- Handles loading states.

## UI Design

```txt
+-----------------------------------------------+
| [Page Navigation Buttons]                     |
+-----------------------------------------------+
| [Per-Page Selection Dropdown]                 |
+-----------------------------------------------+
```

- Uses Bootstrap 5.3 classes for consistent styling.

## Implementation Rules

- All HTTP requests must use the shared API service (`frontend/app/utils/api.ts`).
- All notifications and error handling must use the Notify Service (`frontend/app/utils/notify.ts`).
- Use Bootstrap 5.3 classes for all layout and UI elements.
- Strictly type all props and logic with TypeScript.
- Provide loading and error states for all async operations.
- Ensure accessibility (ARIA roles, keyboard navigation).
- Write tests first in `frontend/tests/` before implementing features.

## Error Handling

- Displays a user-friendly error message if the `pagination` prop is invalid.
- Provides fallback UI for empty or error states.

## Example Usage (optional)

```html
<Pagination
  :pagination="{ totalItems: 150, currentPage: 2, itemsPerPage: 25 }"
  :loading="false"
  @page-change="handlePageChange"
/>
```
