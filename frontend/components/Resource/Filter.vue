<template>
  <div class="dropdown">
    <button
      :id="dropdownId"
      ref="dropdownButton"
      class="btn btn-outline-primary dropdown-toggle"
      type="button"
      data-bs-toggle="dropdown"
      :aria-expanded="isOpen"
      :aria-label="`Filter by status: ${getFilterLabel(modelValue)}`"
      :disabled="loading"
    >
      <i class="bi bi-funnel me-1"></i>
      <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
      <span class="d-none d-sm-inline">{{ getFilterLabel(modelValue) }}</span>
      <span class="d-sm-none">{{ getFilterLabel(modelValue, true) }}</span>
    </button>
    
    <ul 
      class="dropdown-menu" 
      :aria-labelledby="dropdownId"
      role="menu"
    >
      <li role="none">
        <button
          type="button"
          class="dropdown-item"
          :class="{ active: modelValue === 'all' }"
          role="menuitem"
          :aria-pressed="modelValue === 'all'"
          @click="handleFilterChange('all')"
        >
          <i class="bi bi-circle me-2" aria-hidden="true"></i>
          All
        </button>
      </li>
      <li role="none">
        <button
          type="button"
          class="dropdown-item"
          :class="{ active: modelValue === 'active' }"
          role="menuitem"
          :aria-pressed="modelValue === 'active'"
          @click="handleFilterChange('active')"
        >
          <i class="bi bi-check-circle-fill text-success me-2" aria-hidden="true"></i>
          Active
        </button>
      </li>
      <li role="none">
        <button
          type="button"
          class="dropdown-item"
          :class="{ active: modelValue === 'inactive' }"
          role="menuitem"
          :aria-pressed="modelValue === 'inactive'"
          @click="handleFilterChange('inactive')"
        >
          <i class="bi bi-x-circle-fill text-danger me-2" aria-hidden="true"></i>
          Inactive
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

// Types
type FilterValue = 'all' | 'active' | 'inactive'

interface Props {
  modelValue?: FilterValue
  loading?: boolean
}

interface Emits {
  'filter-change': [value: FilterValue]
}

// Props and emits
const props = withDefaults(defineProps<Props>(), {
  modelValue: 'active',
  loading: false
})

const emit = defineEmits<Emits>()

// Reactive state
const dropdownButton = ref<HTMLButtonElement | null>(null)
const isOpen = ref(false)

// Generate SSR-safe unique ID
const dropdownId = useSSRSafeId('filter-dropdown')

// Utility function to get filter labels
const getFilterLabel = (value: FilterValue, short = false) => {
  const labels = {
    all: 'All',
    active: short ? 'Act' : 'Active',
    inactive: short ? 'Inact' : 'Inactive'
  }
  return labels[value] || labels.active
}

// Methods
const handleFilterChange = (value: FilterValue) => {
  if (props.loading) return
  
  try {
    emit('filter-change', value)
  } catch (error) {
    console.error('Error emitting filter change:', error)
  }
}

// Bootstrap dropdown event listeners
const handleDropdownShow = () => {
  isOpen.value = true
}

const handleDropdownHide = () => {
  isOpen.value = false
}

// Lifecycle
onMounted(() => {
  if (dropdownButton.value) {
    dropdownButton.value.addEventListener('show.bs.dropdown', handleDropdownShow)
    dropdownButton.value.addEventListener('hide.bs.dropdown', handleDropdownHide)
  }
})

onUnmounted(() => {
  if (dropdownButton.value) {
    dropdownButton.value.removeEventListener('show.bs.dropdown', handleDropdownShow)
    dropdownButton.value.removeEventListener('hide.bs.dropdown', handleDropdownHide)
  }
})
</script>

<style scoped>
/* Responsive adjustments */
@media (max-width: 576px) {
  .dropdown-menu {
    min-width: 120px;
  }
}
</style>

