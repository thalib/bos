# MasterDetail Component Design Specification

- The `MasterDetail` component provides a master-detail layout for resource management. It dynamically manages the master list view and detail panel interactions.

**File Location:** `frontend/app/components/Resource/MasterDetail.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<MasterDetail
  :data="items"
  :columns="columns"
  :pagination="pagination"
  :loading="false"
  :error="null"
  :selectedItem="selectedItem"
  :showDetailPanel="true"
  :detailPanelTitle="'Details'"
  :resourceTitle="'Resources'"
/>
```

- **Props:**
  - `data` (array): Array of items from API response.
  - `columns` (array): Configuration for table columns.
  - `pagination` (object): Pagination configuration.
  - `loading` (boolean): Indicates if the component is in a loading state.
  - `error` (object): Object containing error details.
  - `selectedItem` (object): Currently selected item.
  - `showDetailPanel` (boolean): Boolean to toggle detail panel visibility.
  - `detailPanelTitle` (string): Title for the detail panel.
  - `resourceTitle` (string): Title of the resource being managed.
- **Events:**
  - `item-select`: Triggered when an item is selected.
  - `item-deselect`: Triggered when item selection is cleared.
  - `detail-close`: Triggered when the detail panel is closed.

## Child Components (optional)

```txt
Parent
└── MasterDetail
```

## Features

- Split-pane layout with master list and detail panel.
- Dynamic detail panel content based on selection.
- Handles loading and error states gracefully.
- Responsive design for various screen sizes.

## UI Design

```txt
+-----------------------------------------------+
| [Master List] [Detail Panel]                  |
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
<MasterDetail
  :data="[{ id: 1, name: 'Sample Item' }]
  :columns="[{ field: 'name', label: 'Name' }]"
  :loading="false"
  @item-select="handleItemSelect"
/>
```
