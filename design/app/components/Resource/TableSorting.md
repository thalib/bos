# TableSorting Component Design Specification

## Overview

The `TableSorting` component provides sorting and filtering status display for data tables. It dynamically displays active sorting and filtering information.

```vue
<TableSorting
  :sort="sortConfig"
  :filters="filters"
  :search="searchQuery"
  :loading="false"
  :disabled="false"
/>
```

## Features

- Displays current sorting configuration with visual indicators.
- Shows active filters with clear badges.
- Provides functionality to clear all filters.
- Handles loading states during operations.
- Responsive design for mobile and desktop.

## Props

- `sort`: Current sort configuration.
- `filters`: Active filters.
- `search`: Current search query.
- `loading`: Boolean indicating loading state.
- `disabled`: Boolean to disable the component.

## Events

- `sort-clear`: Triggered when sorting is cleared.
- `filters-clear`: Triggered when all filters are cleared.
- `search-clear`: Triggered when search is cleared.
- `filter-remove`: Triggered when a specific filter is removed.

## API Response Structure

The component expects props to match the API response structure:

```json
{
  "sort": {
    "column": "name",
    "dir": "asc"
  },
  "filters": {
    "applied": { "field": "status", "value": "active" },
    "available": [
      {
        "field": "status",
        "label": "Status",
        "values": ["active", "inactive"]
      }
    ]
  },
  "search": "mobile phone"
}
```

## Internal Logic

The component handles:

- Parsing sort configuration and displaying sort indicators
- Showing active filters as removable badges
- Calculating total active filters count
- Formatting sort and filter labels for display
- Managing clear operations for various states
- Providing responsive layout adjustments

## Display Features

- Shows current sort column and direction with icons
- Displays active filters as colored badges
- Provides clear buttons for individual and bulk operations
- Shows loading states during filter/sort operations
- Indicates total number of active filters
- Provides responsive layout for different screen sizes

## Sort Display

- Shows column name being sorted
- Displays sort direction with arrow icons
- Provides clear sort functionality
- Indicates when no sorting is active

## Filter Display

- Shows applied filters as badges
- Displays filter field and value
- Provides individual filter removal
- Shows total filter count
- Provides clear all filters functionality

## Error Handling

- Handles null or undefined sort/filter data gracefully
- Provides fallback display when no sorting/filtering is active
- Shows appropriate messages for empty states
- Maintains component stability during errors

## Bootstrap Classes Used

- `badge`, `bg-primary`, `bg-secondary` for filter badges
- `btn`, `btn-outline-secondary`, `btn-sm` for clear buttons
- `d-flex`, `align-items-center` for layout
- `me-2`, `ms-2` for spacing
- `spinner-border` for loading states
- `text-muted` for status text
- `bi` icons for sort indicators

## Responsive Behavior

- **Desktop**: Full display with all sorting and filtering information
- **Mobile**: Compact layout with essential information
- **Tablet**: Balanced approach with key information visible

## Notes

- The component is self-contained and manages display logic
- All sorting and filtering status is handled internally
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports keyboard navigation for interactive elements
- Handles various screen sizes with responsive design
