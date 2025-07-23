# Header Component Documentation

## Overview

The `Header.vue` component provides a page header action slots. It serves as a container for filter, search, and action components while maintaining consistent layout and styling.


- Adapts responsively to screen sizes: horizontal layout for desktop, stacked layout for mobile.
- Handles loading states for child components with placeholders for empty slots.
- Maintains consistent styling and spacing using Bootstrap 5.3 patterns.
- Gracefully handles missing or undefined props with fallback default values.
- Delegates all business logic to child components in slots, focusing solely on layout and presentation.
- Ensures proper alignment and spacing across components for a polished and consistent UI.
- Avoids direct API data handling, ensuring a clean separation of concerns.
- Provides a seamless and aesthetic user experience across different screen sizes and usage contexts.

```html
<header
  :loading="isLoading"
  :filters="response.filters"
  :search="response.search"
  @action-create="handleCreate"
  @action-import="handleImport"
  @action-export="handleExport"
>
  <template #filters>
    <Filter
      :filters="filters"
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
</header>
```

**Props**

- `loading` _(boolean)_: Loading state for the component
- `filters` _(array)_: Array of filter options for the filter section
- `search` _(object)_: Search configuration object
- `showActions` _(boolean)_: Whether to show action buttons (default: true)
- `showFilters` _(boolean)_: Whether to show filter section (default: true)
- `showSearch` _(boolean)_: Whether to show search section (default: true)

**Events**

- `action-create`: Emitted when create action is triggered
- `action-import`: Emitted when import action is triggered
- `action-export`: Emitted when export action is triggered
- `filter-change`: Emitted when filters are updated
- `search-change`: Emitted when search input changes
- `action-custom`: Emitted for custom actions, Payload: `{ action: string, data?: any }`

**Slots**

- `filters`: Filter components area
- `search`: Search component area
- `actions`: Custom action buttons area


**Default Actions**

When no custom actions are provided, the component shows:

- Create button
- Import button (optional)
- Export button (optional)

### Internal Logic

The component handles:

- Layout management for different screen sizes
- Proper spacing and alignment of child components
- Loading state propagation to slots
- Default action button rendering
- Responsive behavior for mobile and desktop
- Consistent styling across different usage contexts
- Delegates all business logic to child components in slots
- Ensures layout integrity during loading states with placeholders for empty slots

## Bootstrap Classes Used

- `d-flex`, `justify-content-between` for layout
- `align-items-center` for vertical alignment
- `mb-3`, `me-2` for spacing
- `btn`, `btn-primary`, `btn-outline-secondary` for buttons
- `h4`, `h5` for typography
- `badge`, `text-muted` for count display
- `row`, `col-*` for responsive grid

