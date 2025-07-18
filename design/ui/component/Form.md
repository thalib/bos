# Form Component Documentation

## Overview

The `Form.vue` component provides a dynamic form for creating, editing, or viewing resources. It receives the complete schema node from the API response (`design/api/index.md`) and handles all form logic internally.

- Dynamic form rendering based on API schema with grouped field rendering in collapsible sections
- Comprehensive validation with real-time feedback, including server-side validation errors
- Displays validation errors inline with form fields and provides user-friendly error messages
- Handles loading and error states, showing a loading indicator during form submission
- Self-contained form logic that manages its own state internally based on the API schema
- Follows Bootstrap 5.3 patterns for responsive design and consistent styling
- Provides proper ARIA attributes for accessibility
- Supports real-time validation and user feedback

```html
<form
  :id="{resourceID}"
  :schema="response.schema"
  :loading="isLoading"
  @form-submit="handleFormSubmit"
  @form-cancel="handleFormCancel"
  @form-error="handleFormError"
/>
```

**Events**

- `form-submit`: Emitted when the form is submitted with valid data, Payload: `{ data: object, mode: string }`
- `form-cancel`: Emitted when the form is canceled
- `form-reset`: Emitted when the form is reset
- `form-error`: Emitted when form validation fails, Payload: `{ errors: object }`

**Props**

- `id` _(int)_: `resourceID = null` then create mode else edit mode
- `schema` _(array|null)_: Complete schema node from API response containing field definitions and groups
- `loading` _(boolean)_: Loading state for the component
- `mode` _(string)_: Form mode - 'create', 'edit', or 'view' (default: 'create')

If `resourceID = null`:
- Form mode = create
- Use the schema to render the form and form fields in groups
- Title = `Create {Resource}` for the resource being managed

Else:
- Form mode = edit
- Use the schema to render the form and form fields in groups
- Fetch the resource item and show it in the form
- Title = Resource Name

## API Response Structure

The component expects the `schema` prop to match the API response (refer to `design/api/index.md`) structure:

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

**Field Types**

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

## Internal Logic

The component handles:

- Parsing schema groups and fields from the API response
- Rendering appropriate input types based on field configuration
- Managing form state and validation
- Providing real-time validation feedback
- Handling form submission and data formatting

## Bootstrap Classes Used

- `form-group`, `form-control`, `form-select` for form styling
- `btn`, `btn-primary`, `btn-secondary` for button styling
- `card`, `card-header`, `card-body` for grouping
- `invalid-feedback` for validation errors
- `spinner-border` for loading states
- `row`, `col-*` for responsive layout
