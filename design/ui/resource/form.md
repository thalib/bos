# Form Component Documentation

## Overview

The `Form.vue` component provides a dynamic form for creating, editing, or viewing resources. It receives the complete schema node from the API response and handles all form logic internally.

## Features

- Dynamic form rendering based on API schema
- Grouped field rendering with collapsible sections
- Comprehensive validation with real-time feedback
- Handles loading and error states
- Self-contained form logic
- Bootstrap-based responsive design

## Props

- `schema` _(array|null)_: Complete schema node from API response containing field definitions and groups
- `data` _(object)_: Form data for edit mode (optional)
- `loading` _(boolean)_: Loading state for the component
- `disabled` _(boolean)_: Whether the form is disabled
- `mode` _(string)_: Form mode - 'create', 'edit', or 'view' (default: 'create')
- `showHeader` _(boolean)_: Whether to show form header (default: true)
- `resourceTitle` _(string)_: Title for the resource being managed

## Events

- `form-submit`: Emitted when form is submitted with valid data
  - Payload: `{ data: object, mode: string }`
- `form-cancel`: Emitted when form is cancelled
- `form-reset`: Emitted when form is reset
- `form-error`: Emitted when form validation fails
  - Payload: `{ errors: object }`

## API Response Structure

The component expects the `schema` prop to match the API response structure:

```json
{
  "schema": [
    {
      "group": "General Information",
      "fields": [
        {
          "field": "name",
          "label": "Product Name",
          "type": "string",
          "required": true,
          "placeholder": "Enter product name",
          "maxLength": 255
        },
        {
          "field": "status",
          "label": "Status",
          "type": "select",
          "required": true,
          "options": [
            { "value": "active", "label": "Active" },
            { "value": "inactive", "label": "Inactive" }
          ]
        }
      ]
    }
  ]
}
```

## Usage

```vue
<Form
  :schema="response.schema"
  :data="selectedItem"
  :loading="isLoading"
  :disabled="false"
  :mode="'edit'"
  :showHeader="true"
  :resourceTitle="'Product'"
  @form-submit="handleFormSubmit"
  @form-cancel="handleFormCancel"
  @form-error="handleFormError"
/>
```

## Internal Logic

The component handles:

- Parsing schema groups and fields from the API response
- Rendering appropriate input types based on field configuration
- Managing form state and validation
- Providing real-time validation feedback
- Handling form submission and data formatting

## Supported Field Types

- `string`: Text input
- `email`: Email input
- `password`: Password input
- `number`: Number input
- `decimal`: Decimal number input
- `select`: Dropdown selection
- `checkbox`: Checkbox input
- `textarea`: Multi-line text input
- `date`: Date picker
- `datetime`: Date and time picker
- `file`: File upload

## Error Handling

- Displays validation errors inline with form fields
- Shows loading state during form submission
- Provides user-friendly error messages
- Handles server-side validation errors

## Bootstrap Classes Used

- `form-group`, `form-control`, `form-select` for form styling
- `btn`, `btn-primary`, `btn-secondary` for button styling
- `card`, `card-header`, `card-body` for grouping
- `invalid-feedback` for validation errors
- `spinner-border` for loading states
- `row`, `col-*` for responsive layout

## Notes

- The component is self-contained and manages its own state
- All form logic is handled internally based on the API schema
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports real-time validation and user feedback
