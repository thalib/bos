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
        title="Use Ctrl+K to focus this field"
        @keydown="handleKeydown"
        :disabled="disabled || loading"
      />
      <button
        type="button"
        class="btn btn-outline-primary"
        @click="handleSearch"
        :disabled="disabled || loading"
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
import { ref, watch, nextTick } from 'vue'

// Define TypeScript interfaces
interface Props {
  modelValue: string
  loading?: boolean
  placeholder?: string
  disabled?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: string): void
  (e: 'search', query: string): void
  (e: 'clear'): void
}

// Define props and emits
const props = withDefaults(defineProps<Props>(), {
  loading: false,
  placeholder: 'Search...',
  disabled: false
})

const emit = defineEmits<Emits>()

// Component state
const searchInput = ref<HTMLInputElement>()
const searchQuery = ref<string>(props.modelValue)

// Single watcher for two-way binding
watch(() => props.modelValue, (newValue) => {
  searchQuery.value = newValue
})

watch(searchQuery, (newValue) => {
  emit('update:modelValue', newValue)
})

// Handle search action
const handleSearch = () => {
  const trimmedQuery = searchQuery.value.trim()
  if (!trimmedQuery) {
    handleClear()
    return
  }
  
  emit('search', trimmedQuery)
}

// Handle clear action
const handleClear = () => {
  searchQuery.value = ''
  emit('clear')
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