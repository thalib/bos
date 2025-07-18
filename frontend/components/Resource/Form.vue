<template>
  <div>
    <!-- Form title -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">
        {{ formTitle }}
      </h5>
    </div>
    
    <!-- Loading state -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 mb-0">Loading data...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="internalError" class="alert alert-danger">
      <h5 class="alert-heading">Error loading data</h5>
      <p class="mb-2">{{ internalError.message }}</p>
      
      <!-- Show validation errors if available -->
      <div v-if="internalError.validationErrors && Object.keys(internalError.validationErrors).length > 0">
        <h6>Validation Errors:</h6>
        <ul class="mb-2">
          <li v-for="(messages, field) in internalError.validationErrors" :key="field">
            <strong>{{ field }}:</strong> {{ Array.isArray(messages) ? messages[0] : messages }}
          </li>
        </ul>
      </div>
      
      <button class="btn btn-outline-danger" @click="loadForm">
        Try Again
      </button>
    </div>

    <!-- Form content -->
    <template v-else-if="schema">
      <form @submit.prevent="handleSubmit">
        <!-- Grouped schema rendering -->
        <div v-for="(group, groupIdx) in normalizedSchema" :key="`group-${groupIdx}`" class="mb-5">
          <!-- Group header - only show if schema was originally grouped -->
          <div v-if="isGroupedSchema" class="border-bottom border-2 border-primary mb-4">
            <h5 class="fw-bold text-primary mb-2 d-flex align-items-center">
              <i class="bi bi-folder me-2"></i>
              {{ group.group }}
            </h5>
          </div>
          
          <!-- Group fields -->
          <div class="row g-3">
            <div v-for="[fieldName, field] in getFieldEntries(group.fields)" :key="`${groupIdx}-${fieldName}`" class="col-12">
              <div class="row align-items-start">
                <div class="col-sm-3">
                  <label :for="fieldName" class="col-form-label fw-medium">
                    {{ field.label }}
                    <span v-if="field.required" class="text-danger ms-1">*</span>
                  </label>
                </div>
                <div class="col-sm-9">
                  <FormField 
                    :field-name="fieldName"
                    :field="field"
                    :model-value="formData[fieldName]"
                    :validation-error="validationErrors[fieldName]"
                    @update:model-value="updateFieldValue(fieldName, $event)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form actions -->
        <div class="border-top pt-3">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
              {{ mode === 'create' ? 'Create' : 'Update' }}
            </button>
            <button type="button" class="btn btn-secondary" @click="handleCancel">
              Cancel
            </button>
            <button v-if="mode === 'edit'" type="button" class="btn btn-outline-secondary" @click="handleReset">
              Reset
            </button>
          </div>
        </div>
      </form>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import FormField from '~/components/Form/FormField.vue'
import { useApiCrud } from '~/services/apiCrud'

// Props definition based on form.md documentation
interface FieldSchema {
  label: string
  type: string
  required?: boolean
  default?: any
  placeholder?: string
  options?: Array<{ value: any; label: string }>
  [key: string]: any
}

interface GroupSchema {
  group: string
  fields: Record<string, FieldSchema>
}

interface Props {
  id?: number | null
  schema?: GroupSchema[] | Record<string, FieldSchema> | any
  loading?: boolean
  mode?: 'create' | 'edit' | 'view'
}

const props = withDefaults(defineProps<Props>(), {
  id: null,
  schema: null,
  loading: false,
  mode: 'create'
})

// Events definition based on form.md documentation
const emit = defineEmits<{
  'form-submit': [payload: { data: object; mode: string }]
  'form-cancel': []
  'form-reset': []
  'form-error': [payload: { errors: object }]
}>()

// Reactive state
const formData = ref<Record<string, any>>({})
const validationErrors = ref<Record<string, string[]>>({})
const isSubmitting = ref(false)
const internalError = ref<any>(null)

// CRUD service
const { apiGet } = useApiCrud()

// Computed properties
const formTitle = computed(() => {
  if (props.id === null) {
    return `Create ${getResourceName()}`
  }
  // For edit mode, we would show the resource name if available in formData
  return formData.value.name || `${getResourceName()} #${props.id}`
})

const isGroupedSchema = computed(() => {
  return props.schema && Array.isArray(props.schema) && 
         props.schema.length > 0 && 
         props.schema.every(item => 
           item && typeof item === 'object' && 
           'group' in item && 'fields' in item
         )
})

const normalizedSchema = computed((): GroupSchema[] => {
  if (!props.schema) return []
  
  if (isGroupedSchema.value) {
    return props.schema as GroupSchema[]
  }
  
  // If schema is not grouped, we need to handle different formats
  // The schema might be passed as a direct fields object
  let fields: Record<string, FieldSchema> = {}
  
  if (typeof props.schema === 'object' && !Array.isArray(props.schema)) {
    // Direct fields object
    fields = props.schema as Record<string, FieldSchema>
  } else if (Array.isArray(props.schema) && props.schema.length > 0) {
    // Array format - could be grouped or ungrouped
    const firstItem = props.schema[0]
    if (firstItem && typeof firstItem === 'object' && 'fields' in firstItem) {
      // It's actually grouped but didn't pass the grouped check
      return props.schema as GroupSchema[]
    } else {
      // It's an array of fields or a fields object
      fields = firstItem as Record<string, FieldSchema> || {}
    }
  }
  
  return [{
    group: 'Form Fields',
    fields: fields
  }]
})

// Helper function to get resource name (simplified for now)
const getResourceName = () => {
  // This could be enhanced to derive from route or passed as prop
  return 'Resource'
}

// Helper function to get field entries with proper typing
const getFieldEntries = (fields: Record<string, FieldSchema>): [string, FieldSchema][] => {
  return Object.entries(fields) as [string, FieldSchema][]
}

// Initialize form data based on schema
const initializeFormData = () => {
  if (!props.schema) return
  
  const initialData: Record<string, any> = {}
  
  normalizedSchema.value.forEach((group: GroupSchema) => {
    Object.entries(group.fields).forEach(([fieldName, field]: [string, FieldSchema]) => {
      initialData[fieldName] = field.default !== undefined ? field.default : null
    })
  })
  
  formData.value = initialData
}

// Load existing data for edit mode
const loadForm = async () => {
  if (props.id !== null) {
    try {
      // This would need to be enhanced to work with actual resource endpoints
      // For now, we'll emit an error to indicate this needs to be handled by parent
      emit('form-error', { errors: { general: ['Form data loading needs to be implemented by parent component'] } })
    } catch (error: any) {
      internalError.value = error
      emit('form-error', { errors: { general: [error.message] } })
    }
  }
}

// Update field value
const updateFieldValue = (fieldName: string, value: any) => {
  formData.value[fieldName] = value
  // Clear validation error for this field
  if (validationErrors.value[fieldName]) {
    delete validationErrors.value[fieldName]
  }
}

// Handle form submission
const handleSubmit = async () => {
  // Clear previous validation errors
  validationErrors.value = {}
  isSubmitting.value = true
  
  try {
    // Basic client-side validation
    const errors: Record<string, string[]> = {}
    
    normalizedSchema.value.forEach((group: GroupSchema) => {
      Object.entries(group.fields).forEach(([fieldName, field]: [string, FieldSchema]) => {
        if (field.required && (!formData.value[fieldName] || formData.value[fieldName] === '')) {
          errors[fieldName] = [`${field.label} is required`]
        }
      })
    })
    
    if (Object.keys(errors).length > 0) {
      validationErrors.value = errors
      emit('form-error', { errors })
      return
    }
    
    // Emit form submission event
    emit('form-submit', {
      data: { ...formData.value },
      mode: props.mode || 'create'
    })
    
  } catch (error: any) {
    const errorMessage = error.message || 'Form submission failed'
    validationErrors.value = { general: [errorMessage] }
    emit('form-error', { errors: validationErrors.value })
  } finally {
    isSubmitting.value = false
  }
}

// Handle form cancel
const handleCancel = () => {
  emit('form-cancel')
}

// Handle form reset
const handleReset = () => {
  initializeFormData()
  validationErrors.value = {}
  emit('form-reset')
}

// Watch for schema changes
watch(() => props.schema, () => {
  if (props.schema) {
    initializeFormData()
  }
}, { immediate: true })

// Watch for id changes
watch(() => props.id, () => {
  if (props.id !== null) {
    loadForm()
  }
}, { immediate: true })

// Initialize on mount
onMounted(() => {
  if (props.schema) {
    initializeFormData()
  }
  if (props.id !== null) {
    loadForm()
  }
})
</script>

<style scoped>
/* Custom form styling - only where Bootstrap doesn't cover */
.form-control,
.form-select {
  border: none;
  border-bottom: 2px solid var(--bs-primary);
  border-radius: 0;
  box-shadow: none;
}

.form-control:focus,
.form-select:focus {
  border-bottom-color: var(--bs-primary);
  box-shadow: 0 1px 0 0 var(--bs-primary);
}

/* Disabled/readonly states */
.form-control[readonly],
.form-select[disabled] {
  border-bottom-color: var(--bs-secondary);
  background-color: transparent;
  opacity: 0.6;
}

.form-check-input[disabled] {
  opacity: 0.6;
}
</style>
