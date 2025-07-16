# MasterDetailContainer Component Documentation

## Overview

The `MasterDetailContainer.vue` component provides a responsive container layout for master-detail interfaces. It handles the layout, transitions, and responsive behavior while delegating content rendering to slotted components.

## Features

- Responsive master-detail layout with smooth transitions
- Configurable panel sizes and breakpoints
- Handles panel visibility and state management
- Supports collapsible panels for mobile view
- Provides consistent spacing and styling
- Bootstrap-based responsive design

## Props

- `showDetailPanel` _(boolean)_: Whether detail panel is visible
- `masterWidth` _(number)_: Width percentage for master panel (default: 60)
- `detailWidth` _(number)_: Width percentage for detail panel (default: 40)
- `collapsible` _(boolean)_: Whether panels can be collapsed on mobile (default: true)
- `minPanelWidth` _(number)_: Minimum width for panels in pixels (default: 300)
- `transition` _(string)_: Transition animation type (default: 'slide')
- `mobileBreakpoint` _(string)_: Bootstrap breakpoint for mobile layout (default: 'md')

## Events

- `panel-resize`: Emitted when panel sizes change
  - Payload: `{ masterWidth: number, detailWidth: number }`
- `panel-collapse`: Emitted when a panel is collapsed
  - Payload: `{ panel: 'master' | 'detail' }`
- `panel-expand`: Emitted when a panel is expanded
  - Payload: `{ panel: 'master' | 'detail' }`

## Slots

- `master-content`: Content for the master panel
- `detail-content`: Content for the detail panel
- `master-header`: Optional header for master panel
- `detail-header`: Optional header for detail panel
- `resizer`: Custom resizer component (optional)

## Usage

```vue
<MasterDetailContainer
  :showDetailPanel="!!selectedItem"
  :masterWidth="65"
  :detailWidth="35"
  :collapsible="true"
  :minPanelWidth="280"
  :transition="'slide'"
  :mobileBreakpoint="'lg'"
  @panel-resize="handlePanelResize"
  @panel-collapse="handlePanelCollapse"
  @panel-expand="handlePanelExpand"
>
  <template #master-header>
    <div class="d-flex justify-content-between align-items-center p-3">
      <h5>Items</h5>
      <button class="btn btn-sm btn-primary">Add New</button>
    </div>
  </template>
  
  <template #master-content>
    <List
      :data="response.data"
      :columns="response.columns"
      :loading="loading"
      @item-click="handleItemSelect"
    />
  </template>
  
  <template #detail-header>
    <div class="d-flex justify-content-between align-items-center p-3">
      <h6>Item Details</h6>
      <button class="btn btn-sm btn-outline-secondary" @click="closeDetail">
        <i class="bi bi-x"></i>
      </button>
    </div>
  </template>
  
  <template #detail-content>
    <div class="p-3">
      <Form
        :schema="response.schema"
        :data="selectedItem"
        :mode="'edit'"
        @form-submit="handleFormSubmit"
      />
    </div>
  </template>
</MasterDetailContainer>
```

## Internal Logic

The component handles:

- Responsive layout calculations based on screen size
- Panel visibility management and transitions
- Touch and mouse interactions for resizing
- Breakpoint-based layout switching
- Panel collapse/expand state management
- Smooth transitions between layout states

## Layout Modes

- **Desktop**: Side-by-side resizable panels
- **Tablet**: Adjustable split with touch-friendly resizing
- **Mobile**: Stacked layout with collapsible panels

## Responsive Behavior

- **Above breakpoint**: Horizontal split layout
- **Below breakpoint**: Vertical stacked layout
- **Touch devices**: Enhanced touch interactions for resizing

## Panel Management

- Tracks panel visibility and sizes
- Handles panel state persistence
- Provides panel collapse/expand functionality
- Manages transition animations

## Error Handling

- Gracefully handles missing slot content
- Provides fallback layouts for edge cases
- Maintains layout integrity during state changes
- Handles responsive breakpoint changes smoothly

## Bootstrap Classes Used

- `row`, `col-*` for responsive grid
- `d-flex`, `flex-column` for layout
- `border-end` for panel separation
- `position-relative` for resizer positioning
- `transition-all` for smooth animations
- `overflow-hidden` for proper clipping
- `h-100` for full height panels

## Resizing Features

- Drag-and-drop panel resizing
- Touch-friendly resizing for mobile
- Configurable minimum and maximum sizes
- Smooth animation during resize
- Persistent panel size preferences

## Animation Types

- `slide`: Slide in/out animation
- `fade`: Fade in/out animation
- `none`: No animation
- `custom`: Custom animation via CSS classes

## Notes

- The component is layout-focused and doesn't handle business logic
- All content is provided through slots for maximum flexibility
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports keyboard navigation between panels
- Handles various screen sizes with responsive design
- Maintains smooth performance during transitions
