<template>
  <div>
    <!-- Checkbox: special layout -->
    <div v-if="field.type === 'checkbox'" class="form-check form-switch">
      <input
        class="form-check-input"
        type="checkbox"
        role="switch"
        :id="fieldName"
        :checked="modelValue"
        :required="field.required"
        @change="onCheckboxChange"
        :class="{ 'is-invalid': hasError }"
        v-bind="field.attributes"
      />
      <div class="invalid-feedback" v-if="hasError">
        {{ validationError?.[0] }}
      </div>
    </div>

    <!-- File input -->
    <div v-else-if="field.type === 'file'">
      <input
        class="form-control"
        type="file"
        :id="fieldName"
        :required="field.required"
        :accept="field.accept"
        :multiple="field.multiple"
        @change="onFileChange"
        :class="{ 'is-invalid': hasError }"
        v-bind="field.attributes"
      />
      <div class="invalid-feedback" v-if="hasError">
        {{ validationError?.[0] }}
      </div>
    </div>

    <!-- Select -->
    <div v-else-if="field.type === 'select'">
      <select
        class="form-select"
        :id="fieldName"
        :required="field.required"
        v-model="localValue"
        :class="{ 'is-invalid': hasError }"
        v-bind="field.attributes"
      >
        <option value="" disabled selected>
          {{ field.placeholder || `Select ${field.label?.toLowerCase() || 'option'}` }}
        </option>
        <option v-for="option in field.options" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
      <div class="invalid-feedback" v-if="hasError">
        {{ validationError?.[0] }}
      </div>
    </div>

    <!-- Textarea -->
    <div v-else-if="field.type === 'textarea'">
      <div class="input-group" v-if="field.prefix || field.suffix">
        <span v-if="field.prefix" class="input-group-text">{{ field.prefix }}</span>
        <textarea
          class="form-control"
          :id="fieldName"
          :placeholder="field.placeholder || `Enter ${field.label?.toLowerCase() || 'text'}`"
          :required="field.required"
          :rows="field.attributes?.rows || 3"
          v-model="localValue"
          :class="{ 'is-invalid': hasError }"
          v-bind="field.attributes"
        />
        <span v-if="field.suffix" class="input-group-text">{{ field.suffix }}</span>
      </div>
      <textarea
        v-else
        class="form-control"
        :id="fieldName"
        :placeholder="field.placeholder || `Enter ${field.label?.toLowerCase() || 'text'}`"
        :required="field.required"
        :rows="field.attributes?.rows || 3"
        v-model="localValue"
        :class="{ 'is-invalid': hasError }"
        v-bind="field.attributes"
      />
      <div class="invalid-feedback" v-if="hasError">
        {{ validationError?.[0] }}
      </div>
    </div>

    <!-- All other input types (text, number, email, tel, password, date, default) -->
    <div v-else>
      <div class="input-group" v-if="field.prefix || field.suffix">
        <span v-if="field.prefix" class="input-group-text">{{ field.prefix }}</span>
        <input
          :type="inputType"
          class="form-control"
          :id="fieldName"
          v-model="localValue"
          :placeholder="field.placeholder || `Enter ${field.label?.toLowerCase() || 'value'}`"
          :required="field.required"
          :minlength="field.minLength"
          :maxlength="field.maxLength"
          :min="field.min"
          :max="field.max"
          :step="field.step || field.attributes?.step"
          :pattern="field.pattern"
          :class="{ 'is-invalid': hasError }"
          v-bind="field.attributes"
        />
        <span v-if="field.suffix" class="input-group-text">{{ field.suffix }}</span>
      </div>
      <input
        v-else
        :type="inputType"
        class="form-control"
        :id="fieldName"
        v-model="localValue"
        :placeholder="field.placeholder || `Enter ${field.label?.toLowerCase() || 'value'}`"
        :required="field.required"
        :minlength="field.minLength"
        :maxlength="field.maxLength"
        :min="field.min"
        :max="field.max"
        :step="field.step || field.attributes?.step"
        :pattern="field.pattern"
        :class="{ 'is-invalid': hasError }"
        v-bind="field.attributes"
      />
      <div class="invalid-feedback" v-if="hasError">
        {{ validationError?.[0] }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

interface FieldOption {
  value: string | number
  label: string
}

interface FieldSchema {
  type?: string
  label: string
  placeholder?: string
  required?: boolean
  options?: FieldOption[]
  minLength?: number
  maxLength?: number
  min?: string
  max?: string
  step?: string
  prefix?: string
  suffix?: string
  accept?: string
  multiple?: boolean
  default?: any
  unique?: boolean
  pattern?: string
  attributes?: Record<string, any>
}

const props = defineProps<{
  fieldName: string
  field: FieldSchema
  modelValue: any
  validationError?: string[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
}>()

const hasError = computed(() => !!props.validationError && props.validationError.length > 0)

// For v-model on select, textarea, and most inputs
const localValue = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  localValue.value = val
})

watch(localValue, (val) => {
  emit('update:modelValue', val)
})

const inputType = computed(() => {
  // Default to text if not specified or unknown
  const t = props.field.type
  if (!t) return 'text'
  if ([ // Changed from {} to []
    'text', 'number', 'email', 'tel', 'password', 'date'
  ].includes(t)) return t
  return 'text'
})

function onFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files) return
  emit('update:modelValue', props.field.multiple ? Array.from(input.files) : input.files[0])
}

function onCheckboxChange(event: Event) {
  const target = event.target as HTMLInputElement | null
  if (!target) return
  emit('update:modelValue', target.checked)
}
</script>
