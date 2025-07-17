<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
      <div class="d-flex align-items-center flex-grow-1 me-2 gap-2">
        <!-- Title Section -->
        <div v-if="showResourceTitle" class="flex-shrink-0">
          <slot name="title">
            <div class="d-flex align-items-center">
              <h4 class="mb-0 me-2">{{ resourceTitle }}</h4>
              <span v-if="resourceCount !== undefined" class="badge bg-secondary">
                {{ resourceCount }}
              </span>
            </div>
          </slot>
        </div>
        
        <!-- Filter Component Slot -->
        <div v-if="showFilters && $slots.filters" class="flex-shrink-0">
          <slot name="filters"></slot>
        </div>
        
        <!-- Search Component -->
        <div v-if="showSearch" class="w-100" style="max-width: 500px;">
          <slot name="search">
            <!-- Default search placeholder when no search is provided -->
            <div class="input-group">
              <input
                type="text"
                class="form-control"
                placeholder="Search..."
                disabled
                readonly
                :aria-label="`Search ${resourceTitle.toLowerCase()}`"
              />
              <button 
                type="button" 
                class="btn btn-outline-primary" 
                disabled
                :aria-label="`Search ${resourceTitle.toLowerCase()}`"
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
        
        <!-- Default Actions -->
        <div v-if="showActions && !$slots.actions && !loading" class="d-flex align-items-center gap-2">
          <!-- Create Button -->
          <button 
            class="btn btn-primary"
            type="button"
            :aria-label="`Create new ${resourceTitle.toLowerCase()}`"
            @click="handleEmit('action-create')"
          >
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
            <span class="d-none d-sm-inline">New</span>
          </button>
          
          <!-- Additional Actions Dropdown -->
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
                  @click="handleEmit('action-import')"
                >
                  <i class="bi bi-upload me-2" aria-hidden="true"></i>
                  Import Data
                </button>
              </li>
              <li role="none">
                <button 
                  class="dropdown-item" 
                  role="menuitem"
                  @click="handleEmit('action-export')"
                >
                  <i class="bi bi-download me-2" aria-hidden="true"></i>
                  Export Data
                </button>
              </li>
              <li role="none">
                <button 
                  class="dropdown-item" 
                  role="menuitem"
                  @click="handleEmit('action-custom', { action: 'refresh' })"
                >
                  <i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>
                  Refresh
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
  </div>
</template>

<script setup lang="ts">
// Props based on design specification
interface Props {
  /** Title of the resource being managed */
  resourceTitle: string
  /** Total count of resources */
  resourceCount?: number
  /** Loading state for the component */
  loading?: boolean
  /** Whether to show action buttons */
  showActions?: boolean
  /** Whether to show filter section */
  showFilters?: boolean
  /** Whether to show search section */
  showSearch?: boolean
  /** Whether to show resource title */
  showResourceTitle?: boolean
}

// Events based on design specification
interface Emits {
  /** Emitted when create action is triggered */
  (event: 'action-create'): void
  /** Emitted when import action is triggered */
  (event: 'action-import'): void
  /** Emitted when export action is triggered */
  (event: 'action-export'): void
  /** Emitted for custom actions */
  (event: 'action-custom', payload: { action: string; data?: any }): void
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  showActions: true,
  showFilters: true,
  showSearch: true,
  showResourceTitle: true
})

const emit = defineEmits<Emits>()

// Generate SSR-safe unique ID for dropdown
const dropdownId = useSSRSafeId('header-actions-dropdown')

// Error handling for emit calls with proper type narrowing
function handleEmit(event: 'action-create'): void
function handleEmit(event: 'action-import'): void  
function handleEmit(event: 'action-export'): void
function handleEmit(event: 'action-custom', payload: { action: string; data?: any }): void
function handleEmit(event: 'action-create' | 'action-import' | 'action-export' | 'action-custom', payload?: { action: string; data?: any }): void {
  try {
    switch (event) {
      case 'action-create':
        emit('action-create')
        break
      case 'action-import':
        emit('action-import')
        break
      case 'action-export':
        emit('action-export')
        break
      case 'action-custom':
        if (payload) {
          emit('action-custom', payload)
        }
        break
    }
  } catch (error) {
    console.error(`Error emitting ${event} event:`, error)
  }
}
</script>