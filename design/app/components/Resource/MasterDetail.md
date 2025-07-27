# MasterDetail Component Design Specification

The `MasterDetail` component manages interactions between the List component and detail panels (Form or DocumentView), adapting to the application's mode.

**File Location:** `frontend/app/components/Resource/MasterDetail.vue`

```html
<MasterDetail
  :data="data" // received from the page 
  :pagemode="'form'"
/>
```

## TDD Requirements

**Test First Approach - Write these tests BEFORE implementation:**

```javascript
// frontend/tests/components/Resource/MasterDetail.spec.ts
describe('MasterDetail Component', () => {
  it('should render responsive master-detail layout')
  it('should coordinate List component in master panel')
  it('should show Form component in detail panel for form mode')
  it('should show DocumentView component in detail panel for document mode')
  it('should handle item selection and detail panel updates')
  it('should adapt layout for mobile and desktop screens')
  it('should manage loading states across child components')
  it('should handle component mode switching (form/document)')
})
```

## Component Structure (Self-Contained Coordinator)



- **Props:**
  - `resource` (string, required): The API resource name
  - `mode` (string, required): Component mode ('form' or 'document')
  - `initial-selection` (string|number, optional): Initially selected item ID
  - `split-view` (boolean, optional): Enable side-by-side view on desktop
- **Events:**
  - `selection-changed`: Emitted when item selection changes. Payload: `{ selectedItem: object|null }`

## Architecture (Coordinator Pattern)

```txt
MasterDetail Component (Self-Contained Coordinator)
├── Layout Management
│   ├── Responsive split-pane layout
│   ├── Mobile stacked layout
│   ├── Master panel (List component)
│   └── Detail panel (Form/DocumentView)
├── Child Component Coordination
│   ├── List component integration
│   ├── Form component integration
│   ├── DocumentView component integration
│   └── Loading state coordination
├── Selection Management
│   ├── Item selection state
│   ├── Detail panel updates
│   ├── URL state synchronization
│   └── Navigation history
└── Mode Switching
    ├── Form mode coordination
    ├── Document mode coordination
    └── Dynamic component loading
```

## Implementation Example

```html
<!-- Responsive Master-Detail Layout -->
<div class="master-detail-container">
  <div class="row g-0 h-100">
    <!-- Master Panel -->
    <div 
      class="col-lg-6 border-end"
      :class="{ 'col-12': !selectedItem || isMobile }"
    >
      <div class="master-panel h-100">
        <List
          :resource="resource"
          @item-selected="handleItemSelection"
        />
      </div>
    </div>
    
    <!-- Detail Panel -->
    <div 
      v-if="selectedItem && (splitView || !isMobile)"
      class="col-lg-6"
    >
      <div class="detail-panel h-100">
        <!-- Form Mode -->
        <Form
          v-if="mode === 'form'"
          :resource="resource"
          :resource-id="selectedItem.id"
          @form-saved="handleFormSaved"
        />
        
        <!-- Document Mode -->
        <DocumentView
          v-else-if="mode === 'document'"
          :resource="resource"
          :document-id="selectedItem.id"
          @action-triggered="handleDocumentAction"
        />
      </div>
    </div>
  </div>
  
  <!-- Mobile Detail Modal -->
  <div 
    v-if="selectedItem && isMobile"
    class="modal fade show"
    style="display: block;"
  >
    <div class="modal-dialog modal-fullscreen">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ selectedItem.title || selectedItem.name }}</h5>
          <button type="button" class="btn-close" @click="clearSelection"></button>
        </div>
        <div class="modal-body p-0">
          <!-- Form Mode -->
          <Form
            v-if="mode === 'form'"
            :resource="resource"
            :resource-id="selectedItem.id"
            @form-saved="handleFormSaved"
          />
          
          <!-- Document Mode -->
          <DocumentView
            v-else-if="mode === 'document'"
            :resource="resource"
            :document-id="selectedItem.id"
            @action-triggered="handleDocumentAction"
          />
        </div>
      </div>
    </div>
  </div>
</div>
```

**Bootstrap Classes Used:**
- `row g-0`: Grid layout without gutters
- `col-lg-6`: Responsive column layout
- `border-end`: Visual separation between panels
- `modal-fullscreen`: Mobile detail modal
- `h-100`: Full height containers

---

**Key Features:**
- **Self-Contained Coordination**: Manages child component interactions
- **Responsive Design**: Side-by-side on desktop, modal on mobile
- **Mode Switching**: Dynamically loads Form or DocumentView components
- **URL State Sync**: Maintains selection state in browser URL
- **Component Isolation**: Child components remain self-contained

**API Integration:** Inherits from child components (List, Form, DocumentView)

**Error Handling:** Delegates to child components, coordinates loading states
