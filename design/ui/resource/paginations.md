# PaginationS Component Documentation

## Overview

The `PaginationS.vue` component provides pagination controls for data tables or lists. It receives the complete pagination node from the API response and handles all pagination logic internally.

## Features

- Displays current page range and total entries
- Allows users to navigate between pages
- Provides per-page selection dropdown
- Responsive design for mobile and desktop
- Handles loading states
- Self-contained pagination logic
- Bootstrap-based responsive design

## Props

- `pagination` _(object|null)_: Complete pagination node from API response
- `loading` _(boolean)_: Loading state for the component
- `disabled` _(boolean)_: Whether pagination is disabled
- `showInfo` _(boolean)_: Whether to show pagination info text (default: true)
- `perPageOptions` _(array)_: Available per-page options (default: [10, 25, 50, 100])

## Events

- `page-change`: Emitted when page is changed
  - Payload: `{ page: number }`
- `per-page-change`: Emitted when per-page value is changed
  - Payload: `{ perPage: number }`

## API Response Structure

The component expects the `pagination` prop to match the API response structure:

```json
{
  "pagination": {
    "totalItems": 150,
    "currentPage": 2,
    "itemsPerPage": 25,
    "totalPages": 6,
    "urlPath": "http://localhost:8000/api/v1/products",
    "urlQuery": "sort=name&dir=asc",
    "nextPage": "http://localhost:8000/api/v1/products?page=3",
    "prevPage": "http://localhost:8000/api/v1/products?page=1"
  }
}
```

## Usage

```vue
<PaginationS
  :pagination="response.pagination"
  :loading="isLoading"
  :disabled="false"
  :showInfo="true"
  :perPageOptions="[10, 25, 50, 100]"
  @page-change="handlePageChange"
  @per-page-change="handlePerPageChange"
/>
```

## Internal Logic

The component handles:

- Calculating page ranges and displaying current position
- Generating page number buttons with proper spacing
- Managing per-page selection dropdown
- Handling edge cases (first page, last page, single page)
- Providing responsive layout for different screen sizes
- Displaying loading states during pagination changes

## Responsive Behavior

- **Desktop**: Full pagination controls with page numbers
- **Mobile**: Compact design with dropdown navigation
- **Tablet**: Hybrid approach with essential controls

## Display Features

- Shows "Showing X to Y of Z entries" information
- Displays page numbers with ellipsis for large page counts
- Provides first/last page navigation
- Indicates current page with active styling
- Shows loading spinners during page changes

## Error Handling

- Gracefully handles null or invalid pagination data
- Provides fallback UI when pagination is unavailable
- Displays appropriate messages for empty results
- Handles edge cases like single page results

## Bootstrap Classes Used

- `pagination` for pagination wrapper
- `page-item`, `page-link` for page buttons
- `btn`, `btn-outline-primary` for navigation buttons
- `form-select` for per-page dropdown
- `spinner-border` for loading states
- `text-muted` for info text
- `d-flex`, `justify-content-between` for layout
- `d-sm-block`, `d-md-none` for responsive behavior

## Notes

- The component is self-contained and manages its own state
- All pagination logic is handled internally based on the API response
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports keyboard navigation for better usability
- Handles various screen sizes with responsive design
