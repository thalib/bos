# MasterDetailDoc Component Documentation

## Overview

The `MasterDetailDoc.vue` component extends the master-detail layout to include document viewing capabilities. It combines the `MasterDetail` component with document viewer functionality for resources that have associated documents.

## Features

- Master-detail layout with integrated document viewer
- Document template management and preview
- Document generation and download capabilities
- Responsive design with document panel
- Handles loading and error states for documents
- Self-contained document management logic
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
- `showDocumentPanel` _(boolean)_: Whether document panel is visible
- `detailPanelTitle` _(string)_: Title for detail panel
- `resourceTitle` _(string)_: Title of the resource being managed
- `documentTemplates` _(array)_: Available document templates
- `documentData` _(object|null)_: Document data for the selected item

## Events

- `item-select`: Emitted when an item is selected from master list
  - Payload: `{ item: object, index: number }`
- `item-deselect`: Emitted when item selection is cleared
- `detail-close`: Emitted when detail panel is closed
- `document-generate`: Emitted when document generation is requested
  - Payload: `{ item: object, template: object }`
- `document-download`: Emitted when document download is requested
  - Payload: `{ item: object, template: object, format: string }`
- `document-preview`: Emitted when document preview is requested
  - Payload: `{ item: object, template: object }`
- `template-change`: Emitted when document template is changed
  - Payload: `{ template: object }`

## Slots

- `master-header`: Custom header for master panel
- `master-actions`: Custom actions for master panel
- `detail-content`: Custom content for detail panel
- `detail-actions`: Custom actions for detail panel
- `document-header`: Custom header for document panel
- `document-actions`: Custom actions for document panel
- `document-preview`: Custom document preview content
- `empty-detail`: Content shown when no item is selected
- `empty-document`: Content shown when no document is available

## Usage

```vue
<MasterDetailDoc
  :data="response.data"
  :columns="response.columns"
  :pagination="response.pagination"
  :sort="response.sort"
  :loading="isLoading"
  :error="response.error"
  :selectedItem="selectedItem"
  :showDetailPanel="!!selectedItem"
  :showDocumentPanel="!!selectedItem && !!documentData"
  :detailPanelTitle="'Item Details'"
  :resourceTitle="'Invoices'"
  :documentTemplates="documentTemplates"
  :documentData="documentData"
  @item-select="handleItemSelect"
  @item-deselect="handleItemDeselect"
  @detail-close="handleDetailClose"
  @document-generate="handleDocumentGenerate"
  @document-download="handleDocumentDownload"
  @document-preview="handleDocumentPreview"
  @template-change="handleTemplateChange"
>
  <template #master-header>
    <h5>Invoice List</h5>
  </template>
  
  <template #detail-content>
    <div v-if="selectedItem">
      <h6>Invoice #{{ selectedItem.number }}</h6>
      <p>Customer: {{ selectedItem.customer_name }}</p>
      <p>Amount: {{ selectedItem.total_amount }}</p>
    </div>
  </template>
  
  <template #document-header>
    <div class="d-flex justify-content-between align-items-center">
      <h6>Document Preview</h6>
      <select class="form-select form-select-sm" @change="handleTemplateChange">
        <option v-for="template in documentTemplates" :key="template.id" :value="template.id">
          {{ template.name }}
        </option>
      </select>
    </div>
  </template>
  
  <template #document-actions>
    <button class="btn btn-primary me-2" @click="handleDocumentGenerate">
      Generate PDF
    </button>
    <button class="btn btn-outline-secondary" @click="handleDocumentDownload">
      Download
    </button>
  </template>
</MasterDetailDoc>
```

## Internal Logic

The component handles:

- Managing three-panel layout (master, detail, document)
- Integrating document viewer with master-detail functionality
- Handling document template selection and switching
- Managing document generation and download processes
- Coordinating between panels for document-related actions
- Providing responsive layout for document viewing

## Layout Structure

```
MasterDetailDoc
├── Master Panel (40% width on desktop)
│   ├── Master Header (slot)
│   ├── List Component
│   └── Master Actions (slot)
├── Detail Panel (30% width on desktop)
│   ├── Detail Header
│   ├── Detail Content (slot)
│   └── Detail Actions (slot)
└── Document Panel (30% width on desktop)
    ├── Document Header (slot)
    ├── Document Preview (slot)
    └── Document Actions (slot)
```

## Document Management

- Handles document template selection
- Manages document preview and generation
- Supports multiple document formats (PDF, Word, etc.)
- Provides document download functionality
- Integrates with document viewer components

## Responsive Behavior

- **Desktop**: Three-column layout with adjustable panels
- **Tablet**: Two-column layout with collapsible document panel
- **Mobile**: Stacked layout with modal document viewer

## Document Templates

- Supports multiple document templates per resource
- Allows template switching with live preview
- Manages template-specific configurations
- Handles template loading and error states

## Error Handling

- Displays error states for document generation failures
- Shows appropriate messages for missing templates
- Handles API errors gracefully for document operations
- Provides fallback content for document preview failures

## Bootstrap Classes Used

- `row`, `col-*` for responsive grid layout
- `card`, `card-header`, `card-body` for panel styling
- `btn`, `btn-primary`, `btn-outline-secondary` for actions
- `form-select` for template selection
- `modal` for mobile document viewer
- `d-flex`, `justify-content-between` for layout
- `border-end` for panel separation

## Document Features

- Real-time document preview
- Multiple export formats (PDF, Word, Excel)
- Template customization capabilities
- Document generation with data binding
- Print preview functionality
- Document history and versioning

## Integration Points

- Uses `MasterDetail` component as base layout
- Integrates with `DocumentViewer` component
- Supports `Form` component in detail panel
- Works with document generation APIs
- Connects with print and export services

## Notes

- The component extends master-detail functionality with document capabilities
- All document operations are handled through events to parent components
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports keyboard navigation between all panels
- Handles various screen sizes with responsive design
- Maintains document state across item selection changes
