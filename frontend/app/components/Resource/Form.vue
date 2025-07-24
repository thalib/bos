<template>
  <div class="resource-form">
    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <div class="d-flex justify-content-center align-items-center py-5">
        <div class="text-center">
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading form...</span>
          </div>
          <p class="text-muted">{{ isEdit ? 'Loading data...' : 'Loading form...' }}</p>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="error-state">
      <div class="alert alert-danger text-center">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Failed to load form data.
        <button class="btn btn-link p-0 ms-2" @click="retryLoad">
          Try again
        </button>
      </div>
    </div>

    <!-- Form Content -->
    <div v-else class="form-content">
      <!-- Form Header -->
      <div class="form-header border-bottom pb-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h4 class="mb-1">
              {{ isEdit ? `Edit ${resourceTitle}` : `New ${resourceTitle}` }}
            </h4>
            <p class="text-muted mb-0" v-if="isEdit && formData.id">
              ID: {{ formData.id }}
            </p>
          </div>
          <div class="form-mode-indicator">
            <span class="badge" :class="getModeClass()">
              {{ mode.toUpperCase() }}
            </span>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" novalidate>
        <!-- Form Fields -->
        <div class="form-fields">
          <!-- Dynamic form fields based on schema -->
          <div v-for="fieldGroup in groupedFields" :key="fieldGroup.name" class="field-group mb-4">
            <!-- Group Header -->
            <div v-if="fieldGroup.title" class="group-header mb-3">
              <h5 class="mb-2">{{ fieldGroup.title }}</h5>
              <hr class="mt-0">
            </div>

            <!-- Fields in Group -->
            <div class="row">
              <div 
                v-for="field in fieldGroup.fields" 
                :key="field.name"
                :class="getFieldColumnClass(field)"
              >
                <!-- Text Input -->
                <div v-if="field.type === 'text'" class="mb-3">
                  <label :for="field.name" class="form-label">
                    {{ field.label }}
                    <span v-if="field.required" class="text-danger">*</span>
                  </label>
                  <input
                    :id="field.name"
                    v-model="formData[field.name]"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': hasFieldError(field.name) }"
                    :disabled="mode === 'view'"
                    :required="field.required"
                    :placeholder="field.placeholder"
                  >
                  <div v-if="hasFieldError(field.name)" class="invalid-feedback">
                    {{ getFieldError(field.name) }}
                  </div>
                </div>

                <!-- Email Input -->
                <div v-else-if="field.type === 'email'" class="mb-3">
                  <label :for="field.name" class="form-label">
                    {{ field.label }}
                    <span v-if="field.required" class="text-danger">*</span>
                  </label>
                  <input
                    :id="field.name"
                    v-model="formData[field.name]"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': hasFieldError(field.name) }"
                    :disabled="mode === 'view'"
                    :required="field.required"
                    :placeholder="field.placeholder"
                  >
                  <div v-if="hasFieldError(field.name)" class="invalid-feedback">
                    {{ getFieldError(field.name) }}
                  </div>
                </div>

                <!-- Number Input -->
                <div v-else-if="field.type === 'number'" class="mb-3">
                  <label :for="field.name" class="form-label">
                    {{ field.label }}
                    <span v-if="field.required" class="text-danger">*</span>
                  </label>
                  <input
                    :id="field.name"
                    v-model.number="formData[field.name]"
                    type="number"
                    class="form-control"
                    :class="{ 'is-invalid': hasFieldError(field.name) }"
                    :disabled="mode === 'view'"
                    :required="field.required"
                    :min="field.min"
                    :max="field.max"
                    :step="field.step"
                  >
                  <div v-if="hasFieldError(field.name)" class="invalid-feedback">
                    {{ getFieldError(field.name) }}
                  </div>
                </div>

                <!-- Textarea -->
                <div v-else-if="field.type === 'textarea'" class="mb-3">
                  <label :for="field.name" class="form-label">
                    {{ field.label }}
                    <span v-if="field.required" class="text-danger">*</span>
                  </label>
                  <textarea
                    :id="field.name"
                    v-model="formData[field.name]"
                    class="form-control"
                    :class="{ 'is-invalid': hasFieldError(field.name) }"
                    :disabled="mode === 'view'"
                    :required="field.required"
                    :rows="field.rows || 3"
                    :placeholder="field.placeholder"
                  ></textarea>
                  <div v-if="hasFieldError(field.name)" class="invalid-feedback">
                    {{ getFieldError(field.name) }}
                  </div>
                </div>

                <!-- Select -->
                <div v-else-if="field.type === 'select'" class="mb-3">
                  <label :for="field.name" class="form-label">
                    {{ field.label }}
                    <span v-if="field.required" class="text-danger">*</span>
                  </label>
                  <select
                    :id="field.name"
                    v-model="formData[field.name]"
                    class="form-select"
                    :class="{ 'is-invalid': hasFieldError(field.name) }"
                    :disabled="mode === 'view'"
                    :required="field.required"
                  >
                    <option value="">{{ field.placeholder || 'Select...' }}</option>
                    <option 
                      v-for="option in field.options" 
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </option>
                  </select>
                  <div v-if="hasFieldError(field.name)" class="invalid-feedback">
                    {{ getFieldError(field.name) }}
                  </div>
                </div>

                <!-- Checkbox -->
                <div v-else-if="field.type === 'checkbox'" class="mb-3">
                  <div class="form-check">
                    <input
                      :id="field.name"
                      v-model="formData[field.name]"
                      type="checkbox"
                      class="form-check-input"
                      :class="{ 'is-invalid': hasFieldError(field.name) }"
                      :disabled="mode === 'view'"
                    >
                    <label :for="field.name" class="form-check-label">
                      {{ field.label }}
                      <span v-if="field.required" class="text-danger">*</span>
                    </label>
                    <div v-if="hasFieldError(field.name)" class="invalid-feedback">
                      {{ getFieldError(field.name) }}
                    </div>
                  </div>
                </div>

                <!-- Date Input -->
                <div v-else-if="field.type === 'date'" class="mb-3">
                  <label :for="field.name" class="form-label">
                    {{ field.label }}
                    <span v-if="field.required" class="text-danger">*</span>
                  </label>
                  <input
                    :id="field.name"
                    v-model="formData[field.name]"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': hasFieldError(field.name) }"
                    :disabled="mode === 'view'"
                    :required="field.required"
                  >
                  <div v-if="hasFieldError(field.name)" class="invalid-feedback">
                    {{ getFieldError(field.name) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div v-if="mode !== 'view'" class="form-actions border-top pt-4 mt-4">
          <div class="d-flex gap-2">
            <button 
              type="submit" 
              class="btn btn-primary"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
              {{ isEdit ? 'Update' : 'Create' }} {{ resourceTitle }}
            </button>
            
            <button 
              type="button" 
              class="btn btn-outline-secondary"
              @click="handleCancel"
              :disabled="isSubmitting"
            >
              Cancel
            </button>

            <button 
              v-if="isEdit"
              type="button" 
              class="btn btn-outline-warning ms-auto"
              @click="handleReset"
              :disabled="isSubmitting"
            >
              Reset Changes
            </button>
          </div>
        </div>

        <!-- View Mode Actions -->
        <div v-else class="view-actions border-top pt-4 mt-4">
          <div class="d-flex gap-2">
            <button 
              type="button" 
              class="btn btn-primary"
              @click="switchToEdit"
            >
              <i class="bi bi-pencil me-1"></i>
              Edit {{ resourceTitle }}
            </button>

            <button 
              type="button" 
              class="btn btn-outline-secondary"
              @click="handleCancel"
            >
              Close
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

interface FormField {
  name: string
  type: string
  label: string
  required?: boolean
  placeholder?: string
  options?: { value: any; label: string }[]
  min?: number
  max?: number
  step?: number
  rows?: number
  group?: string
  width?: 'full' | 'half' | 'third' | 'quarter'
}

interface FieldGroup {
  name: string
  title?: string
  fields: FormField[]
}

interface Props {
  resource: string
  resourceId?: string | number | null
  mode?: 'create' | 'edit' | 'view'
}

const props = withDefaults(defineProps<Props>(), {
  resourceId: null,
  mode: 'create'
})

const emit = defineEmits<{
  formSubmit: [{ data: any; mode: string }]
  formCancel: []
  formReset: []
  formError: [{ errors: any }]
}>()

// Services
const apiService = useApiService()
const notifyService = useNotifyService()

// Reactive state
const formData = ref<Record<string, any>>({})
const schema = ref<FormField[]>([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const hasError = ref(false)
const validationErrors = ref<Record<string, string>>({})
const originalData = ref<Record<string, any>>({})

// Computed properties
const resourceTitle = computed(() => 
  props.resource.charAt(0).toUpperCase() + props.resource.slice(1, -1)
)

const isEdit = computed(() => 
  props.mode === 'edit' || (props.resourceId !== null && props.resourceId !== undefined)
)

const hasValidationErrors = computed(() => 
  Object.keys(validationErrors.value).length > 0
)

const groupedFields = computed((): FieldGroup[] => {
  const groups: Record<string, FormField[]> = {}
  
  schema.value.forEach(field => {
    const groupName = field.group || 'default'
    if (!groups[groupName]) {
      groups[groupName] = []
    }
    groups[groupName].push(field)
  })

  return Object.entries(groups).map(([groupName, fields]) => ({
    name: groupName,
    title: groupName !== 'default' ? groupName : undefined,
    fields
  }))
})

// Initialize component
onMounted(() => {
  initializeForm()
})

const initializeForm = async () => {
  try {
    isLoading.value = true
    hasError.value = false

    // Load schema first
    await loadSchema()

    // Load data if editing
    if (isEdit.value && props.resourceId) {
      await loadData()
    } else {
      // Initialize with empty form data
      initializeEmptyForm()
    }
  } catch (error) {
    handleError('Failed to initialize form', error)
  } finally {
    isLoading.value = false
  }
}

const loadSchema = async () => {
  try {
    const response = await apiService.get(`${props.resource}/schema`, 'form')
    if (response.success && response.data) {
      schema.value = response.data.fields || []
    }
  } catch (error) {
    // If schema endpoint doesn't exist, use default fields
    schema.value = getDefaultFields()
  }
}

const loadData = async () => {
  if (!props.resourceId) return

  try {
    const response = await apiService.get(props.resource, props.resourceId)
    if (response.success && response.data) {
      formData.value = { ...response.data }
      originalData.value = { ...response.data }
    }
  } catch (error) {
    throw new Error(`Failed to load ${props.resource} data`)
  }
}

const initializeEmptyForm = () => {
  const emptyData: Record<string, any> = {}
  
  schema.value.forEach(field => {
    switch (field.type) {
      case 'checkbox':
        emptyData[field.name] = false
        break
      case 'number':
        emptyData[field.name] = null
        break
      default:
        emptyData[field.name] = ''
    }
  })

  formData.value = emptyData
  originalData.value = { ...emptyData }
}

// Form operations
const handleSubmit = async () => {
  try {
    clearValidationErrors()
    
    if (!validateForm()) {
      notifyService.warning('Please fix validation errors before submitting')
      return
    }

    isSubmitting.value = true

    let response
    if (isEdit.value && props.resourceId) {
      response = await apiService.update(props.resource, props.resourceId, formData.value)
    } else {
      response = await apiService.create(props.resource, formData.value)
    }

    if (response.success) {
      notifyService.success(response.message || `${resourceTitle.value} saved successfully`)
      emit('formSubmit', { data: response.data, mode: props.mode })
    } else {
      handleApiErrors(response)
    }
  } catch (error) {
    handleError('Failed to save form', error)
  } finally {
    isSubmitting.value = false
  }
}

const handleCancel = () => {
  emit('formCancel')
}

const handleReset = () => {
  formData.value = { ...originalData.value }
  clearValidationErrors()
  notifyService.info('Form reset to original values')
  emit('formReset')
}

const switchToEdit = () => {
  // This would typically be handled by parent component
  notifyService.info('Switching to edit mode')
}

// Validation
const validateForm = (): boolean => {
  let isValid = true

  schema.value.forEach(field => {
    if (field.required && !formData.value[field.name]) {
      setFieldError(field.name, `${field.label} is required`)
      isValid = false
    }

    if (field.type === 'email' && formData.value[field.name]) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(formData.value[field.name])) {
        setFieldError(field.name, 'Please enter a valid email address')
        isValid = false
      }
    }
  })

  return isValid
}

const clearValidationErrors = () => {
  validationErrors.value = {}
}

const setFieldError = (fieldName: string, message: string) => {
  validationErrors.value[fieldName] = message
}

const hasFieldError = (fieldName: string): boolean => {
  return !!validationErrors.value[fieldName]
}

const getFieldError = (fieldName: string): string => {
  return validationErrors.value[fieldName] || ''
}

// Utility functions
const getDefaultFields = (): FormField[] => {
  return [
    { name: 'name', type: 'text', label: 'Name', required: true },
    { name: 'description', type: 'textarea', label: 'Description' }
  ]
}

const getModeClass = () => {
  const classes = {
    'create': 'bg-success',
    'edit': 'bg-warning',
    'view': 'bg-info'
  }
  return classes[props.mode] || 'bg-secondary'
}

const getFieldColumnClass = (field: FormField) => {
  const widthClasses = {
    'full': 'col-12',
    'half': 'col-md-6',
    'third': 'col-md-4',
    'quarter': 'col-md-3'
  }
  return widthClasses[field.width || 'full']
}

const handleApiErrors = (response: any) => {
  if (response.error && response.error.details) {
    const errors = response.error.details
    Object.keys(errors).forEach(field => {
      setFieldError(field, errors[field][0] || 'Invalid value')
    })
  }
  emit('formError', { errors: response.error })
}

const handleError = (message: string, error: any) => {
  console.error('[Form]', message, error)
  hasError.value = true
  notifyService.error(message)
}

const retryLoad = () => {
  hasError.value = false
  initializeForm()
}

// Watch for prop changes
watch(() => props.resourceId, (newId) => {
  if (newId !== originalData.value?.id) {
    initializeForm()
  }
})

watch(() => props.mode, () => {
  // Mode change might require reinitialization
  clearValidationErrors()
})

// Expose methods for testing
defineExpose({
  handleSubmit,
  handleCancel,
  setLoading: (loading: boolean) => { isLoading.value = loading },
  setFormData: (data: Record<string, any>) => { formData.value = { ...data } },
  isLoading,
  hasValidationErrors,
  schema,
  formData
})
</script>

<style scoped>
.resource-form {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.form-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.loading-state,
.error-state {
  padding: 2rem;
  text-align: center;
}

.form-header {
  position: sticky;
  top: 0;
  background-color: white;
  z-index: 10;
}

.form-fields {
  flex: 1;
}

.field-group {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 0.375rem;
  padding: 1rem;
}

.group-header h5 {
  color: #495057;
  font-weight: 600;
}

.form-actions,
.view-actions {
  position: sticky;
  bottom: 0;
  background-color: white;
  z-index: 10;
}

.form-mode-indicator .badge {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .form-content {
    padding: 1rem;
  }
  
  .form-actions .d-flex,
  .view-actions .d-flex {
    flex-direction: column;
    gap: 0.5rem !important;
  }
  
  .form-actions .ms-auto {
    margin-left: 0 !important;
  }
}
</style>