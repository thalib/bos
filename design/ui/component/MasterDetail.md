# MasterDetail Component Design Specification

## Overview

The `MasterDetail` component provides a master-detail layout for resource management. It dynamically manages the master list view and detail panel interactions.

```vue
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

## Features

- Split-pane layout with master list and detail panel.
- Dynamic detail panel content based on selection.
- Handles loading and error states gracefully.
- Responsive design for various screen sizes.

## Props

- `data`: Array of items from API response.
- `columns`: Configuration for table columns.
- `pagination`: Pagination configuration.
- `loading`: Boolean indicating loading state.
- `error`: Object containing error details.
- `selectedItem`: Currently selected item.
- `showDetailPanel`: Boolean to toggle detail panel visibility.
- `detailPanelTitle`: Title for the detail panel.
- `resourceTitle`: Title of the resource being managed.

## Events

- `item-select`: Triggered when an item is selected.
- `item-deselect`: Triggered when item selection is cleared.
- `detail-close`: Triggered when the detail panel is closed.
