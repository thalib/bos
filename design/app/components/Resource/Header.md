# Header Component Design Specification

The `Header` component is a **self-contained** page header that manages action buttons and coordinates child components. It provides a consistent layout container while allowing child components (Search, Filter) to be self-contained.

**File Location:** `frontend/app/components/Resource/Header.vue`

## TDD Requirements

**Test First Approach - Write these tests BEFORE implementation:**

```javascript
// frontend/tests/components/Resource/Header.spec.ts
describe('Header Component', () => {
  it('should render responsive header layout with Bootstrap 5.3 styling')
  it('should coordinate child components in proper slots')
  it('should handle action button events correctly')
  it('should adapt layout for mobile and desktop screens')
  it('should manage loading states across child components')
  it('should handle export/import actions with API service')
  it('should display component title and action counts')
  it('should be accessible (ARIA labels, keyboard navigation)')
})
```

## Component Structure (Self-Contained)

The component acts as a **coordinator** for self-contained child components:

```html
<Header
  :title="'Products'"
  :resource="'products'"
  :show-create="true"
  :show-export="true"
  @action-triggered="handleAction"
>
  <!-- Child components are passed as slots -->
  <template #search>
    <Search resource="products" @search-applied="handleSearch" />
  </template>
  
  <template #filters>
    <Filter resource="products" @filters-applied="handleFilters" />
  </template>
</Header>
```

- **Props:**
  - `title` (string, required): Page/resource title to display
  - `resource` (string, required): API resource name for actions
  - `show-create` (boolean, optional): Whether to show create button
  - `show-export` (boolean, optional): Whether to show export button
  - `show-import` (boolean, optional): Whether to show import button
  - `loading` (boolean, optional): Global loading state for coordination
- **Events:**
  - `action-triggered`: Emitted when action buttons are clicked. Payload: `{ action: string, data?: any }`

## Internal Architecture

```txt
Header Component (Coordinator)
├── Layout Management
│   ├── Responsive grid layout
│   ├── Action button group
│   ├── Slot coordination
│   └── Mobile adaptation
├── Action Integration
│   ├── Create new resource
│   ├── Export data
│   ├── Import data
│   └── Custom actions
├── Child Component Coordination
│   ├── Search slot management
│   ├── Filter slot management
│   ├── Loading state propagation
│   └── Event aggregation
└── API Integration (useApiService)
    ├── Export operations
    ├── Import operations
    └── Action feedback
```

## Features

- **Coordinator Role**: Provides layout and coordinates self-contained child components
- **Responsive Layout**: Bootstrap 5.3 grid system with mobile-first design
- **Action Management**: Built-in create, export, import actions with API integration
- **Slot-Based Architecture**: Child components use slots for maximum flexibility
- **Loading Coordination**: Manages loading states across child components
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support
- **Mobile Adaptation**: Stacked layout on mobile, horizontal on desktop

## UI Design & Bootstrap 5.3 Implementation

```html
<!-- Responsive Header Layout -->
<div class="header-container">
  <!-- Header Title Row -->
  <div class="row align-items-center mb-3">
    <div class="col">
      <div class="d-flex align-items-center gap-2">
        <h2 class="mb-0">{{ title }}</h2>
        <span v-if="itemCount" class="badge bg-secondary">{{ itemCount }}</span>
        <div v-if="loading" class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="col-auto">
      <div class="btn-group" role="group" aria-label="Page actions">
        <button 
          v-if="showCreate"
          class="btn btn-primary"
          @click="handleCreateAction"
          :disabled="loading"
        >
          <i class="bi bi-plus-lg me-1"></i>
          Create
        </button>
        
        <div class="btn-group" role="group">
          <button
            type="button"
            class="btn btn-outline-secondary dropdown-toggle"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            :disabled="loading"
          >
            <i class="bi bi-three-dots"></i>
          </button>
          <ul class="dropdown-menu">
            <li v-if="showExport">
              <button class="dropdown-item" @click="handleExportAction">
                <i class="bi bi-download me-2"></i>
                Export Data
              </button>
            </li>
            <li v-if="showImport">
              <button class="dropdown-item" @click="handleImportAction">
                <i class="bi bi-upload me-2"></i>
                Import Data
              </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <button class="dropdown-item" @click="handleRefreshAction">
                <i class="bi bi-arrow-clockwise me-2"></i>
                Refresh
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Search and Filter Row -->
  <div class="row g-3 mb-3">
    <!-- Search Column -->
    <div class="col-lg-6">
      <slot name="search">
        <!-- Fallback if no search component provided -->
        <div class="text-muted small">Search not available</div>
      </slot>
    </div>
    
    <!-- Filter Column -->
    <div class="col-lg-6">
      <slot name="filters">
        <!-- Fallback if no filter component provided -->
        <div class="text-muted small">Filters not available</div>
      </slot>
    </div>
  </div>
  
  <!-- Mobile Action Bar (visible on mobile only) -->
  <div class="d-lg-none mb-3">
    <div class="row g-2">
      <div class="col-6" v-if="showCreate">
        <button class="btn btn-primary w-100" @click="handleCreateAction">
          <i class="bi bi-plus-lg"></i>
          Create
        </button>
      </div>
      <div class="col-6">
        <button 
          class="btn btn-outline-secondary w-100"
          @click="showMobileActions = !showMobileActions"
        >
          <i class="bi bi-three-dots"></i>
          More
        </button>
      </div>
    </div>
    
    <!-- Mobile Actions Collapse -->
    <div v-if="showMobileActions" class="mt-2">
      <div class="list-group">
        <button 
          v-if="showExport"
          class="list-group-item list-group-item-action"
          @click="handleExportAction"
        >
          <i class="bi bi-download me-2"></i>
          Export Data
        </button>
        <button 
          v-if="showImport"
          class="list-group-item list-group-item-action"
          @click="handleImportAction"
        >
          <i class="bi bi-upload me-2"></i>
          Import Data
        </button>
        <button 
          class="list-group-item list-group-item-action"
          @click="handleRefreshAction"
        >
          <i class="bi bi-arrow-clockwise me-2"></i>
          Refresh
        </button>
      </div>
    </div>
  </div>
  
  <!-- Status Bar -->
  <div v-if="statusMessage" class="alert alert-info alert-dismissible" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    {{ statusMessage }}
    <button 
      type="button" 
      class="btn-close" 
      @click="clearStatus"
      aria-label="Close"
    ></button>
  </div>
</div>
```

**Bootstrap Classes Used:**
- `row align-items-center`: Header layout with vertical alignment
- `btn-group`: Action button grouping
- `btn btn-primary`, `btn btn-outline-secondary`: Action button styling
- `dropdown-toggle`, `dropdown-menu`: Action dropdown
- `col-lg-6`: Responsive column layout
- `d-lg-none`: Mobile-only visibility
- `list-group`, `list-group-item-action`: Mobile action list
- `alert alert-info`: Status message styling
- `badge bg-secondary`: Item count indicator

## Implementation Rules & DDD Principles

### Domain-Driven Design (DDD)
- **Coordination Domain**: Component focuses on layout coordination and action management
- **Child Component Independence**: Slots allow self-contained child components
- **Action Boundary**: Clear separation between UI actions and business logic

### Technical Requirements
- **API Service**: MUST use `useApiService()` for export/import operations
- **Notifications**: MUST use `useNotifyService()` for action feedback
- **TypeScript**: Strict typing with proper interfaces and type guards
- **Accessibility**: WCAG 2.1 AA compliance (ARIA labels, keyboard navigation)
- **Responsive Design**: Mobile-first approach with Bootstrap 5.3 breakpoints

### Error Handling Strategy

```typescript
// Error Handling Pattern
const handleActionError = (action: string, error: ApiError) => {
  console.error(`[Header] ${action} action failed:`, error)
  
  notifyService.error(
    `Failed to ${action.toLowerCase()}. Please try again.`,
    `${action} Error`
  )
  
  isLoading.value = false
}
```

## Testing Requirements (TDD)

### Unit Tests
```typescript
describe('Header Component TDD Tests', () => {
  beforeEach(() => {
    vi.mocked(useApiService).mockReturnValue(mockApiService)
    vi.mocked(useNotifyService).mockReturnValue(mockNotifyService)
  })

  describe('Layout Coordination', () => {
    it('should render title and action buttons correctly')
    it('should adapt layout for mobile and desktop breakpoints')
    it('should coordinate child component slots properly')
    it('should manage loading states across components')
  })

  describe('Action Handling', () => {
    it('should handle create action button click')
    it('should handle export action with API service')
    it('should handle import action with file selection')
    it('should emit action-triggered events correctly')
  })

  describe('Responsive Behavior', () => {
    it('should show mobile action bar on small screens')
    it('should use dropdown actions on desktop')
    it('should adapt child component layout responsively')
  })

  describe('Child Component Integration', () => {
    it('should provide search slot for Search component')
    it('should provide filters slot for Filter component')
    it('should handle child component events correctly')
  })
})
```

## Implementation Example

```typescript
// frontend/app/components/Resource/Header.vue
<template>
  <div class="header-container">
    <!-- Header Title Row -->
    <div class="row align-items-center mb-3">
      <div class="col">
        <div class="d-flex align-items-center gap-2">
          <h2 class="mb-0">{{ title }}</h2>
          <span v-if="itemCount" class="badge bg-secondary">{{ itemCount }}</span>
          <div v-if="loading" class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      
      <!-- Desktop Actions -->
      <div class="col-auto d-none d-lg-block">
        <div class="btn-group" role="group" aria-label="Page actions">
          <button 
            v-if="showCreate"
            class="btn btn-primary"
            @click="handleCreateAction"
            :disabled="loading"
          >
            <i class="bi bi-plus-lg me-1"></i>
            Create {{ getSingularResource() }}
          </button>
          
          <div class="btn-group" role="group">
            <button
              type="button"
              class="btn btn-outline-secondary dropdown-toggle"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              :disabled="loading"
            >
              Actions
            </button>
            <ul class="dropdown-menu">
              <li v-if="showExport">
                <button class="dropdown-item" @click="handleExportAction">
                  <i class="bi bi-download me-2"></i>
                  Export {{ title }}
                </button>
              </li>
              <li v-if="showImport">
                <button class="dropdown-item" @click="handleImportAction">
                  <i class="bi bi-upload me-2"></i>
                  Import {{ title }}
                </button>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <button class="dropdown-item" @click="handleRefreshAction">
                  <i class="bi bi-arrow-clockwise me-2"></i>
                  Refresh Data
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Search and Filter Row -->
    <div class="row g-3 mb-3">
      <div class="col-lg-6">
        <slot name="search" :loading="loading">
          <div class="text-muted small">Search component not provided</div>
        </slot>
      </div>
      
      <div class="col-lg-6">
        <slot name="filters" :loading="loading">
          <div class="text-muted small">Filter component not provided</div>
        </slot>
      </div>
    </div>
    
    <!-- Mobile Actions -->
    <div class="d-lg-none mb-3">
      <div class="row g-2">
        <div class="col-6" v-if="showCreate">
          <button 
            class="btn btn-primary w-100"
            @click="handleCreateAction"
            :disabled="loading"
          >
            <i class="bi bi-plus-lg me-1"></i>
            Create
          </button>
        </div>
        <div class="col-6">
          <button 
            class="btn btn-outline-secondary w-100"
            @click="toggleMobileActions"
            :disabled="loading"
          >
            <i class="bi bi-three-dots me-1"></i>
            More
          </button>
        </div>
      </div>
      
      <!-- Mobile Actions Collapse -->
      <div v-show="showMobileActions" class="mt-2">
        <div class="list-group">
          <button 
            v-if="showExport"
            class="list-group-item list-group-item-action"
            @click="handleExportAction"
          >
            <i class="bi bi-download me-2"></i>
            Export {{ title }}
          </button>
          <button 
            v-if="showImport"
            class="list-group-item list-group-item-action"
            @click="handleImportAction"
          >
            <i class="bi bi-upload me-2"></i>
            Import {{ title }}
          </button>
          <button 
            class="list-group-item list-group-item-action"
            @click="handleRefreshAction"
          >
            <i class="bi bi-arrow-clockwise me-2"></i>
            Refresh Data
          </button>
        </div>
      </div>
    </div>
    
    <!-- Status Message -->
    <div v-if="statusMessage" class="alert alert-info alert-dismissible" role="alert">
      <i class="bi bi-info-circle me-2"></i>
      {{ statusMessage }}
      <button 
        type="button" 
        class="btn-close" 
        @click="clearStatus"
        aria-label="Close"
      ></button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface Props {
  title: string
  resource: string
  showCreate?: boolean
  showExport?: boolean
  showImport?: boolean
  loading?: boolean
  itemCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  showCreate: true,
  showExport: true,
  showImport: false,
  loading: false,
  itemCount: 0
})

const emit = defineEmits<{
  actionTriggered: [{ action: string; data?: any }]
}>()

// Services
const router = useRouter()
const apiService = useApiService()
const notifyService = useNotifyService()

// Reactive state
const showMobileActions = ref(false)
const statusMessage = ref('')
const isActionLoading = ref(false)

// Computed properties
const getSingularResource = () => {
  return props.resource.slice(0, -1) // Remove 's' from plural
}

// Action handlers
const handleCreateAction = () => {
  router.push(`/list/${props.resource}/create`)
  emit('actionTriggered', { action: 'create' })
}

const handleExportAction = async () => {
  try {
    isActionLoading.value = true
    
    const response = await apiService.request(`/api/v1/${props.resource}/export`, {
      responseType: 'blob'
    })
    
    // Create download link
    const url = window.URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = `${props.resource}-export.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    notifyService.success(`${props.title} exported successfully`)
    emit('actionTriggered', { action: 'export' })
    
  } catch (error) {
    handleActionError('Export', error)
  } finally {
    isActionLoading.value = false
  }
}

const handleImportAction = () => {
  // Create file input for import
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.csv,.xlsx'
  input.onchange = handleFileImport
  input.click()
}

const handleFileImport = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  try {
    isActionLoading.value = true
    
    const formData = new FormData()
    formData.append('file', file)
    
    await apiService.request(`/api/v1/${props.resource}/import`, {
      method: 'POST',
      body: formData,
      headers: {
        // Don't set Content-Type for FormData
      }
    })
    
    notifyService.success(`${props.title} imported successfully`)
    emit('actionTriggered', { action: 'import', data: { filename: file.name } })
    
  } catch (error) {
    handleActionError('Import', error)
  } finally {
    isActionLoading.value = false
  }
}

const handleRefreshAction = () => {
  emit('actionTriggered', { action: 'refresh' })
  notifyService.info('Refreshing data...')
}

const toggleMobileActions = () => {
  showMobileActions.value = !showMobileActions.value
}

const clearStatus = () => {
  statusMessage.value = ''
}

// Error handling
const handleActionError = (action: string, error: any) => {
  console.error(`[Header] ${action} action failed:`, error)
  notifyService.error(`Failed to ${action.toLowerCase()}. Please try again.`, `${action} Error`)
}

// Expose methods for parent component
defineExpose({
  setStatus: (message: string) => {
    statusMessage.value = message
  },
  clearStatus
})
</script>

<style scoped>
.header-container {
  border-bottom: 1px solid var(--bs-border-color);
  padding-bottom: 1rem;
  margin-bottom: 1rem;
}

.clickable-action {
  cursor: pointer;
}

@media (max-width: 991.98px) {
  .btn-group .btn {
    font-size: 0.875rem;
  }
}
</style>
```

---

## Child Component Integration

The Header component coordinates with self-contained child components:

**Search Component Integration:**
```html
<template #search>
  <Search 
    :resource="resource" 
    @search-applied="handleSearchUpdate" 
  />
</template>
```

**Filter Component Integration:**
```html
<template #filters>
  <Filter 
    :resource="resource" 
    @filters-applied="handleFiltersUpdate" 
  />
</template>
```

**API Integration Reference:**
- **Export:** `GET /api/v1/{resource}/export` (blob response)
- **Import:** `POST /api/v1/{resource}/import` (FormData)
- **Error Handling:** All errors handled via `frontend/app/utils/notify.ts`

