# Header Component Documentation

## Overview

The `Header.vue` component provides a page header with resource title and action slots. It serves as a container for filter, search, and action components while maintaining consistent layout and styling.

## Features

- Displays resource title and page information
- Provides slots for filter, search, and action components
- Responsive layout for different screen sizes
- Handles loading states for child components
- Consistent styling and spacing
- Bootstrap-based responsive design

## Props

- `resourceTitle` _(string)_: Title of the resource being managed
- `resourceCount` _(number)_: Total count of resources (optional)
- `loading` _(boolean)_: Loading state for the component
- `showActions` _(boolean)_: Whether to show action buttons (default: true)
- `showFilters` _(boolean)_: Whether to show filter section (default: true)
- `showSearch` _(boolean)_: Whether to show search section (default: true)

## Events

- `action-create`: Emitted when create action is triggered
- `action-import`: Emitted when import action is triggered
- `action-export`: Emitted when export action is triggered
- `action-custom`: Emitted for custom actions
  - Payload: `{ action: string, data?: any }`

## Slots

- `filters`: Filter components area
- `search`: Search component area
- `actions`: Custom action buttons area
- `title`: Custom title area (overrides resourceTitle prop)

## Usage

```vue
<Header
  :resourceTitle="'Products'"
  :resourceCount="response.pagination?.totalItems"
  :loading="isLoading"
  :showActions="true"
  :showFilters="true"
  :showSearch="true"
  @action-create="handleCreate"
  @action-import="handleImport"
  @action-export="handleExport"
>
  <template #filters>
    <Filter
      :filters="response.filters"
      :loading="isLoading"
      @filter-change="handleFilterChange"
    />
  </template>
  
  <template #search>
    <Search
      :search="response.search"
      :loading="isLoading"
      @search-change="handleSearchChange"
    />
  </template>
  
  <template #actions>
    <button class="btn btn-primary" @click="handleCreate">
      <i class="bi bi-plus-circle me-2"></i>
      Create New
    </button>
  </template>
</Header>
```

## Internal Logic

The component handles:

- Layout management for different screen sizes
- Proper spacing and alignment of child components
- Loading state propagation to slots
- Default action button rendering
- Responsive behavior for mobile and desktop
- Consistent styling across different usage contexts

## Responsive Layout

- **Desktop**: Horizontal layout with filters, search, and actions in a row
- **Mobile**: Stacked layout with filters and search above, actions below
- **Tablet**: Hybrid layout with smart wrapping

## Default Actions

When no custom actions are provided, the component shows:

- Create button (if `showActions` is true)
- Import button (optional)
- Export button (optional)

## Error Handling

- Handles missing or undefined props gracefully
- Provides fallback titles and default behavior
- Maintains layout integrity during loading states
- Shows appropriate placeholders for empty slots

## Bootstrap Classes Used

- `d-flex`, `justify-content-between` for layout
- `align-items-center` for vertical alignment
- `mb-3`, `me-2` for spacing
- `btn`, `btn-primary`, `btn-outline-secondary` for buttons
- `h4`, `h5` for typography
- `badge`, `text-muted` for count display
- `row`, `col-*` for responsive grid

## Layout Structure

```
Header
├── Title Section
│   ├── Resource Title
│   └── Resource Count (if available)
├── Filter Section (slot)
├── Search Section (slot)
└── Actions Section (slot)
```

## Notes

- The component is a layout container and doesn't handle API data directly
- All business logic is delegated to child components in slots
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports responsive design for various screen sizes
- Maintains consistent spacing and alignment across components
