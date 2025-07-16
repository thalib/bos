# MasterDetail Component Documentation

## Overview

The `MasterDetail.vue` component provides a master-detail layout for resource management. It receives complete API response data and manages the master list view with detail panel interactions.

## Features

- Split-pane layout with master list and detail panel
- Integrated List component for master view
- Dynamic detail panel content based on selection
- Responsive design for different screen sizes
- Handles loading and error states
- Self-contained master-detail logic
- Bootstrap-based responsive design

## Props

- `data` _(array)_: Array of items from API response
- `columns` _(array)_: Complete columns configuration from API response
- `pagination` _(object|null)_: Pagination configuration from API response
- `sort` _(object|null)_: Current sort configuration from API response
- `loading` _(boolean)_: Loading state for the component
- `error` _(object|null)_: Error object from API response
- `selectedItem` _(object|null)_: Currently selected item
- `showDetailPanel` _(boolean)_: Whether detail panel is visible
- `detailPanelTitle` _(string)_: Title for detail panel
- `resourceTitle` _(string)_: Title of the resource being managed

## Events

- `item-select`: Emitted when an item is selected from master list
  - Payload: `{ item: object, index: number }`
- `item-deselect`: Emitted when item selection is cleared
- `detail-close`: Emitted when detail panel is closed
- `item-create`: Emitted when create action is triggered
- `item-edit`: Emitted when edit action is triggered
  - Payload: `{ item: object }`
- `item-delete`: Emitted when delete action is triggered
  - Payload: `{ item: object }`

## Slots

- `master-header`: Custom header for master panel
- `master-actions`: Custom actions for master panel
- `detail-content`: Custom content for detail panel
- `detail-actions`: Custom actions for detail panel
- `empty-detail`: Content shown when no item is selected

## Usage

```vue
<MasterDetail
  :data="response.data"
  :columns="response.columns"
  :pagination="response.pagination"
  :sort="response.sort"
  :loading="isLoading"
  :error="response.error"
  :selectedItem="selectedItem"
  :showDetailPanel="!!selectedItem"
  :detailPanelTitle="'Item Details'"
  :resourceTitle="'Products'"
  @item-select="handleItemSelect"
  @item-deselect="handleItemDeselect"
  @detail-close="handleDetailClose"
  @item-create="handleItemCreate"
  @item-edit="handleItemEdit"
  @item-delete="handleItemDelete"
>
  <template #master-header>
    <h5>Product List</h5>
  </template>
  
  <template #detail-content>
    <div v-if="selectedItem">
      <h6>{{ selectedItem.name }}</h6>
      <p>{{ selectedItem.description }}</p>
    </div>
  </template>
  
  <template #detail-actions>
    <button class="btn btn-primary me-2" @click="handleItemEdit(selectedItem)">
      Edit
    </button>
    <button class="btn btn-danger" @click="handleItemDelete(selectedItem)">
      Delete
    </button>
  </template>
</MasterDetail>
```

## Internal Logic

The component handles:

- Managing master-detail layout and responsive behavior
- Integrating List component for master view
- Handling item selection and detail panel state
- Coordinating between master and detail panels
- Managing loading states for both panels
- Providing proper keyboard navigation

## Layout Structure

```
MasterDetail
├── Master Panel (60% width on desktop)
│   ├── Master Header (slot)
│   ├── List Component
│   └── Master Actions (slot)
└── Detail Panel (40% width on desktop)
    ├── Detail Header
    ├── Detail Content (slot)
    └── Detail Actions (slot)
```

## Responsive Behavior

- **Desktop**: Side-by-side layout with 60/40 split
- **Tablet**: Adjustable split with responsive breakpoints
- **Mobile**: Stacked layout with detail panel overlay

## Selection Management

- Tracks currently selected item
- Provides visual feedback for selected state
- Handles selection clearing and deselection
- Manages detail panel visibility based on selection

## Error Handling

- Displays error states in master panel
- Shows appropriate messages for empty results
- Handles API errors gracefully
- Provides fallback content for missing detail data

## Bootstrap Classes Used

- `row`, `col-*` for responsive grid layout
- `card`, `card-header`, `card-body` for panel styling
- `list-group`, `list-group-item` for master list
- `btn`, `btn-primary`, `btn-secondary` for actions
- `d-flex`, `justify-content-between` for layout
- `active` for selected item styling
- `border-end` for panel separation

## Integration Points

- Uses `List` component for master view
- Integrates with `PaginationS` for master pagination
- Supports `Form` component in detail panel
- Works with `Filter` and `Search` components

## Notes

- The component is self-contained and manages panel state
- All API data is passed through to child components
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports keyboard navigation between panels
- Handles various screen sizes with responsive design
- Maintains selection state across data updates
