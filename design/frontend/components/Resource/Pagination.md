# Pagination Component Design Specification

The `Pagination` component is a **self-contained** pagination interface that integrates directly with the API service to handle page navigation and items-per-page selection. It manages its own state and URL synchronization for seamless navigation.

**File Location:** `frontend/app/components/Resource/Pagination.vue`

## TDD Requirements

**Test First Approach - Write these tests BEFORE implementation:**

```javascript
// frontend/tests/components/Resource/Pagination.spec.ts
describe('Pagination Component', () => {
  it('should render pagination controls with Bootstrap 5.3 styling')
  it('should handle page navigation correctly')
  it('should update URL parameters when page changes')
  it('should handle per-page selection (15, 50, 100)')
  it('should display current page info and total items')
  it('should handle API errors gracefully with notify service')
  it('should disable navigation at boundaries (first/last page)')
  it('should be accessible (ARIA labels, keyboard navigation)')
})
```

## Component Structure (Self-Contained)

The component requires **minimal props** and handles all pagination logic internally:

```html
<Pagination
  resource="products"
  :initial-page="$route.query.page"
  :initial-per-page="$route.query.per_page"
  @page-changed="onPageUpdate"
/>
```

- **Props:**
  - `resource` (string, required): The API resource to paginate
  - `initial-page` (number, optional): Initial page number from URL/route
  - `initial-per-page` (number, optional): Initial items per page from URL/route
- **Events:**
  - `page-changed`: Emitted when pagination changes. Payload: `{ page: number, perPage: number, totalItems: number }`

## Internal Architecture

```txt
Pagination Component (Self-Contained)
├── Internal State Management
│   ├── currentPage (reactive)
│   ├── itemsPerPage (reactive)
│   ├── totalItems (reactive)
│   ├── totalPages (computed)
│   ├── isLoading (reactive)
│   └── hasError (reactive)
├── API Integration (useApiService)
│   ├── Fetch paginated data
│   ├── Handle page navigation
│   └── Automatic error handling
├── URL State Sync
│   ├── Read initial values from route.query
│   ├── Update URL when pagination changes
│   └── Browser back/forward support
└── Notification Integration (useNotifyService)
    ├── Page navigation feedback
    ├── Error notifications
    └── Per-page change confirmations
```

## Features

- **Self-Contained Logic**: Manages its own pagination state, API calls, and URL synchronization
- **Dynamic Page Calculation**: Automatically calculates total pages and navigation boundaries
- **Per-Page Selection**: Dropdown for 15, 50, 100 items per page
- **URL State Management**: Syncs pagination state with browser URL for bookmarking
- **Bootstrap 5.3 UI**: Responsive pagination controls with proper styling
- **Boundary Handling**: Disables navigation at first/last pages
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support
- **Loading States**: Visual feedback during page transitions

## UI Design & Bootstrap 5.3 Implementation

```html
<!-- Responsive Pagination Interface -->
<div class="pagination-container">
  <!-- Pagination Info -->
  <div class="row align-items-center">
    <div class="col-md-6">
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted small">Show</span>
        <select 
          class="form-select form-select-sm"
          style="width: auto;"
          v-model="itemsPerPage"
          :disabled="isLoading"
        >
          <option value="15">15</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="text-muted small">items per page</span>
      </div>
    </div>
    
    <div class="col-md-6 text-md-end">
      <span class="text-muted small">
        Showing {{ startItem }}-{{ endItem }} of {{ totalItems }} items
      </span>
    </div>
  </div>
  
  <!-- Pagination Controls -->
  <nav aria-label="Page navigation" class="mt-3">
    <ul class="pagination justify-content-center mb-0">
      <!-- First Page -->
      <li class="page-item" :class="{ disabled: currentPage === 1 || isLoading }">
        <button 
          class="page-link"
          @click="goToPage(1)"
          :disabled="currentPage === 1 || isLoading"
          aria-label="First page"
        >
          <i class="bi bi-chevron-double-left"></i>
        </button>
      </li>
      
      <!-- Previous Page -->
      <li class="page-item" :class="{ disabled: currentPage === 1 || isLoading }">
        <button 
          class="page-link"
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1 || isLoading"
          aria-label="Previous page"
        >
          <i class="bi bi-chevron-left"></i>
        </button>
      </li>
      
      <!-- Page Numbers -->
      <li 
        v-for="page in visiblePages" 
        :key="page"
        class="page-item"
        :class="{ active: page === currentPage, disabled: isLoading }"
      >
        <button 
          class="page-link"
          @click="goToPage(page)"
          :disabled="isLoading"
          :aria-label="`Page ${page}`"
          :aria-current="page === currentPage ? 'page' : undefined"
        >
          {{ page }}
        </button>
      </li>
      
      <!-- Next Page -->
      <li class="page-item" :class="{ disabled: currentPage === totalPages || isLoading }">
        <button 
          class="page-link"
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages || isLoading"
          aria-label="Next page"
        >
          <i class="bi bi-chevron-right"></i>
        </button>
      </li>
      
      <!-- Last Page -->
      <li class="page-item" :class="{ disabled: currentPage === totalPages || isLoading }">
        <button 
          class="page-link"
          @click="goToPage(totalPages)"
          :disabled="currentPage === totalPages || isLoading"
          aria-label="Last page"
        >
          <i class="bi bi-chevron-double-right"></i>
        </button>
      </li>
    </ul>
  </nav>
  
  <!-- Loading State -->
  <div v-if="isLoading" class="text-center mt-2">
    <div class="spinner-border spinner-border-sm text-primary" role="status">
      <span class="visually-hidden">Loading page...</span>
    </div>
  </div>
</div>
```

**Bootstrap Classes Used:**
- `pagination justify-content-center`: Centered pagination controls
- `page-item`, `page-link`: Standard Bootstrap pagination styling
- `form-select form-select-sm`: Per-page dropdown
- `row align-items-center`: Layout for info and controls
- `text-muted small`: Helper text styling
- `disabled`, `active`: State classes
- `spinner-border spinner-border-sm`: Loading indicator

## Implementation Rules & DDD Principles

### Domain-Driven Design (DDD)
- **Pagination Domain**: Component encapsulates all pagination-related business logic
- **API Boundary**: Clear separation between UI and API concerns  
- **State Management**: Self-contained reactive state with computed derived values

### Technical Requirements
- **API Service**: MUST use `useApiService()` from `frontend/app/utils/api.ts`
- **Notifications**: MUST use `useNotifyService()` from `frontend/app/utils/notify.ts`
- **TypeScript**: Strict typing with proper interfaces and type guards
- **Accessibility**: WCAG 2.1 AA compliance (ARIA labels, keyboard navigation)
- **Performance**: Efficient re-rendering with computed properties and debounced navigation

### Error Handling Strategy

```typescript
// Error Handling Pattern
const handlePaginationError = (error: ApiError) => {
  console.error('[Pagination] API error:', error)
  
  notifyService.error(
    'Page navigation temporarily unavailable. Please try again.',
    'Pagination Error'
  )
  
  isLoading.value = false
  hasError.value = true
  
  // Revert to previous valid page if navigation fails
  if (previousPage.value !== currentPage.value) {
    currentPage.value = previousPage.value
    notifyService.info('Reverted to previous page')
  }
}
```

## Testing Requirements (TDD)

### Unit Tests
```typescript
describe('Pagination Component TDD Tests', () => {
  beforeEach(() => {
    vi.mocked(useApiService).mockReturnValue(mockApiService)
    vi.mocked(useNotifyService).mockReturnValue(mockNotifyService)
  })

  describe('Initialization', () => {
    it('should initialize with route query page parameters')
    it('should fetch first page data on mount')
    it('should apply proper Bootstrap 5.3 classes')
    it('should calculate total pages correctly')
  })

  describe('Navigation', () => {
    it('should navigate to specific page')
    it('should handle first/previous page navigation')
    it('should handle next/last page navigation')
    it('should disable navigation at boundaries')
    it('should update URL parameters when page changes')
  })

  describe('Per-Page Selection', () => {
    it('should change items per page and reset to page 1')
    it('should update URL with new per_page parameter')
    it('should recalculate total pages when per_page changes')
  })

  describe('State Management', () => {
    it('should manage loading state during navigation')
    it('should emit page-changed event with correct payload')
    it('should maintain pagination state during navigation')
  })

  describe('Accessibility', () => {
    it('should have proper ARIA labels for navigation')
    it('should support keyboard navigation')
    it('should announce page changes to screen readers')
  })
})
```

## Implementation Example

```typescript
// frontend/app/components/Resource/Pagination.vue
<template>
  <div class="pagination-container">
    <!-- Per-page selector and info -->
    <div class="row align-items-center mb-3">
      <div class="col-md-6">
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small">Show</span>
          <select 
            class="form-select form-select-sm"
            style="width: auto;"
            v-model="itemsPerPage"
            :disabled="isLoading"
            @change="changePerPage"
          >
            <option value="15">15</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="text-muted small">items per page</span>
        </div>
      </div>
      
      <div class="col-md-6 text-md-end">
        <span class="text-muted small">
          Showing {{ startItem }}-{{ endItem }} of {{ totalItems }} items
        </span>
      </div>
    </div>
    
    <!-- Pagination controls -->
    <nav aria-label="Page navigation">
      <ul class="pagination justify-content-center mb-0">
        <!-- First page -->
        <li class="page-item" :class="{ disabled: isFirstPage || isLoading }">
          <button 
            class="page-link"
            @click="goToPage(1)"
            :disabled="isFirstPage || isLoading"
            aria-label="First page"
          >
            <i class="bi bi-chevron-double-left"></i>
          </button>
        </li>
        
        <!-- Previous page -->
        <li class="page-item" :class="{ disabled: isFirstPage || isLoading }">
          <button 
            class="page-link"
            @click="goToPage(currentPage - 1)"
            :disabled="isFirstPage || isLoading"
            aria-label="Previous page"
          >
            <i class="bi bi-chevron-left"></i>
          </button>
        </li>
        
        <!-- Page numbers -->
        <li 
          v-for="page in visiblePages" 
          :key="page"
          class="page-item"
          :class="{ active: page === currentPage, disabled: isLoading }"
        >
          <button 
            class="page-link"
            @click="goToPage(page)"
            :disabled="isLoading"
            :aria-label="`Page ${page}`"
            :aria-current="page === currentPage ? 'page' : undefined"
          >
            {{ page }}
          </button>
        </li>
        
        <!-- Next page -->
        <li class="page-item" :class="{ disabled: isLastPage || isLoading }">
          <button 
            class="page-link"
            @click="goToPage(currentPage + 1)"
            :disabled="isLastPage || isLoading"
            aria-label="Next page"
          >
            <i class="bi bi-chevron-right"></i>
          </button>
        </li>
        
        <!-- Last page -->
        <li class="page-item" :class="{ disabled: isLastPage || isLoading }">
          <button 
            class="page-link"
            @click="goToPage(totalPages)"
            :disabled="isLastPage || isLoading"
            aria-label="Last page"
          >
            <i class="bi bi-chevron-double-right"></i>
          </button>
        </li>
      </ul>
    </nav>
    
    <!-- Loading indicator -->
    <div v-if="isLoading" class="text-center mt-2">
      <div class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="visually-hidden">Loading page...</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface Props {
  resource: string
  initialPage?: number
  initialPerPage?: number
}

const props = withDefaults(defineProps<Props>(), {
  initialPage: 1,
  initialPerPage: 15
})

const emit = defineEmits<{
  pageChanged: [{ page: number; perPage: number; totalItems: number }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()
const route = useRoute()
const router = useRouter()

// Reactive state
const currentPage = ref(props.initialPage)
const itemsPerPage = ref(props.initialPerPage)
const totalItems = ref(0)
const isLoading = ref(false)
const hasError = ref(false)
const previousPage = ref(1)

// Computed properties
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value))
const isFirstPage = computed(() => currentPage.value === 1)
const isLastPage = computed(() => currentPage.value === totalPages.value)
const startItem = computed(() => (currentPage.value - 1) * itemsPerPage.value + 1)
const endItem = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalItems.value))

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, currentPage.value + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Initialize from URL
onMounted(() => {
  initializeFromUrl()
  fetchPageData()
})

const initializeFromUrl = () => {
  const page = parseInt(route.query.page as string) || props.initialPage
  const perPage = parseInt(route.query.per_page as string) || props.initialPerPage
  
  currentPage.value = Math.max(1, page)
  itemsPerPage.value = [15, 50, 100].includes(perPage) ? perPage : 15
}

const fetchPageData = async () => {
  try {
    isLoading.value = true
    hasError.value = false
    previousPage.value = currentPage.value
    
    const params = {
      page: currentPage.value,
      per_page: itemsPerPage.value,
      ...getAdditionalParams()
    }
    
    const response = await apiService.fetch(props.resource, params)
    
    if (response.pagination) {
      totalItems.value = response.pagination.totalItems
      
      // Validate current page is within bounds
      if (currentPage.value > totalPages.value && totalPages.value > 0) {
        currentPage.value = totalPages.value
        return fetchPageData() // Retry with corrected page
      }
    }
    
    updateUrl()
    emit('pageChanged', {
      page: currentPage.value,
      perPage: itemsPerPage.value,
      totalItems: totalItems.value
    })
    
  } catch (error) {
    handlePaginationError(error)
  } finally {
    isLoading.value = false
  }
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
    currentPage.value = page
    fetchPageData()
  }
}

const changePerPage = () => {
  currentPage.value = 1 // Reset to first page when changing per-page
  fetchPageData()
}

const updateUrl = () => {
  const query = { ...route.query }
  
  query.page = currentPage.value.toString()
  query.per_page = itemsPerPage.value.toString()
  
  router.replace({ query })
}

const getAdditionalParams = () => {
  const params: Record<string, any> = {}
  
  // Include other query parameters (search, filters, etc.)
  Object.entries(route.query).forEach(([key, value]) => {
    if (!['page', 'per_page'].includes(key) && value) {
      params[key] = value
    }
  })
  
  return params
}

// Error handling
const handlePaginationError = (error: any) => {
  console.error('[Pagination] API error:', error)
  hasError.value = true
  
  notifyService.error('Page navigation failed. Please try again.', 'Pagination Error')
  
  // Revert to previous page
  if (previousPage.value !== currentPage.value) {
    currentPage.value = previousPage.value
  }
}

// Watch for external page changes
watch(() => route.query.page, (newPage) => {
  const page = parseInt(newPage as string) || 1
  if (page !== currentPage.value) {
    currentPage.value = page
    fetchPageData()
  }
})
</script>
```

---

## API Integration Reference

**Endpoint Used:** `GET /api/v1/{resource}?page={page}&per_page={perPage}`

**Response Structure:** Uses `pagination` object from `design/api/index.md`

**Error Handling:** All errors handled via `frontend/app/utils/notify.ts`

**URL State:** Automatically syncs page and per_page with route query parameters
