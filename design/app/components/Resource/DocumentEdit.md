# DocumentEdit Component Design Specification

The `DocumentEdit` component is a **self-contained** document editor for business documents (estimates, invoices, etc.). It manages its own data fetching, form validation, and save operations with a responsive modal-style interface.

**File Location:** `frontend/app/components/Resource/DocumentEdit.vue`

## TDD Requirements

**Test First Approach - Write these tests BEFORE implementation:**

```javascript
// frontend/tests/components/Resource/DocumentEdit.spec.ts
describe('DocumentEdit Component', () => {
  it('should render modal document editor with Bootstrap 5.3 styling')
  it('should fetch document data on mount if ID provided')
  it('should handle document creation mode when no ID provided')
  it('should validate document fields before saving')
  it('should save document data via API service')
  it('should handle API errors gracefully with notify service')
  it('should support auto-save functionality')
  it('should be accessible (ARIA labels, keyboard navigation)')
})
```

## Component Structure (Self-Contained)

The component requires **minimal props** and handles all document logic internally:

```html
<DocumentEdit
  resource="estimates"
  :document-id="documentId"
  :template="templateName"
  @document-saved="onDocumentSaved"
  @editor-closed="onEditorClosed"
/>
```

- **Props:**
  - `resource` (string, required): The API resource type (estimates, invoices, etc.)
  - `document-id` (string|number, optional): Document ID for editing (null for creation)
  - `template` (string, optional): Document template to use
  - `auto-save` (boolean, optional): Enable auto-save every 30 seconds
- **Events:**
  - `document-saved`: Emitted when document is saved. Payload: `{ document: object, isNew: boolean }`
  - `editor-closed`: Emitted when editor is closed. Payload: `{ hasUnsavedChanges: boolean }`

## Internal Architecture

```txt
DocumentEdit Component (Self-Contained)
├── Document State Management
│   ├── documentData (reactive)
│   ├── formValidation (reactive)
│   ├── isDirty (computed)
│   ├── isLoading (reactive)
│   └── hasError (reactive)
├── API Integration (useApiService)
│   ├── Fetch document data
│   ├── Save/Update operations
│   ├── Template loading
│   └── Automatic error handling
├── Form Management
│   ├── Dynamic field rendering
│   ├── Real-time validation
│   ├── Auto-save functionality
│   └── Dirty state tracking
├── Modal Interface
│   ├── Responsive modal layout
│   ├── Header with title/actions
│   ├── Scrollable body content
│   └── Fixed footer with actions
└── Notification Integration (useNotifyService)
    ├── Save confirmations
    ├── Validation errors
    ├── Auto-save status
    └── Error notifications
```

## Features

- **Self-Contained Logic**: Manages document state, validation, and API operations
- **Modal Interface**: Full-screen modal with responsive design
- **Auto-Save**: Optional auto-save functionality every 30 seconds
- **Form Validation**: Real-time validation with error highlighting
- **Template Support**: Dynamic form rendering based on document templates
- **Dirty State Tracking**: Warns about unsaved changes on close
- **Keyboard Shortcuts**: Save (Ctrl+S), Close (Escape), etc.
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support

## UI Design & Bootstrap 5.3 Implementation

```html
<!-- Full-Screen Modal Document Editor -->
<div class="modal fade" tabindex="-1" aria-labelledby="documentEditTitle">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header border-bottom">
        <div class="d-flex align-items-center">
          <h5 class="modal-title" id="documentEditTitle">
            {{ isCreateMode ? `New ${resourceTitle}` : `Edit ${resourceTitle} #${documentId}` }}
          </h5>
          <span v-if="isDirty" class="badge bg-warning ms-2">Unsaved Changes</span>
          <span v-if="autoSaveEnabled && lastSaved" class="text-muted ms-2 small">
            Auto-saved {{ formatRelativeTime(lastSaved) }}
          </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
          <!-- Auto-save toggle -->
          <div class="form-check form-switch">
            <input 
              class="form-check-input" 
              type="checkbox" 
              id="autoSaveToggle"
              v-model="autoSaveEnabled"
            >
            <label class="form-check-label small" for="autoSaveToggle">
              Auto-save
            </label>
          </div>
          
          <!-- Close button -->
          <button 
            type="button" 
            class="btn-close" 
            @click="handleClose"
            aria-label="Close editor"
          ></button>
        </div>
      </div>
      
      <!-- Modal Body -->
      <div class="modal-body p-0">
        <!-- Loading State -->
        <div v-if="isLoading" class="d-flex justify-content-center align-items-center h-100">
          <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
              <span class="visually-hidden">Loading document...</span>
            </div>
            <p class="text-muted">Loading document...</p>
          </div>
        </div>
        
        <!-- Document Form -->
        <div v-else-if="documentSchema" class="container-fluid py-4">
          <form @submit.prevent="handleSave" novalidate>
            <!-- Form Groups -->
            <div v-for="group in documentSchema" :key="group.name" class="mb-4">
              <div class="card">
                <div class="card-header">
                  <h6 class="card-title mb-0">{{ group.label }}</h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div 
                      v-for="field in group.fields" 
                      :key="field.name"
                      :class="getFieldColumnClass(field)"
                    >
                      <DocumentField
                        :field="field"
                        :value="getFieldValue(field.name)"
                        :error="getFieldError(field.name)"
                        :disabled="isLoading"
                        @input="updateField(field.name, $event)"
                        @blur="validateField(field.name)"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
        
        <!-- Error State -->
        <div v-else-if="hasError" class="d-flex justify-content-center align-items-center h-100">
          <div class="text-center">
            <i class="bi bi-exclamation-triangle display-1 text-danger mb-3"></i>
            <h5 class="text-danger">Failed to load document</h5>
            <p class="text-muted mb-4">Unable to load the document template or data.</p>
            <button class="btn btn-outline-primary" @click="retryLoad">
              <i class="bi bi-arrow-clockwise me-1"></i>
              Try Again
            </button>
          </div>
        </div>
      </div>
      
      <!-- Modal Footer -->
      <div class="modal-footer border-top">
        <div class="d-flex justify-content-between align-items-center w-100">
          <!-- Save Status -->
          <div class="text-muted small">
            <span v-if="isValidating">
              <div class="spinner-border spinner-border-sm me-1" role="status"></div>
              Validating...
            </span>
            <span v-else-if="validationErrors.length > 0" class="text-danger">
              <i class="bi bi-exclamation-triangle me-1"></i>
              {{ validationErrors.length }} validation error(s)
            </span>
            <span v-else-if="isDirty" class="text-warning">
              <i class="bi bi-pencil me-1"></i>
              Unsaved changes
            </span>
            <span v-else class="text-success">
              <i class="bi bi-check-circle me-1"></i>
              All changes saved
            </span>
          </div>
          
          <!-- Action Buttons -->
          <div class="d-flex gap-2">
            <button 
              type="button" 
              class="btn btn-outline-secondary"
              @click="handleClose"
              :disabled="isSaving"
            >
              Cancel
            </button>
            
            <button 
              type="button"
              class="btn btn-primary"
              @click="handleSave"
              :disabled="isSaving || validationErrors.length > 0"
            >
              <div v-if="isSaving" class="spinner-border spinner-border-sm me-1" role="status"></div>
              <i v-else class="bi bi-check-lg me-1"></i>
              {{ isCreateMode ? 'Create' : 'Save' }} {{ resourceTitle }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
```

**Bootstrap Classes Used:**
- `modal-fullscreen`: Full-screen modal for document editing
- `modal-header`, `modal-body`, `modal-footer`: Modal structure
- `card`, `card-header`, `card-body`: Form group containers
- `form-check form-switch`: Auto-save toggle
- `btn btn-primary`, `btn btn-outline-secondary`: Action buttons
- `spinner-border spinner-border-sm`: Loading indicators
- `badge bg-warning`: Unsaved changes indicator
- `text-success`, `text-warning`, `text-danger`: Status colors

## Implementation Rules & DDD Principles

### Domain-Driven Design (DDD)
- **Document Domain**: Component encapsulates all document editing business logic
- **Form Management**: Self-contained validation and state management
- **Template Engine**: Dynamic form rendering based on document types

### Technical Requirements
- **API Service**: MUST use `useApiService()` from `frontend/app/utils/api.ts`
- **Notifications**: MUST use `useNotifyService()` from `frontend/app/utils/notify.ts`
- **TypeScript**: Strict typing with proper interfaces and type guards
- **Accessibility**: WCAG 2.1 AA compliance (ARIA labels, keyboard navigation)
- **Performance**: Debounced auto-save, efficient form updates

### Error Handling Strategy

```typescript
// Error Handling Pattern
const handleDocumentError = (operation: string, error: ApiError) => {
  console.error(`[DocumentEdit] ${operation} failed:`, error)
  
  notifyService.error(
    `Failed to ${operation.toLowerCase()} document. Please try again.`,
    `${operation} Error`
  )
  
  isSaving.value = false
  isLoading.value = false
  
  // Offer specific recovery options
  if (operation === 'save' && isDirty.value) {
    notifyService.info('Your changes are preserved. You can try saving again.')
  }
}
```

## Testing Requirements (TDD)

### Unit Tests
```typescript
describe('DocumentEdit Component TDD Tests', () => {
  beforeEach(() => {
    vi.mocked(useApiService).mockReturnValue(mockApiService)
    vi.mocked(useNotifyService).mockReturnValue(mockNotifyService)
  })

  describe('Document Loading', () => {
    it('should fetch document data when ID provided')
    it('should load document template/schema')
    it('should handle create mode when no ID provided')
    it('should display loading state during data fetch')
  })

  describe('Form Management', () => {
    it('should render form fields based on document schema')
    it('should validate fields in real-time')
    it('should track dirty state correctly')
    it('should handle field updates and validation')
  })

  describe('Save Operations', () => {
    it('should save document via API service')
    it('should handle validation errors before saving')
    it('should show loading state during save')
    it('should emit document-saved event on success')
  })

  describe('Auto-Save', () => {
    it('should auto-save every 30 seconds when enabled')
    it('should not auto-save if no changes made')
    it('should pause auto-save during manual save')
  })

  describe('Modal Behavior', () => {
    it('should warn about unsaved changes on close')
    it('should handle keyboard shortcuts (Ctrl+S, Escape)')
    it('should emit editor-closed event with proper payload')
  })
})
```

## Implementation Example

```typescript
// frontend/app/components/Resource/DocumentEdit.vue
<template>
  <div class="modal fade show" style="display: block;" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header border-bottom">
          <div class="d-flex align-items-center">
            <h5 class="modal-title">
              {{ isCreateMode ? `New ${resourceTitle}` : `Edit ${resourceTitle} #${documentId}` }}
            </h5>
            <span v-if="isDirty" class="badge bg-warning ms-2">Unsaved Changes</span>
            <span v-if="autoSaveEnabled && lastSaved" class="text-muted ms-2 small">
              Auto-saved {{ formatRelativeTime(lastSaved) }}
            </span>
          </div>
          
          <div class="d-flex align-items-center gap-2">
            <div class="form-check form-switch">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="autoSaveToggle"
                v-model="autoSaveEnabled"
              >
              <label class="form-check-label small" for="autoSaveToggle">
                Auto-save
              </label>
            </div>
            
            <button 
              type="button" 
              class="btn-close" 
              @click="handleClose"
              aria-label="Close editor"
            ></button>
          </div>
        </div>
        
        <!-- Body -->
        <div class="modal-body p-0">
          <div v-if="isLoading" class="d-flex justify-content-center align-items-center h-100">
            <div class="text-center">
              <div class="spinner-border text-primary mb-3" role="status"></div>
              <p class="text-muted">Loading document...</p>
            </div>
          </div>
          
          <div v-else-if="documentSchema" class="container-fluid py-4">
            <form @submit.prevent="handleSave" novalidate>
              <div v-for="group in documentSchema" :key="group.name" class="mb-4">
                <div class="card">
                  <div class="card-header">
                    <h6 class="card-title mb-0">{{ group.label }}</h6>
                  </div>
                  <div class="card-body">
                    <div class="row g-3">
                      <div 
                        v-for="field in group.fields" 
                        :key="field.name"
                        :class="getFieldColumnClass(field)"
                      >
                        <DocumentField
                          :field="field"
                          :value="getFieldValue(field.name)"
                          :error="getFieldError(field.name)"
                          :disabled="isLoading"
                          @input="updateField(field.name, $event)"
                          @blur="validateField(field.name)"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
          
          <div v-else-if="hasError" class="d-flex justify-content-center align-items-center h-100">
            <div class="text-center">
              <i class="bi bi-exclamation-triangle display-1 text-danger mb-3"></i>
              <h5 class="text-danger">Failed to load document</h5>
              <p class="text-muted mb-4">Unable to load the document template or data.</p>
              <button class="btn btn-outline-primary" @click="retryLoad">
                <i class="bi bi-arrow-clockwise me-1"></i>
                Try Again
              </button>
            </div>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="modal-footer border-top">
          <div class="d-flex justify-content-between align-items-center w-100">
            <div class="text-muted small">
              <span v-if="isValidating">
                <div class="spinner-border spinner-border-sm me-1" role="status"></div>
                Validating...
              </span>
              <span v-else-if="validationErrors.length > 0" class="text-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                {{ validationErrors.length }} validation error(s)
              </span>
              <span v-else-if="isDirty" class="text-warning">
                <i class="bi bi-pencil me-1"></i>
                Unsaved changes
              </span>
              <span v-else class="text-success">
                <i class="bi bi-check-circle me-1"></i>
                All changes saved
              </span>
            </div>
            
            <div class="d-flex gap-2">
              <button 
                type="button" 
                class="btn btn-outline-secondary"
                @click="handleClose"
                :disabled="isSaving"
              >
                Cancel
              </button>
              
              <button 
                type="button"
                class="btn btn-primary"
                @click="handleSave"
                :disabled="isSaving || validationErrors.length > 0"
              >
                <div v-if="isSaving" class="spinner-border spinner-border-sm me-1" role="status"></div>
                <i v-else class="bi bi-check-lg me-1"></i>
                {{ isCreateMode ? 'Create' : 'Save' }} {{ resourceTitle }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'
import DocumentField from './DocumentField.vue'

interface DocumentField {
  name: string
  label: string
  type: string
  required?: boolean
  validation?: any
  columnClass?: string
}

interface DocumentGroup {
  name: string
  label: string
  fields: DocumentField[]
}

interface Props {
  resource: string
  documentId?: string | number
  template?: string
  autoSave?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  autoSave: true
})

const emit = defineEmits<{
  documentSaved: [{ document: any; isNew: boolean }]
  editorClosed: [{ hasUnsavedChanges: boolean }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()

// Reactive state
const documentData = ref<Record<string, any>>({})
const documentSchema = ref<DocumentGroup[]>([])
const validationErrors = ref<Record<string, string>>({})
const isLoading = ref(false)
const isSaving = ref(false)
const isValidating = ref(false)
const hasError = ref(false)
const autoSaveEnabled = ref(props.autoSave)
const lastSaved = ref<Date | null>(null)
const originalData = ref<Record<string, any>>({})

// Computed properties
const isCreateMode = computed(() => !props.documentId)
const resourceTitle = computed(() => 
  props.resource.charAt(0).toUpperCase() + props.resource.slice(1, -1)
)

const isDirty = computed(() => 
  JSON.stringify(documentData.value) !== JSON.stringify(originalData.value)
)

// Auto-save timer
let autoSaveTimer: NodeJS.Timeout | null = null

// Initialize component
onMounted(() => {
  loadDocumentData()
  setupKeyboardShortcuts()
  if (autoSaveEnabled.value) {
    startAutoSave()
  }
})

onUnmounted(() => {
  stopAutoSave()
  removeKeyboardShortcuts()
})

const loadDocumentData = async () => {
  try {
    isLoading.value = true
    hasError.value = false
    
    // Load document schema/template
    const schemaResponse = await apiService.get(`${props.resource}/schema`, props.template || 'default')
    documentSchema.value = schemaResponse.data?.schema || []
    
    if (props.documentId) {
      // Load existing document
      const docResponse = await apiService.get(props.resource, props.documentId)
      documentData.value = docResponse.data || {}
      originalData.value = { ...documentData.value }
    } else {
      // Initialize new document with defaults
      initializeNewDocument()
    }
    
  } catch (error) {
    handleDocumentError('Load', error)
  } finally {
    isLoading.value = false
  }
}

const handleSave = async () => {
  if (isValidating.value || isSaving.value) return
  
  try {
    isSaving.value = true
    
    // Validate before saving
    await validateAllFields()
    if (Object.keys(validationErrors.value).length > 0) {
      notifyService.warning('Please fix validation errors before saving')
      return
    }
    
    // Save document
    let response
    if (isCreateMode.value) {
      response = await apiService.create(props.resource, documentData.value)
    } else {
      response = await apiService.update(props.resource, props.documentId!, documentData.value)
    }
    
    // Update state
    originalData.value = { ...documentData.value }
    lastSaved.value = new Date()
    
    notifyService.success(
      `${resourceTitle.value} ${isCreateMode.value ? 'created' : 'saved'} successfully`
    )
    
    emit('documentSaved', {
      document: response.data,
      isNew: isCreateMode.value
    })
    
  } catch (error) {
    handleDocumentError('Save', error)
  } finally {
    isSaving.value = false
  }
}

const handleClose = () => {
  if (isDirty.value) {
    const confirmed = confirm('You have unsaved changes. Are you sure you want to close?')
    if (!confirmed) return
  }
  
  emit('editorClosed', { hasUnsavedChanges: isDirty.value })
}

// Auto-save functionality
const startAutoSave = () => {
  autoSaveTimer = setInterval(() => {
    if (isDirty.value && !isSaving.value && !isValidating.value) {
      autoSaveDocument()
    }
  }, 30000) // 30 seconds
}

const stopAutoSave = () => {
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer)
    autoSaveTimer = null
  }
}

const autoSaveDocument = async () => {
  if (!isDirty.value || isCreateMode.value) return
  
  try {
    await apiService.update(props.resource, props.documentId!, documentData.value)
    originalData.value = { ...documentData.value }
    lastSaved.value = new Date()
  } catch (error) {
    console.warn('[DocumentEdit] Auto-save failed:', error)
  }
}

// Field management
const updateField = (fieldName: string, value: any) => {
  documentData.value[fieldName] = value
  // Clear validation error when field is updated
  if (validationErrors.value[fieldName]) {
    delete validationErrors.value[fieldName]
  }
}

const validateField = async (fieldName: string) => {
  // Implement field validation logic
  const field = findField(fieldName)
  if (field?.required && !documentData.value[fieldName]) {
    validationErrors.value[fieldName] = `${field.label} is required`
  }
}

const validateAllFields = async () => {
  isValidating.value = true
  validationErrors.value = {}
  
  for (const group of documentSchema.value) {
    for (const field of group.fields) {
      await validateField(field.name)
    }
  }
  
  isValidating.value = false
}

// Helper methods
const getFieldValue = (fieldName: string) => documentData.value[fieldName]
const getFieldError = (fieldName: string) => validationErrors.value[fieldName]
const getFieldColumnClass = (field: DocumentField) => field.columnClass || 'col-md-6'

const findField = (fieldName: string): DocumentField | undefined => {
  for (const group of documentSchema.value) {
    const field = group.fields.find(f => f.name === fieldName)
    if (field) return field
  }
}

// Keyboard shortcuts
const setupKeyboardShortcuts = () => {
  document.addEventListener('keydown', handleKeyboardShortcuts)
}

const removeKeyboardShortcuts = () => {
  document.removeEventListener('keydown', handleKeyboardShortcuts)
}

const handleKeyboardShortcuts = (event: KeyboardEvent) => {
  if (event.ctrlKey && event.key === 's') {
    event.preventDefault()
    handleSave()
  } else if (event.key === 'Escape') {
    handleClose()
  }
}

// Error handling
const handleDocumentError = (operation: string, error: any) => {
  console.error(`[DocumentEdit] ${operation} failed:`, error)
  hasError.value = true
  notifyService.error(`Failed to ${operation.toLowerCase()} document. Please try again.`)
}

// Watch auto-save setting
watch(autoSaveEnabled, (enabled) => {
  if (enabled) {
    startAutoSave()
  } else {
    stopAutoSave()
  }
})
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-body {
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}

.form-check-input:checked {
  background-color: var(--bs-success);
  border-color: var(--bs-success);
}
</style>
```

---

## API Integration Reference

**Endpoints Used:**
- **Schema:** `GET /api/v1/{resource}/schema/{template}`
- **Load:** `GET /api/v1/{resource}/{id}`
- **Create:** `POST /api/v1/{resource}`
- **Update:** `PUT /api/v1/{resource}/{id}`

**Response Structure:** Follows `design/api/index.md` standards

**Error Handling:** All errors handled via `frontend/app/utils/notify.ts`