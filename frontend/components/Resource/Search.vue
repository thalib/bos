<template>
  <div>
    <div class="input-group">
      <input
        ref="searchInput"
        v-model="searchQuery"
        type="text"
        class="form-control"
        :placeholder="placeholder"
        aria-label="Search"
        :title="`Use Ctrl+K to focus this field${minLength > 0 ? ` (minimum ${minLength} characters)` : ''}`"
        @keydown="handleKeydown"
        @input="handleInput"
        :disabled="disabled || loading"
      />
      <button
        type="button"
        class="btn btn-outline-primary"
        @click="handleSearch"
        :disabled="disabled || loading || !canSearch"
        aria-label="Search"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        <i v-else class="bi bi-search"></i>
      </button>
      <button
        v-if="searchQuery"
        type="button"
        class="btn btn-outline-secondary"
        @click="handleClear"
        :disabled="disabled || loading"
        aria-label="Clear search"
      >
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    
    <!-- Search Results Info -->
    <div v-if="searchQuery && !loading" class="mt-2">
      <small class="text-muted">
        <slot name="search-info" :query="searchQuery">
          <!-- Default search info can be overridden by parent -->
        </slot>
      </small>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'

// Define TypeScript interfaces based on design specification
interface Props {
  /** Current search query from API response */
  search: string | null
  /** Loading state for the component */
  loading?: boolean
  /** Whether the search is disabled */
  disabled?: boolean
  /** Placeholder text for search input */
  placeholder?: string
  /** Debounce delay in milliseconds */
  debounceMs?: number
  /** Minimum search length */
  minLength?: number
}

interface Emits {
  /** Emitted when search query changes */
  (e: 'search-change', payload: { query: string }): void
  /** Emitted when search is cleared */
  (e: 'search-clear'): void
  /** Emitted when search is explicitly submitted */
  (e: 'search-submit', payload: { query: string }): void
}

// Define props and emits based on design specification
const props = withDefaults(defineProps<Props>(), {
  loading: false,
  disabled: false,
  placeholder: 'Search...',
  debounceMs: 300,
  minLength: 2
})

const emit = defineEmits<Emits>()

// Component state
const searchInput = ref<HTMLInputElement>()
const searchQuery = ref<string>(props.search || '')
const debounceTimer = ref<NodeJS.Timeout>()

// Computed properties
const canSearch = computed(() => {
  return searchQuery.value.trim().length >= props.minLength || searchQuery.value.trim().length === 0
})

// Watch for props changes
watch(() => props.search, (newValue) => {
  searchQuery.value = newValue || ''
})

// Handle input with debounce
const handleInput = () => {
  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }
  
  debounceTimer.value = setTimeout(() => {
    const trimmedQuery = searchQuery.value.trim()
    if (trimmedQuery.length >= props.minLength) {
      emit('search-change', { query: trimmedQuery })
    } else if (trimmedQuery.length === 0) {
      emit('search-clear')
    }
  }, props.debounceMs)
}

// Handle search action
const handleSearch = () => {
  const trimmedQuery = searchQuery.value.trim()
  if (!trimmedQuery || trimmedQuery.length < props.minLength) {
    if (trimmedQuery.length === 0) {
      handleClear()
    }
    return
  }
  
  // Clear debounce timer
  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }
  
  emit('search-submit', { query: trimmedQuery })
}

// Handle clear action
const handleClear = () => {
  searchQuery.value = ''
  
  // Clear debounce timer
  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }
  
  emit('search-clear')
}

// Handle keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    event.preventDefault()
    handleSearch()
  } else if (event.key === 'Escape') {
    event.preventDefault()
    handleClear()
  }
}

// Focus method for external access
const focus = async () => {
  await nextTick()
  if (searchInput.value) {
    searchInput.value.focus()
    searchInput.value.select()
  }
}

// Expose methods for parent component
defineExpose({
  focus
})
</script>