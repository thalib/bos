# Header Component Design Specification

- The `Header` component provides a page header with action slots. It serves as a container for filter, search, and action components while maintaining consistent layout and styling.

**File Location:** `frontend/app/components/Resource/Header.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Header
  :loading="isLoading"
  @action-export="handleExport"
>
  <template #filters>
    <!-- Filter components -->
  </template>
  <template #search>
    <!-- Search components -->
  </template>
  <template #actions>
    <!-- Action buttons -->
  </template>
</Header>
```

- **Props:**
  - `loading` (boolean): Indicates if the component is in a loading state.
  - `filters` (array): Array of filter options for the filter section.
  - `search` (object): Search configuration object.
  - `showActions` (boolean): Whether to show action buttons (default: true).
  - `showFilters` (boolean): Whether to show filter section (default: true).
  - `showSearch` (boolean): Whether to show search section (default: true).
- **Events:**
  - `action-create`: Triggered when create action is triggered.
  - `action-import`: Triggered when import action is triggered.
  - `action-export`: Triggered when export action is triggered.
  - `filter-change`: Triggered when filters are updated.
  - `search-change`: Triggered when search input changes.
  - `action-custom`: Triggered for custom actions. Payload: `{ action: string, data?: any }`.

## Child Components (optional)

```txt
Parent
└── Header
```

## Features

- Adapts responsively to screen sizes: horizontal layout for desktop, stacked layout for mobile.
- Handles loading states for child components with placeholders for empty slots.
- Maintains consistent styling and spacing using Bootstrap 5.3 patterns.
- Delegates all business logic to child components in slots, focusing solely on layout and presentation.
- Provides a seamless and aesthetic user experience across different screen sizes and usage contexts.

## UI Design

```txt
+-----------------------------------------------+
| [Filters] [Search] [Actions]                  |
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

- Displays error state when child components fail to load.
- Provides user-friendly error messages for missing or undefined props.

## Example Usage (optional)

```html
<Header
  :loading="false"
  @action-export="handleExport"
>
  <template #filters>
    <Filter :filters="filters" />
  </template>
  <template #search>
    <Search :search="searchQuery" />
  </template>
  <template #actions>
    <button class="btn btn-primary">Create</button>
  </template>
</Header>
```

