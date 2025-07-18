# Filter Component Documentation

## Overview

The `Filter.vue` component provides dropdown-based filtering options for data tables or lists. It receives the complete filters node from the API response and handles all filtering logic internally.

## Features

- Dropdown menu for filter selection with available options
- Displays applied filters with clear indicators
- Handles loading and error states
- Self-contained filtering logic
- Bootstrap-based responsive design

## Props

- `filters` _(object|null)_: Complete filters node from API response containing `applied` and `available` properties
- `loading` _(boolean)_: Loading state for the component
- `disabled` _(boolean)_: Whether the filter is disabled

## Events

- `filter-change`: Emitted when a filter is selected/changed
  - Payload: `{ field: string, value: string }`
- `filter-clear`: Emitted when filters are cleared
  - Payload: `{ field: string }`

## API Response Structure

The component expects the `filters` prop to match the API response structure:

```json
{
  "filters": {
    "applied": { "field": "status", "value": "active" } | null,
    "available": [
      {
        "field": "status",
        "label": "Status",
        "values": ["active", "inactive", "pending"]
      }
    ] | null
  }
}
```

## Usage

```vue
<Filter
  :filters="response.filters"
  :loading="isLoading"
  @filter-change="handleFilterChange"
  @filter-clear="handleFilterClear"
/>
```

## Internal Logic

The component handles:

- Parsing available filter options from the API response
- Displaying currently applied filters
- Formatting filter labels and values
- Managing dropdown state and interactions
- Providing clear visual feedback for active filters

## Error Handling

- Displays error state when filters fail to load
- Shows appropriate fallback UI when no filters are available
- Provides user-friendly error messages

## Bootstrap Classes Used

- `dropdown`, `dropdown-toggle`, `dropdown-menu` for dropdown functionality
- `btn`, `btn-outline-secondary`, `btn-primary` for button styling
- `badge`, `bg-primary` for active filter indicators
- `spinner-border` for loading states
- `text-muted`, `text-warning` for status indicators

## Notes

- The component is self-contained and manages its own state
- All filtering logic is handled internally based on the API response
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
