# Search Component Design Specification

- The `Search` component provides a search input field with real-time query handling. It dynamically manages search interactions and displays search results.

**File Location:** `frontend/app/components/Resource/Search.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Search
  :search="searchQuery"
  :loading="false"
  :disabled="false"
  :placeholder="'Search...'"
  :debounceMs="300"
  :minLength="2"
  @search-change="handleSearchChange"
  @search-clear="handleSearchClear"
  @search-submit="handleSearchSubmit"
/>
```

- **Props:**
  - `search` (string): Current search query.
  - `loading` (boolean): Indicates if the component is in a loading state.
  - `disabled` (boolean): Whether the search input is disabled.
  - `placeholder` (string): Placeholder text for the search input.
  - `debounceMs` (number): Debounce delay in milliseconds.
  - `minLength` (number): Minimum search length.
- **Events:**
  - `search-change`: Triggered when the search query changes.
  - `search-clear`: Triggered when the search is cleared.
  - `search-submit`: Triggered when the search is explicitly submitted.

## Child Components (optional)

```txt
Parent
└── Search
```

## Features

- Real-time search input with debounce functionality.
- Search button for explicit search triggering.
- Clear button for resetting search.
- Displays search result information.
- Handles loading states during search.

## UI Design

```txt
+-----------------------------------------------+
| [Search Input Field]                          |
+-----------------------------------------------+
| [Search Button] [Clear Button]                |
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

- Displays a user-friendly error message if the `search` prop is invalid.
- Provides fallback UI for empty or error states.

## Example Usage (optional)

```html
<Search
  :search="'example query'"
  :loading="false"
  :placeholder="'Search for items...'"
  @search-change="handleSearchChange"
/>
```
