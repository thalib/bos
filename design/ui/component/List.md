# List Component Documentation

## Overview

The `List.vue` component provides a dynamic list/table display for resources. It receives the complete data from the API response (design\api\index.md) and handles all display logic internally.

- Dynamic table rendering based on API columns configuration
- Responsive design with mobile-friendly layout
- Built-in sorting capabilities with visual indicators
- Item selection and click handling
- Handles loading, error states, and fallback UI for empty data
- Self-contained display logic based on API response
- Bootstrap 5.3-based styling for consistency and responsiveness
- Provides user-friendly error messages and ARIA attributes for accessibility
- Automatically handles various data types and formats
- Manages its own state and handles missing or invalid column configurations
- Ensures responsive behavior for mobile devices
- Displays appropriate UI for error or empty states
- Follows modern accessibility standards

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

**Props**

- `data` _(array)_: Array of items from API response (design\api\index.md)
- `columns` _(array)_: Complete columns configuration from API response (design\api\index.md)
- `sort` _(object|null)_: Current sort configuration from API response (design\api\index.md)
- `loading` _(boolean)_: Loading state for the component

**Events**

- `item-click`: Emitted when an item is clicked, Payload: `{ item: object, index: number }`
- `sort-change`: Emitted when column sorting is requested, Payload: `{ column: string, direction: string }`

**Error Handling**

If the `data` or `columns` props are `null` or invalid, the component will display a user-friendly error message in place of the data table. This ensures the user is informed about the issue and prevents rendering an empty or broken table.

The error message will be styled using Bootstrap's `alert` classes for consistency and accessibility:

```html
<div class="alert alert-danger" role="alert">
  Unable to load data. Please try again later or contact support. {more details button}
  {on click of more details, show if more details are available about the error}
</div>
```

This fallback UI is automatically triggered when the required props are missing or invalid, providing a seamless user experience.

## API Response Structure

The component expects props to match the API response structure:

```json
"data": [ /* array of resource objects */ ],
```

```json
response["data"]: [
  {
    "id": 1,
    "name": "Sample Product",
    "price": 299.99,
    "status": "active"
  },
  {
    "id": 2,
    "name": "Good Product",
    "price": 555.99,
    "status": "active"
  }
]
```

```json
"columns": [
  {
    "field": "<string>", //mandatory
    "label": "<string>", //mandatory
    "sortable": <boolean>, //optional, tells if the column should be sortable
    "clickable": <boolean>, //optional, if the column should be a clickable link to Form.vue component
    "search": <boolean>, //not used by frontend, tells backend to include this column when searching
    "type": "<string>", // optional, default column type is text, can be decimal, etc.
    "format": "<string>", //optional e.g., format to currency
    "align": "<string>", //optional left, right, middle, default left
  }
]
```

```json
response["columns"]: [
  {
    "field": "name",
    "label": "Name",
    "sortable": true,
    "clickable": true,
    "search": true,
    "type": "string"
  },
  {
    "field": "price",
    "label": "Price",
    "sortable": true,
    "type": "decimal",
    "format": "currency",
    "align": "right"
  }
]
```

```json
"sort": {
    "column": "<string>",
    "dir": "<\"asc\"|\"desc\">"
  } | null,
```

```json
response["sort"]: {
  "column": "name",
  "dir": "asc"
}

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
- `decimal`: Number formatting
- `date`: Date formatting
- `datetime`: Date and time formatting
- `boolean`: Checkbox or badge display
- `email`: Email link formatting
- `url`: URL link formatting
- `image`: Image thumbnail display

## Bootstrap Classes Used

- `table`, `table-striped`, `table-hover` for table styling
- `table-responsive` for responsive behavior
- `btn`, `btn-link` for sortable headers
- `badge` for status indicators
- `spinner-border` for loading states
- `alert`, `alert-danger` for error states
- `text-muted` for empty states
