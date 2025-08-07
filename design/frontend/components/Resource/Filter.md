# Filter Component Design Specification

The `Filter` component is a **self-contained** filtering interface that integrates directly with the API service to manage filter operations. It automatically fetches available filters and manages applied filter state with URL synchronization.

**File Location:** `frontend/app/components/Resource/Filter.vue`

## TDD Requirements

**Test First Approach - Write these tests BEFORE implementation:**

```javascript
// frontend/tests/components/Resource/Filter.spec.ts
describe('Filter Component', () => {
  it('should fetch available filters on mount')
  it('should render filter dropdowns with Bootstrap 5.3 styling')
  it('should update URL parameters when filters change')
  it('should display applied filters as removable badges')
  it('should handle API errors gracefully with notify service')
  it('should clear all filters and reset URL')
  it('should support multiple filter selection')
  it('should be accessible (ARIA labels, keyboard navigation)')
})
```

## Component Structure (Self-Contained)

The component requires **minimal props** and handles all filtering logic internally:

```html
<Filter
  resource="products"
  :initial-filters="$route.query"
  @filters-applied="onFiltersUpdate"
/>
```

- **Props:**
  - `resource` (string, required): The API resource to filter
  - `initial-filters` (object, optional): Initial filter values from URL/route
- **Events:**
  - `filters-applied`: Emitted when filters change. Payload: `{ filters: object, hasActiveFilters: boolean }`

## Internal Architecture

```txt
Filter Component (Self-Contained)
├── Internal State Management
│   ├── availableFilters (reactive)
│   ├── appliedFilters (reactive)
│   ├── isLoading (reactive)
│   └── hasError (reactive)
├── API Integration (useApiService)
│   ├── Fetch available filters on mount
│   ├── Apply filters with API calls
│   └── Automatic error handling
├── URL State Sync
│   ├── Read initial filters from route.query
│   ├── Update URL when filters change
│   └── Browser back/forward support
└── Notification Integration (useNotifyService)
    ├── Filter application feedback
    ├── Error notifications
    └── Clear filter confirmations
```

## Features

- **Self-Contained Logic**: Manages its own filter state, API calls, and URL synchronization
- **Dynamic Filter Loading**: Automatically fetches available filters from API
- **Multi-Filter Support**: Handles multiple simultaneous filters
- **URL State Management**: Syncs filter state with browser URL for bookmarking
- **Bootstrap 5.3 UI**: Responsive dropdowns and filter badges
- **Real-time Application**: Immediate filter application with loading states
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support

## UI Design & Bootstrap 5.3 Implementation

```html
<!-- Responsive Filter Interface -->
<div class="filter-container">
  <!-- Filter Dropdowns Row -->
  <div class="row g-2 mb-3">
    <div class="col-md-3" v-for="filter in availableFilters" :key="filter.field">
      <select 
        class="form-select"
        :aria-label="`Filter by ${filter.label}`"
        v-model="appliedFilters[filter.field]"
      >
        <option value="">All {{ filter.label }}</option>
        <option 
          v-for="option in filter.values" 
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
    </div>
    
    <!-- Clear Filters Button -->
    <div class="col-md-3 d-flex align-items-end">
      <button 
        v-if="hasActiveFilters"
        class="btn btn-outline-secondary w-100"
        @click="clearAllFilters"
      >
        <i class="bi bi-x-circle me-1"></i>
        Clear Filters
      </button>
    </div>
  </div>
  
  <!-- Applied Filters Badges -->
  <div v-if="hasActiveFilters" class="d-flex flex-wrap gap-2 mb-3">
    <span class="text-muted small">Active filters:</span>
    <badge
      v-for="(value, field) in appliedFilters"
      :key="field"
      class="badge bg-primary d-flex align-items-center gap-1"
    >
      {{ getFilterLabel(field) }}: {{ value }}
      <button 
        class="btn-close btn-close-white btn-sm"
        @click="removeFilter(field)"
        :aria-label="`Remove ${field} filter`"
      ></button>
    </badge>
  </div>
  
  <!-- Filter Status -->
  <div class="text-muted small">
    <span v-if="isLoading">
      <div class="spinner-border spinner-border-sm me-1" role="status"></div>
      Applying filters...
    </span>
    <span v-else-if="hasActiveFilters">
      {{ filteredCount }} items match current filters
    </span>
  </div>
</div>
```

**Bootstrap Classes Used:**
- `form-select`: Standard Bootstrap dropdown styling
- `row g-2`: Grid layout with gap
- `btn btn-outline-secondary`: Clear button styling
- `badge bg-primary`: Applied filter badges
- `d-flex flex-wrap gap-2`: Flexible badge layout
- `btn-close btn-close-white`: Remove filter buttons
- `spinner-border spinner-border-sm`: Loading indicator

## Implementation Rules & DDD Principles

### Domain-Driven Design (DDD)
- **Filter Domain**: Component encapsulates all filter-related business logic
- **API Boundary**: Clear separation between UI and API concerns
- **State Management**: Self-contained reactive state with computed derived values

### Technical Requirements
- **API Service**: MUST use `useApiService()` from `frontend/app/utils/api.ts`
- **Notifications**: MUST use `useNotifyService()` from `frontend/app/utils/notify.ts`
- **TypeScript**: Strict typing with proper interfaces and type guards
- **Accessibility**: WCAG 2.1 AA compliance (ARIA labels, keyboard navigation)
- **Performance**: Efficient re-rendering with computed properties and watchers

### Error Handling Strategy

```typescript
// Error Handling Pattern
const handleFilterError = (error: ApiError) => {
  console.error('[Filter] API error:', error)
  
  notifyService.error(
    'Filter options temporarily unavailable. Please try again.',
    'Filter Error'
  )
  
  isLoading.value = false
  hasError.value = true
  
  // Fallback to cached filters if available
  if (cachedFilters.value.length > 0) {
    availableFilters.value = cachedFilters.value
    notifyService.info('Using cached filter options')
  }
}
```

## Testing Requirements (TDD)

### Unit Tests
```typescript
describe('Filter Component TDD Tests', () => {
  beforeEach(() => {
    vi.mocked(useApiService).mockReturnValue(mockApiService)
    vi.mocked(useNotifyService).mockReturnValue(mockNotifyService)
  })

  describe('Initialization', () => {
    it('should fetch available filters on component mount')
    it('should initialize with route query filter parameters')
    it('should apply proper Bootstrap 5.3 classes')
    it('should set correct ARIA attributes for accessibility')
  })

  describe('Filter Operations', () => {
    it('should apply filter and update URL parameters')
    it('should remove individual filters')
    it('should clear all filters and reset URL')
    it('should handle multiple simultaneous filters')
    it('should call API service with correct filter parameters')
  })

  describe('State Management', () => {
    it('should manage loading state during filter operations')
    it('should display applied filters as removable badges')
    it('should emit filters-applied event with correct payload')
    it('should maintain filter state during navigation')
  })

  describe('Error Handling', () => {
    it('should handle API errors with notify service')
    it('should fallback to cached filters when API fails')
    it('should recover gracefully from network errors')
  })
})
```

## Implementation Example

```typescript
// frontend/app/components/Resource/Filter.vue
<template>
  <div class="filter-container">
    <!-- Filter Dropdowns -->
    <div class="row g-2 mb-3">
      <div 
        class="col-md-3" 
        v-for="filter in availableFilters" 
        :key="filter.field"
      >
        <select 
          class="form-select"
          :class="{ 'is-invalid': hasError }"
          :aria-label="`Filter by ${filter.label}`"
          v-model="appliedFilters[filter.field]"
          :disabled="isLoading"
        >
          <option value="">All {{ filter.label }}</option>
          <option 
            v-for="option in filter.values" 
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>
      
      <div class="col-md-3 d-flex align-items-end">
        <button 
          v-if="hasActiveFilters"
          class="btn btn-outline-secondary w-100"
          :disabled="isLoading"
          @click="clearAllFilters"
        >
          <i class="bi bi-x-circle me-1"></i>
          Clear Filters
        </button>
      </div>
    </div>
    
    <!-- Applied Filter Badges -->
    <div v-if="hasActiveFilters" class="d-flex flex-wrap gap-2 mb-3">
      <span class="text-muted small">Active filters:</span>
      <span
        v-for="(value, field) in activeFilters"
        :key="field"
        class="badge bg-primary d-flex align-items-center gap-1"
      >
        {{ getFilterLabel(field) }}: {{ value }}
        <button 
          class="btn-close btn-close-white btn-sm"
          @click="removeFilter(field)"
          :aria-label="`Remove ${field} filter`"
        ></button>
      </span>
    </div>
    
    <!-- Status Display -->
    <div class="text-muted small">
      <span v-if="isLoading">
        <div class="spinner-border spinner-border-sm me-1" role="status"></div>
        Applying filters...
      </span>
      <span v-else-if="hasActiveFilters">
        {{ filteredCount }} items match current filters
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface FilterOption {
  value: string
  label: string
}

interface AvailableFilter {
  field: string
  label: string
  values: FilterOption[]
}

interface Props {
  resource: string
  initialFilters?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
  initialFilters: () => ({})
})

const emit = defineEmits<{
  filtersApplied: [{ filters: Record<string, string>; hasActiveFilters: boolean }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()
const route = useRoute()
const router = useRouter()

// Reactive state
const availableFilters = ref<AvailableFilter[]>([])
const appliedFilters = ref<Record<string, string>>({})
const isLoading = ref(false)
const hasError = ref(false)
const filteredCount = ref(0)

// Computed properties
const hasActiveFilters = computed(() => 
  Object.values(appliedFilters.value).some(value => value !== '')
)

const activeFilters = computed(() => 
  Object.fromEntries(
    Object.entries(appliedFilters.value).filter(([_, value]) => value !== '')
  )
)

// Initialize filters from URL
onMounted(() => {
  initializeFilters()
  fetchAvailableFilters()
})

const initializeFilters = () => {
  const routeFilters = { ...route.query }
  delete routeFilters.page // Don't include pagination
  delete routeFilters.search // Don't include search
  
  appliedFilters.value = { 
    ...props.initialFilters, 
    ...routeFilters 
  } as Record<string, string>
}

const fetchAvailableFilters = async () => {
  try {
    isLoading.value = true
    hasError.value = false
    
    const response = await apiService.fetch(props.resource, { page: 1, per_page: 1 })
    
    if (response.filters?.available) {
      availableFilters.value = response.filters.available
    }
  } catch (error) {
    handleFilterError(error)
  } finally {
    isLoading.value = false
  }
}

const applyFilters = async () => {
  try {
    isLoading.value = true
    
    const filterParams = { ...activeFilters.value }
    const response = await apiService.fetch(props.resource, filterParams)
    
    filteredCount.value = response.pagination?.totalItems || 0
    updateUrl(filterParams)
    
    emit('filtersApplied', { 
      filters: filterParams, 
      hasActiveFilters: hasActiveFilters.value 
    })
    
  } catch (error) {
    handleFilterError(error)
  } finally {
    isLoading.value = false
  }
}

const removeFilter = (field: string) => {
  appliedFilters.value[field] = ''
  applyFilters()
}

const clearAllFilters = () => {
  appliedFilters.value = {}
  updateUrl({})
  emit('filtersApplied', { filters: {}, hasActiveFilters: false })
}

const updateUrl = (filters: Record<string, string>) => {
  const query = { ...route.query }
  
  // Remove existing filter parameters
  Object.keys(query).forEach(key => {
    if (!['page', 'search', 'sort', 'dir'].includes(key)) {
      delete query[key]
    }
  })
  
  // Add new filter parameters
  Object.entries(filters).forEach(([key, value]) => {
    if (value) {
      query[key] = value
    }
  })
  
  router.replace({ query })
}

const getFilterLabel = (field: string): string => {
  return availableFilters.value.find(f => f.field === field)?.label || field
}

// Watch for filter changes
watch(appliedFilters, applyFilters, { deep: true })

// Error handling
const handleFilterError = (error: any) => {
  console.error('[Filter] API error:', error)
  hasError.value = true
  notifyService.error('Filter options temporarily unavailable.', 'Filter Error')
}
</script>
```

---

## API Integration Reference

**Endpoint Used:** `GET /api/v1/{resource}?filter={field}:{value}`

**Response Structure:** Uses `filters.available` and `filters.applied` from `design/api/index.md`

**Error Handling:** All errors handled via `frontend/app/utils/notify.ts`

**URL State:** Automatically syncs with route query parameters for bookmarking support
