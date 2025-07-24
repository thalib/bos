# TableSorting Component Design Specification

- The `TableSorting` component provides sorting and filtering status display for data tables. It dynamically displays active sorting and filtering information.

**File Location:** `frontend/app/components/Resource/TableSorting.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<TableSorting
  :sort="sortConfig"
  :filters="filters"
  :search="searchQuery"
  :loading="false"
  :disabled="false"
  @sort-clear="handleSortClear"
  @filters-clear="handleFiltersClear"
  @search-clear="handleSearchClear"
  @filter-remove="handleFilterRemove"
/>
```

- **Props:**
  - `sort` (object): Current sort configuration.
  - `filters` (object): Active filters.
  - `search` (string): Current search query.
  - `loading` (boolean): Indicates if the component is in a loading state.
  - `disabled` (boolean): Whether the component is disabled.
- **Events:**
  - `sort-clear`: Triggered when sorting is cleared.
  - `filters-clear`: Triggered when all filters are cleared.
  - `search-clear`: Triggered when the search is cleared.
  - `filter-remove`: Triggered when a specific filter is removed.

## Child Components (optional)

```txt
Parent
└── TableSorting
```

## Features

- Displays current sorting configuration with visual indicators.
- Shows active filters with clear badges.
- Provides functionality to clear all filters.
- Handles loading states during operations.
- Responsive design for mobile and desktop.

## UI Design

```txt
+-----------------------------------------------+
| [Sort Indicators] [Filter Badges]             |
+-----------------------------------------------+
| [Clear Buttons for Filters and Sorting]       |
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

- Handles null or undefined sort/filter data gracefully.
- Provides fallback display when no sorting/filtering is active.
- Shows appropriate messages for empty states.

## Example Usage (optional)

```html
<TableSorting
  :sort="{ column: 'name', dir: 'asc' }"
  :filters="{ applied: { field: 'status', value: 'active' } }"
  :search="'example query'"
  :loading="false"
  @sort-clear="handleSortClear"
/>
```
