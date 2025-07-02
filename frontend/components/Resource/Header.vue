<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
      <div class="d-flex align-items-center flex-grow-1 me-2 gap-2">
        <!-- Filter Component Slot -->
        <div v-if="$slots.filter" class="flex-shrink-0">
          <slot name="filter"></slot>
        </div>
        
        <!-- Search Component replacing title -->
        <div class="w-100" style="max-width: 500px;">
          <slot name="search">
            <!-- Default search placeholder when no search is provided -->
            <div class="input-group">
              <input
                type="text"
                class="form-control"
                placeholder="Search..."
                disabled
                readonly
                :aria-label="`Search ${resourceName.toLowerCase()}`"
              />
              <button 
                type="button" 
                class="btn btn-outline-primary" 
                disabled
                :aria-label="`Search ${resourceName.toLowerCase()}`"
              >
                <i class="bi bi-search" aria-hidden="true"></i>
              </button>
            </div>
          </slot>
        </div>
      </div>
      
      <!-- Action Buttons Area -->
      <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <!-- Custom Actions Slot -->
        <slot name="actions"></slot>
        
        <!-- Default Primary Action -->
        <div v-if="!$slots.actions && !loading" class="d-flex align-items-center gap-2">
          <!-- New Button -->
          <button 
            class="btn btn-primary"
            type="button"
            :aria-label="`Create new ${resourceName.toLowerCase()}`"
            @click="handleEmit('create')"
          >
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">New</span>
          </button>
          
          <!-- Three Dots Menu -->
          <div class="dropdown">
            <button 
              class="btn btn-outline-secondary"
              type="button"
              :id="dropdownId"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              aria-label="More actions"
            >
              <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" :aria-labelledby="dropdownId" role="menu">
              <li role="none">
                <button 
                  class="dropdown-item" 
                  role="menuitem"
                  @click="handleEmit('export')"
                >
                  <i class="bi bi-download me-2" aria-hidden="true"></i>
                  Export Data
                </button>
              </li>
              <li role="none">
                <button 
                  class="dropdown-item" 
                  role="menuitem"
                  @click="handleEmit('import')"
                >
                  <i class="bi bi-upload me-2" aria-hidden="true"></i>
                  Import Data
                </button>
              </li>
            </ul>
          </div>
        </div>
        
        <!-- Loading skeleton for actions -->
        <div v-if="loading" class="placeholder-glow d-flex align-items-center gap-2">
          <span class="placeholder col-4 btn btn-sm"></span>
          <span class="placeholder col-2 btn btn-sm"></span>
        </div>
      </div>
    </div>    
    
    <!-- Results Summary -->
    <div v-if="totalResults !== undefined && totalResults >= 0" class="d-flex justify-content-between align-items-center mb-3">
      <div class="text-muted small">
        <!-- Custom Summary Slot -->
        <slot name="summary" :total="totalResults" :loading="loading" :hasFilters="hasFilters">
          <template v-if="loading">
            <div class="placeholder-glow">
              <span class="placeholder col-4"></span>
            </div>
          </template>
          <template v-else-if="totalResults > 0">
            {{ formatResultsCount(totalResults) }}
            <span v-if="hasFilters" class="badge bg-light text-dark ms-2">
              <i class="bi bi-funnel-fill me-1" aria-hidden="true"></i>
              Filters applied
            </span>
          </template>
          <template v-else>
            No {{ resourceName.toLowerCase() }} found
          </template>
        </slot>
      </div>
      
      <!-- Action Indicators -->
      <div class="d-flex align-items-center gap-2">
        <slot name="indicators"></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title?: string;
  loading?: boolean;
  totalResults?: number;
  hasFilters?: boolean;
  resourceName: string;
  showBreadcrumbs?: boolean;
  from?: number;
  to?: number;
}

interface Emits {
  create: []
  export: []
  import: []
  'bulk-action': [action: string]
}

const props = withDefaults(defineProps<Props>(), {
  title: undefined,
  loading: false,
  totalResults: undefined,
  hasFilters: false,
  showBreadcrumbs: true,
  from: undefined,
  to: undefined
});

const emit = defineEmits<Emits>()

// Generate SSR-safe unique ID for dropdown
const dropdownId = useSSRSafeId('header-actions-dropdown')

// Error handling for emit calls with proper type narrowing
function handleEmit(event: 'create'): void
function handleEmit(event: 'export'): void
function handleEmit(event: 'import'): void
function handleEmit(event: 'create' | 'export' | 'import'): void {
  try {
    switch (event) {
      case 'create':
        emit('create')
        break
      case 'export':
        emit('export')
        break
      case 'import':
        emit('import')
        break
    }
  } catch (error) {
    console.error(`Error emitting ${event} event:`, error)
  }
}

// Format results count with proper pluralization and validation
const formatResultsCount = (total: number) => {
  if (typeof total !== 'number' || total < 0) {
    return `No ${props.resourceName.toLowerCase()} found`
  }
  
  const resourceLower = props.resourceName.toLowerCase()
  const resourcePlural = resourceLower.endsWith('s') ? resourceLower : `${resourceLower}s`
  
  if (props.from !== undefined && props.to !== undefined && total > 0) {
    return `Showing ${props.from} to ${props.to} of ${total} ${total === 1 ? resourceLower : resourcePlural}`
  } else if (total > 0) {
    return `${total} ${total === 1 ? resourceLower : resourcePlural}`
  } else {
    return `No ${resourcePlural} found`
  }
}
</script>