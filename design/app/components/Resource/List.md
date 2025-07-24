# List Component Design Specification

- The `List` component provides a dynamic list/table display for resources. It receives the complete data from the API response and handles all display logic internally.

**File Location:** `frontend/app/components/Resource/List.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<List
  :data="response.data"
  :columns="response.columns"
  :sort="response.sort"
  :loading="isLoading"
  @item-click="handleItemClick"
  @item-select="handleItemSelect"
  @sort-change="handleSortChange"
/>
```

- **Props:**
  - `data` (array): Array of items from API response.
  - `columns` (array): Complete columns configuration from API response.
  - `sort` (object|null): Current sort configuration from API response.
  - `loading` (boolean): Indicates if the component is in a loading state.
- **Events:**
  - `item-click`: Triggered when an item is clicked. Payload: `{ item: object, index: number }`.
  - `sort-change`: Triggered when column sorting is requested. Payload: `{ column: string, direction: string }`.

## Child Components (optional)

```txt
Parent
└── List
```

## Features

- Dynamic table rendering based on API columns configuration.
- Responsive design with mobile-friendly layout.
- Built-in sorting capabilities with visual indicators.
- Item selection and click handling.
- Handles loading, error states, and fallback UI for empty data.

## UI Design

```txt
+-----------------------------------------------+
| [Table Headers with Sorting Indicators]       |
+-----------------------------------------------+
| [Table Rows with Data]                        |
+-----------------------------------------------+
| [Pagination Controls]                         |
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

- Displays a user-friendly error message if the `data` or `columns` props are invalid.
- Provides fallback UI for empty or error states.

## Example Usage (optional)

```html
<List
  :data="[{ id: 1, name: 'Sample Product', price: 299.99 }]
  :columns="[{ field: 'name', label: 'Name', sortable: true }]
  :loading="false"
  @item-click="handleItemClick"
/>
```
