# Search Component Design Specification

The `Search` component is a **self-contained** search interface that integrates directly with the API service to handle search operations. It manages its own state and URL synchronization to provide a seamless search experience.

**File Location:** `frontend/app/components/Resource/Search.vue`

## TDD Requirements

**Test First Approach - Write these tests BEFORE implementation:**

```javascript
// frontend/tests/components/Resource/Search.spec.ts
describe('Search Component', () => {
  it('should render search input with proper Bootstrap 5.3 styling')
  it('should debounce search input for 300ms by default')
  it('should validate minimum search length (2 characters)')
  it('should update URL parameters when search changes')
  it('should handle API errors gracefully with notify service')
  it('should display loading state during search operations')
  it('should clear search and reset URL when clear button clicked')
  it('should be accessible (ARIA labels, keyboard navigation)')
})
```

## Component Structure (Self-Contained)

The component requires **minimal props** and handles all search logic internally:

```html
<Search
  resource="products"
  :initial-search="$route.query.search"
  @search-applied="onSearchUpdate"
/>
```

- **Props:**
  - `resource` (string, required): The API resource to search within
  - `initial-search` (string, optional): Initial search value from URL/route
- **Events:**
  - `search-applied`: Emitted when search is applied. Payload: `{ search: string, hasResults: boolean }`

## Internal Architecture

```txt
Search Component (Self-Contained)
├── Internal State Management
│   ├── searchQuery (reactive)
│   ├── isLoading (reactive)
│   └── hasError (reactive)
├── API Integration (useApiService)
│   ├── Debounced search requests
│   ├── Automatic error handling
│   └── Loading state management
├── URL State Sync
│   ├── Read initial value from route.query.search
│   ├── Update URL when search changes
│   └── Browser back/forward support
└── Notification Integration (useNotifyService)
    ├── Search validation warnings
    ├── Error notifications
    └── Success feedback
```

## Features

- **Self-Contained Logic**: Manages its own search state, API calls, and URL synchronization
- **API Integration**: Uses `useApiService()` for all HTTP requests with automatic error handling
- **Real-time Search**: Debounced input (300ms) with minimum 2-character validation
- **URL State Management**: Automatically syncs search state with browser URL for bookmarking
- **Bootstrap 5.3 Styling**: Responsive search input with proper form controls
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support
- **Error Resilience**: Graceful degradation when API is unavailable

## UI Design & Bootstrap 5.3 Implementation

```html
<!-- Mobile-First Responsive Design -->
<div class="search-container">
  <!-- Input Group with Bootstrap 5.3 -->
  <div class="input-group mb-3">
    <input 
      type="search" 
      class="form-control" 
      placeholder="Search resources..."
      aria-label="Search"
      aria-describedby="search-help"
    >
    <button class="btn btn-outline-secondary" type="button" aria-label="Clear search">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
  
  <!-- Search Status -->
  <div class="d-flex justify-content-between align-items-center text-muted small">
    <span id="search-help">Search results update as you type</span>
    <span class="badge bg-light text-dark" v-if="searchQuery">
      {{ resultCount }} results
    </span>
  </div>
</div>
```

**Bootstrap Classes Used:**
- `input-group`: Combines input with action buttons
- `form-control`: Standard Bootstrap form input styling
- `btn btn-outline-secondary`: Clear button styling
- `d-flex`, `justify-content-between`: Layout utilities
- `badge bg-light text-dark`: Result count indicator
- `text-muted small`: Helper text styling

## Implementation Rules & DDD Principles

### Domain-Driven Design (DDD)
- **Search Domain**: Component encapsulates all search-related business logic
- **API Boundary**: Clear separation between UI and API concerns
- **State Management**: Self-contained reactive state following Vue 3 patterns

### Technical Requirements
- **API Service**: MUST use `useApiService()` from `frontend/app/utils/api.ts`
- **Notifications**: MUST use `useNotifyService()` from `frontend/app/utils/notify.ts`
- **TypeScript**: Strict typing with proper interfaces and type guards
- **Accessibility**: WCAG 2.1 AA compliance (ARIA labels, keyboard navigation)
- **Performance**: Debounced API calls, efficient re-rendering with computed properties

### Error Handling Strategy

```typescript
// Error Handling Pattern
const handleSearchError = (error: ApiError) => {
  // Log error for debugging
  console.error('[Search] API error:', error)
  
  // Show user-friendly notification
  notifyService.error(
    'Search temporarily unavailable. Please try again.',
    'Search Error'
  )
  
  // Reset loading state
  isLoading.value = false
  
  // Optionally fall back to cached results
  if (cachedResults.value.length > 0) {
    notifyService.info('Showing cached search results')
  }
}
```

## Testing Requirements (TDD)

### Unit Tests
```typescript
// Test Coverage Requirements
describe('Search Component TDD Tests', () => {
  beforeEach(() => {
    // Mock API service
    vi.mocked(useApiService).mockReturnValue(mockApiService)
    vi.mocked(useNotifyService).mockReturnValue(mockNotifyService)
  })

  describe('Initialization', () => {
    it('should initialize with empty search when no route query')
    it('should initialize with route query search parameter')
    it('should apply proper Bootstrap 5.3 classes')
    it('should set correct ARIA attributes for accessibility')
  })

  describe('Search Behavior', () => {
    it('should debounce search input for 300ms')
    it('should not search with less than 2 characters')
    it('should update URL parameters when search changes')
    it('should call API service with correct parameters')
    it('should handle API errors with notify service')
  })

  describe('State Management', () => {
    it('should manage loading state during API calls')
    it('should clear search and reset URL')
    it('should emit search-applied event with correct payload')
  })

  describe('Accessibility', () => {
    it('should support keyboard navigation')
    it('should announce search results to screen readers')
    it('should have proper focus management')
  })
})
```

### Integration Tests
```typescript
describe('Search Integration', () => {
  it('should integrate with real API service')
  it('should sync with browser URL correctly')
  it('should work with router navigation')
})
```

## Implementation Example

```typescript
// frontend/app/components/Resource/Search.vue
<template>
  <div class="search-container">
    <div class="input-group mb-3">
      <input
        v-model="searchQuery"
        type="search"
        class="form-control"
        :class="{ 'is-invalid': hasError }"
        placeholder="Search resources..."
        aria-label="Search"
        aria-describedby="search-help"
        :disabled="isLoading"
        @keyup.enter="performSearch"
      >
      <button
        v-if="searchQuery"
        class="btn btn-outline-secondary"
        type="button"
        aria-label="Clear search"
        @click="clearSearch"
      >
        <i class="bi bi-x-lg"></i>
      </button>
      <button
        v-if="isLoading"
        class="btn btn-outline-secondary"
        type="button"
        disabled
      >
        <div class="spinner-border spinner-border-sm" role="status">
          <span class="visually-hidden">Searching...</span>
        </div>
      </button>
    </div>
    
    <div class="d-flex justify-content-between align-items-center text-muted small">
      <span id="search-help">Search results update as you type</span>
      <span v-if="searchQuery && !isLoading" class="badge bg-light text-dark">
        {{ resultCount }} results found
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'
import { debounce } from 'lodash-es'

// Props with minimal interface
interface Props {
  resource: string
  initialSearch?: string
}

const props = withDefaults(defineProps<Props>(), {
  initialSearch: ''
})

// Events
const emit = defineEmits<{
  searchApplied: [{ search: string; hasResults: boolean }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()
const route = useRoute()
const router = useRouter()

// Reactive state
const searchQuery = ref(props.initialSearch || route.query.search as string || '')
const isLoading = ref(false)
const hasError = ref(false)
const resultCount = ref(0)

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
  if (query.length < 2 && query.length > 0) {
    notifyService.warning('Search term must be at least 2 characters')
    return
  }
  
  if (!query) {
    updateUrl('')
    emit('searchApplied', { search: '', hasResults: false })
    return
  }
  
  try {
    isLoading.value = true
    hasError.value = false
    
    const response = await apiService.fetch(props.resource, { search: query })
    resultCount.value = response.pagination?.totalItems || 0
    
    updateUrl(query)
    emit('searchApplied', { search: query, hasResults: resultCount.value > 0 })
    
  } catch (error) {
    hasError.value = true
    handleSearchError(error)
  } finally {
    isLoading.value = false
  }
}, 300)

// URL synchronization
const updateUrl = (search: string) => {
  const query = { ...route.query }
  if (search) {
    query.search = search
  } else {
    delete query.search
  }
  
  router.replace({ query })
}

// Clear search
const clearSearch = () => {
  searchQuery.value = ''
  updateUrl('')
  emit('searchApplied', { search: '', hasResults: false })
}

// Watch for search changes
watch(searchQuery, debouncedSearch)

// Error handling
const handleSearchError = (error: any) => {
  console.error('[Search] API error:', error)
  notifyService.error('Search temporarily unavailable. Please try again.', 'Search Error')
}
</script>
```

---

## API Integration Reference

**Endpoint Used:** `GET /api/v1/{resource}?search={query}`

**Response Structure:** Follows `design/api/index.md` standards

**Error Handling:** All errors handled via `frontend/app/utils/notify.ts`

**URL State:** Automatically syncs with route query parameters for bookmarking support
