# List Component Documentation

## Overview

The `List.vue` component provides a dynamic list/table display for resources. It receives the complete data, columns, and other nodes from the API response and handles all display logic internally.

## Features

- Dynamic table rendering based on API columns configuration
- Responsive table design with mobile-friendly layout
- Built-in sorting capabilities with visual indicators
- Item selection and click handling
- Handles loading and error states
- Self-contained display logic
- Bootstrap-based responsive design

## Props

- `data` _(array)_: Array of items from API response
- `columns` _(array)_: Complete columns configuration from API response
- `sort` _(object|null)_: Current sort configuration from API response
- `loading` _(boolean)_: Loading state for the component
- `error` _(object|null)_: Error object from API response
- `clickable` _(boolean)_: Whether items are clickable (default: true)
- `selectable` _(boolean)_: Whether items can be selected (default: false)

## Events

- `item-click`: Emitted when an item is clicked
  - Payload: `{ item: object, index: number }`
- `item-select`: Emitted when items are selected
  - Payload: `{ selectedItems: array }`
- `sort-change`: Emitted when column sorting is requested
  - Payload: `{ column: string, direction: string }`

## API Response Structure

The component expects props to match the API response structure:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Sample Product",
      "price": 299.99,
      "status": "active"
    }
  ],
  "columns": [
    {
      "field": "name",
      "label": "Name",
      "sortable": true,
      "clickable": true,
      "search": true,
      "type": "string",
      "width": "200px"
    },
    {
      "field": "price",
      "label": "Price",
      "sortable": true,
      "type": "currency",
      "format": "currency",
      "align": "right"
    }
  ],
  "sort": {
    "column": "name",
    "dir": "asc"
  }
}
```

## Usage

```vue
<List
  :data="response.data"
  :columns="response.columns"
  :sort="response.sort"
  :loading="isLoading"
  :error="response.error"
  :clickable="true"
  :selectable="false"
  @item-click="handleItemClick"
  @item-select="handleItemSelect"
  @sort-change="handleSortChange"
/>
```

## Internal Logic

The component handles:

- Rendering table headers with sorting indicators
- Formatting cell values based on column type and format
- Managing row selection state
- Handling click events on rows and cells
- Providing responsive table behavior
- Displaying loading and error states

## Supported Column Types

- `string`: Plain text display
- `number`: Number formatting
- `currency`: Currency formatting with symbol
- `date`: Date formatting
- `datetime`: Date and time formatting
- `boolean`: Checkbox or badge display
- `email`: Email link formatting
- `url`: URL link formatting
- `image`: Image thumbnail display

## Error Handling

- Displays error state when data fails to load
- Shows appropriate fallback UI when no data is available
- Provides user-friendly error messages
- Handles missing or invalid column configurations

## Bootstrap Classes Used

- `table`, `table-striped`, `table-hover` for table styling
- `table-responsive` for responsive behavior
- `btn`, `btn-link` for sortable headers
- `badge` for status indicators
- `spinner-border` for loading states
- `alert`, `alert-danger` for error states
- `text-muted` for empty states

## Notes

- The component is self-contained and manages its own state
- All display logic is handled internally based on the API response
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports responsive design for mobile devices
- Handles various data types and formats automatically
