<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3" v-if="showHeader">
      <h5 class="mb-0">
        {{ isViewMode ? 'View/Edit' : 'Create' }} {{ resourceTitle }}
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
    <div v-else-if="error" class="alert alert-danger">
      <h5 class="alert-heading">Error loading data</h5>
      <p class="mb-2">{{ error.message }}</p>
      
      <!-- Show validation errors if available -->
      <div v-if="error.validationErrors && Object.keys(error.validationErrors).length > 0">
        <h6>Validation Errors:</h6>
        <ul class="mb-2">
          <li v-for="(messages, field) in error.validationErrors" :key="field">
            <strong>{{ field }}:</strong> {{ Array.isArray(messages) ? messages[0] : messages }}
          </li>
        </ul>
      </div>
      
      <button class="btn btn-outline-danger" @click="loadForm">
        Try Again
      </button>
    </div>    <!-- Form content -->
    <template v-else-if="schema">
      <form @submit.prevent="handleSubmit">
        <!-- Unified grouped schema rendering -->
        <div v-for="(group, groupIdx) in normalizedGroupedSchema" :key="`group-${groupIdx}`" class="mb-5">
          <!-- Group header - only show if schema was originally grouped -->
          <div v-if="isGroupedSchema" class="border-bottom border-2 border-primary mb-4">
            <h5 class="fw-bold text-primary mb-2 d-flex align-items-center">
              <i class="bi bi-folder me-2"></i>
              {{ group.group }}
            </h5>
          </div>
          
          <!-- Group fields -->
          <div class="row g-3">
            <template v-for="(field, fieldName) in group.fields" :key="`${groupIdx}-${fieldName}`">
              <!-- All fields - consistent layout -->
              <div class="col-12">
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
                      @update:model-value="formData[fieldName] = $event"
                    />
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
        
        <!-- Delete button for view mode -->
        <div v-if="isViewMode" class="row mb-4">
          <div class="col-sm-3"></div>
          <div class="col-sm-9">
            <button 
              type="button" 
              class="btn btn-outline-danger btn-sm" 
              @click="showDeleteModal = true"
              :disabled="isDeleting"
            >
              <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2" role="status"></span>
              <i v-else class="bi bi-trash me-1"></i>
              Delete this {{ resourceTitle.toLowerCase() }}
            </button>
          </div>
        </div>

        <!-- Form actions -->
        <div class="border-top pt-3">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
              {{ isViewMode ? 'Update' : 'Create' }}
            </button>
            <button type="button" class="btn btn-secondary" @click="$emit('cancel')">
              Close
            </button>
          </div>
        </div>
      </form>
    </template>
  </div>

  <!-- Delete Confirmation Modal -->
  <div 
    v-if="showDeleteModal"
    class="modal d-block" 
    tabindex="-1" 
    aria-labelledby="deleteConfirmModalLabel" 
    aria-modal="true"
    role="dialog"
    style="background-color: rgba(0,0,0,0.5);"
    @click.self="showDeleteModal = false"
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow">
        <div class="modal-header border-bottom">
          <h5 class="modal-title" id="deleteConfirmModalLabel">
            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
            Confirm Deletion
          </h5>
          <button 
            type="button" 
            class="btn-close" 
            @click="showDeleteModal = false"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <p class="mb-3">
            Are you sure you want to delete this {{ resourceTitle.toLowerCase() }}?
          </p>
          <div class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <div>
              <strong>Warning:</strong> This action cannot be undone.
            </div>
          </div>
        </div>
        <div class="modal-footer border-top">
          <button 
            type="button" 
            class="btn btn-secondary" 
            @click="showDeleteModal = false"
            :disabled="isDeleting"
          >
            Cancel
          </button>
          <button 
            type="button" 
            class="btn btn-danger" 
            @click="handleDelete"            :disabled="isDeleting"
          >
            <span v-if="isDeleting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            <i v-else class="bi bi-trash me-1"></i>
            Delete {{ resourceTitle }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import FormField from '~/components/Form/FormField.vue'
import { ref, computed, onMounted, watch } from 'vue'
import { useApiCrud, type SchemaResponse } from '~/services/apiCrud'
import type { ApiError } from '~/types'
import { toast, ToastType } from '~/utils/toast'

interface Props {
  resource: string
  id?: string | number
  apiVersion?: string
  resourceTitle?: string
  showHeader?: boolean
}

interface Emits {
  (e: 'submit', data: any): void
  (e: 'success', data: any): void
  (e: 'error', error: ApiError): void
  (e: 'cancel'): void
  (e: 'delete', id: string | number): void
  (e: 'deleted', data: any): void
  (e: 'refresh'): void
}

const props = withDefaults(defineProps<Props>(), {
  apiVersion: undefined,
  resourceTitle: undefined,
  showHeader: true
})

const emit = defineEmits<Emits>()

// State
const schema = ref<SchemaResponse | null>(null)
const formData = ref<Record<string, any>>({})
const initialData = ref<Record<string, any>>({})
const loading = ref(true)
const isSubmitting = ref(false)
const error = ref<ApiError | null>(null)
const validationErrors = ref<Record<string, string[]>>({})
const isDeleting = ref(false)
const showDeleteModal = ref(false)

// API service
const apiCrud = useApiCrud()

// Computed properties
const isViewMode = computed(() => !!props.id)
const resourceTitle = computed(() => 
  props.resourceTitle || 
  props.resource.charAt(0).toUpperCase() + props.resource.slice(1)
)

// Normalize schema fields - handle both 'fields' and 'properties' keys
const normalizedFields = computed(() => {
  if (!schema.value) return {}
  
  // If schema is an array (grouped), don't try to normalize it here
  if (Array.isArray(schema.value)) return {}
  
  // Prefer 'fields' but fallback to 'properties' for backward compatibility
  return schema.value.fields || schema.value.properties || {}
})

// Computed: detect grouped schema
const isGroupedSchema = computed(() => {
  // Schema is grouped if it's an array and each item has group and fields keys
  if (!schema.value) return false
  
  // Check if schema.value is an array
  if (!Array.isArray(schema.value)) return false
  
  // Check if array is not empty
  if (schema.value.length === 0) return false
  
  // Check if every item in the array has the required structure
  return schema.value.every(
    (g: any) => g && 
               typeof g === 'object' && 
               'group' in g && 
               'fields' in g && 
               typeof g.fields === 'object' &&
               g.fields !== null
  )
})

// Unified grouped schema - treats all schemas as grouped for consistent rendering
const normalizedGroupedSchema = computed(() => {
  if (!schema.value) return []
  
  // If already grouped, return as-is
  if (isGroupedSchema.value) {
    return schema.value as Array<{ group: string, fields: Record<string, any> }>
  }
  
  // Convert flat schema to single group format
  const flatFields = normalizedFields.value
  if (Object.keys(flatFields).length > 0) {
    return [
      {
        group: 'Form Fields',
        fields: flatFields
      }
    ]
  }
  
  return []
})

// Get all fields from unified grouped schema for form data initialization
const allFieldsFromSchema = computed(() => {
  const allFields: Record<string, any> = {}
  normalizedGroupedSchema.value.forEach(group => {
    Object.assign(allFields, group.fields)
  })
  return allFields
})

// Methods
const loadForm = async () => {
  error.value = null
  loading.value = true
  validationErrors.value = {}

  try {
    const { schema: schemaResponse, data: dataResponse } = await apiCrud.getFormData(
      props.resource,
      props.id,
      props.apiVersion
    )

    if (schemaResponse.error) {
      throw schemaResponse.error
    }
    if (props.id && dataResponse?.error) {
      throw dataResponse.error
    }    // Handle standardized response format for schema
    let schemaData = schemaResponse.data
    if (schemaData && typeof schemaData === 'object' && 'success' in schemaData) {
      // Standardized format: { success: true, data: {...} }
      const standardizedResponse = schemaData as any
      if (standardizedResponse.success) {
        schemaData = standardizedResponse.data
      } else {
        throw new Error(standardizedResponse.error?.message || 'Failed to load schema')
      }
    }
    
    schema.value = schemaData    // Handle standardized response format for data (when editing)
    let recordData = null
    if (props.id && dataResponse?.data) {
      recordData = dataResponse.data
      if (recordData && typeof recordData === 'object' && 'success' in recordData) {
        // Standardized format: { success: true, data: {...} }
        const standardizedResponse = recordData as any
        if (standardizedResponse.success) {
          recordData = standardizedResponse.data
        } else {
          throw new Error(standardizedResponse.error?.message || 'Failed to load record data')
        }
      }
    }

    // Initialize form data with defaults
    const defaultValues: Record<string, any> = {}
    
    if (schema.value) {
      // Use unified grouped schema for initialization
      normalizedGroupedSchema.value.forEach(group => {
        Object.entries(group.fields).forEach(([fieldName, field]) => {
          defaultValues[fieldName] = field.default !== undefined ? field.default : null
        })
      })
    }

    // Merge with existing data if editing
    if (props.id && recordData) {
      initialData.value = { ...recordData }
      formData.value = { ...defaultValues, ...recordData }
    } else {
      initialData.value = { ...defaultValues }
      formData.value = { ...defaultValues }
    }

  } catch (err) {
    error.value = err as ApiError
    emit('error', error.value)
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  validationErrors.value = {}
  isSubmitting.value = true
  
  // Filter form data to only include schema fields using unified approach
  const filteredFormData: Record<string, any> = {}
  
  const allFields = allFieldsFromSchema.value
  Object.keys(allFields).forEach(fieldName => {
    if (formData.value[fieldName] !== undefined) {
      filteredFormData[fieldName] = formData.value[fieldName]
    }
  })
  
  try {
    emit('submit', filteredFormData)
    
    const response = isViewMode.value 
      ? await apiCrud.apiUpdate(props.resource, props.id!, filteredFormData, props.apiVersion)
      : await apiCrud.apiCreate(props.resource, filteredFormData, props.apiVersion)
    
    if (response.error) {
      if ((response.error as ApiError).validationErrors) {
        validationErrors.value = (response.error as ApiError).validationErrors || {}
      }
      throw response.error
    }    // Handle success message from new standardized format
    let successMessage = isViewMode.value 
      ? `${resourceTitle.value} updated successfully!`
      : `${resourceTitle.value} created successfully!`
    
    // Check if response has a message from the new standardized format
    if (response.data && typeof response.data === 'object' && 'message' in response.data) {
      successMessage = (response.data as any).message
    }
    
    toast(ToastType.SUCCESS, successMessage)
    emit('success', response.data)
  } catch (err) {
    const apiError = err as ApiError
    error.value = apiError
    
    if (apiError.validationErrors) {
      validationErrors.value = apiError.validationErrors
    }
    
    emit('error', apiError)
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async () => {
  if (!props.id) return

  isDeleting.value = true
  
  try {
    emit('delete', props.id)
    
    const response = await apiCrud.apiDelete(props.resource, props.id, props.apiVersion)
    
    if (response.error) throw response.error
    
    showDeleteModal.value = false
    toast(ToastType.SUCCESS, `${resourceTitle.value} deleted successfully!`)
    emit('deleted', response.data)
    emit('refresh')
    emit('cancel')
    
  } catch (err) {
    const apiError = err as ApiError
    error.value = apiError
    showDeleteModal.value = false
    toast(ToastType.ERROR, `Failed to delete ${resourceTitle.value.toLowerCase()}: ${apiError.message}`)
    emit('error', apiError)
  } finally {
    isDeleting.value = false
  }
}

// Watchers and lifecycle
watch(() => [props.resource, props.id], loadForm)
onMounted(loadForm)

// Expose methods for parent components
defineExpose({
  loadForm
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
