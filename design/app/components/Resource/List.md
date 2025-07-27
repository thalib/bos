
# List Component Design Specification

- Data table for displaying resources. Data fetching, sorting, and error handling are managed by the parent component.
- Receives data, loading, error, and sort state via props. Handles only presentation and user interaction.

**File Location:** `frontend/app/components/Resource/List.vue`

## Component Structure


Example usage:

```html
<List
  :data="tableData"
  :columns="columns"
  :isLoading="isLoading"
  :hasError="hasError"
  :sortConfig="sortConfig"
  @sort-changed="handleSortChanged"
/>
```

- **Props:**
  - `data` (array, required): The resource data to display (array of objects)
  - `columns` (array, required): Column configuration (array of objects)
  - `isLoading` (boolean, optional): Loading state
  - `hasError` (boolean, optional): Error state
  - `sortConfig` (object, optional): Current sort state `{ column: string, direction: 'asc'|'desc' }`
- **Events:**
  - `sort-changed`: Emitted when user requests a sort. Payload: `{ column: string, direction: 'asc'|'desc' }`

## Child Components (optional)

```txt
List
└── [CellRenderer]
```

- **CellRenderer:**
  - Design specification reference: `design/app/components/Resource/CellRenderer.md`
  - File path: `frontend/app/components/Resource/CellRenderer.vue`

## Features

- Pure presentational logic: receives all data, loading, error, and sort state via props
- Dynamic columns: renders columns based on provided configuration
- Responsive table: Bootstrap 5.3 responsive table with mobile-friendly design
- Sorting integration: visual sort indicators, emits sort-changed event
- Row interactions: clickable rows with selection states
- Loading states: skeleton loading and shimmer effects
- Empty states: user-friendly empty state with action suggestions
- Accessibility: ARIA labels, keyboard navigation, screen reader support

## UI Design


- Uses Bootstrap 5.3 classes for all layout and UI elements.

## Implementation Rules

- Data fetching, error handling, and sort state are managed by the parent component.
- List only displays data and emits user interaction events.
- Use Bootstrap 5.3 classes for all layout and UI elements.
- Strictly type all props and logic with TypeScript.
- Provide loading and error states via props.
- Ensure accessibility (ARIA roles, keyboard navigation).
- Write tests first in `frontend/tests/` before implementing features.

## Error Handling (optional)

- Error state is visually distinct and offers a retry action (triggered by parent).

## Accessibility (optional)

- ARIA labels for table and sort state.
- Keyboard navigation for rows and sorting.
- Announces sort changes to screen readers.

## Example Usage (optional)

```html
<List
  :data="users"
  :columns="userColumns"
  :isLoading="isLoading"
  :hasError="hasError"
  :sortConfig="sortConfig"
  @sort-changed="handleSortChanged"
/>
```

## References

- [Frontend Rules](design/rules-app.md)
- [GitHub Copilot Instructions](.github/copilot-instructions.md)
