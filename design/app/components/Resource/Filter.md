# Filter Component Design Specification

- The `Filter` component provides dropdown-based filtering options for data tables or lists. It receives the complete filters node from the API response and handles all filtering logic internally.

**File Location:** `frontend/app/components/Resource/Filter.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Filter
  :filters="response.filters"
  :loading="isLoading"
  @filter-change="handleFilterChange"
  @filter-clear="handleFilterClear"
/>
```

- **Props:**
  - `filters` (object|null): Complete filters node from API response containing `applied` and `available` properties.
  - `loading` (boolean): Indicates if the component is in a loading state.
  - `disabled` (boolean): Whether the filter is disabled.
- **Events:**
  - `filter-change`: Triggered when a filter is selected/changed. Payload: `{ field: string, value: string }`.
  - `filter-clear`: Triggered when filters are cleared. Payload: `{ field: string }`.

## Child Components (optional)

```txt
Parent
└── Filter
```

## Features

- Dropdown menu for filter selection with available options.
- Displays applied filters with clear indicators.
- Handles loading and error states.
- Self-contained filtering logic.
- Responsive design for various screen sizes.

## UI Design

```txt
+-----------------------------------------------+
| [Dropdown Menu for Filters]                   |
+-----------------------------------------------+
| [Applied Filters as Badges]                   |
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

- Displays error state when filters fail to load.
- Shows appropriate fallback UI when no filters are available.
- Provides user-friendly error messages.

## Example Usage (optional)

```html
<Filter
  :filters="{ applied: { field: 'status', value: 'active' }, available: [{ field: 'status', label: 'Status', values: ['active', 'inactive'] }] }"
  :loading="false"
  @filter-change="handleFilterChange"
  @filter-clear="handleFilterClear"
/>
```
