# Form Component Design Specification

- The `Form` component provides a dynamic form for creating, editing, or viewing resources. It receives the complete schema node from the API response and handles all form logic internally.

**File Location:** `frontend/app/components/Resource/Form.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Form
  :id="resourceID"
  :schema="schema"
  :loading="isLoading"
  :mode="'edit'"
  @form-submit="handleFormSubmit"
  @form-cancel="handleFormCancel"
/>
```

- **Props:**
  - `id` (int): Resource ID. If `null`, the form is in create mode; otherwise, edit mode.
  - `schema` (array|null): Complete schema node from API response containing field definitions and groups.
  - `loading` (boolean): Indicates if the component is in a loading state.
  - `mode` (string): Form mode - 'create', 'edit', or 'view' (default: 'create').
- **Events:**
  - `form-submit`: Triggered when the form is submitted with valid data. Payload: `{ data: object, mode: string }`.
  - `form-cancel`: Triggered when the form is canceled.
  - `form-reset`: Triggered when the form is reset.
  - `form-error`: Triggered when form validation fails. Payload: `{ errors: object }`.

## Child Components (optional)

```txt
Parent
└── Form
```

## Features

- Dynamic form rendering based on API schema with grouped field rendering in collapsible sections.
- Comprehensive validation with real-time feedback, including server-side validation errors.
- Displays validation errors inline with form fields and provides user-friendly error messages.
- Handles loading and error states, showing a loading indicator during form submission.
- Self-contained form logic that manages its own state internally based on the API schema.
- Responsive design for various screen sizes.

## UI Design

```txt
+-----------------------------------------------+
| [Form Title]                                  |
+-----------------------------------------------+
| [Form Fields Grouped in Collapsible Sections] |
+-----------------------------------------------+
| [Submit Button] [Cancel Button]               |
+-----------------------------------------------+
```

- Uses Bootstrap 5.3 classes for consistent styling.

## Implementation Rules

- All HTTP requests must use the shared API service (`frontend/app/utils/api.ts`).
- All notifications and error handling must use the Notify Service (`frontend/app/utils/notify.ts`).
- Use Bootstrap 5.3 classes for all layout and UI elements.
- Strictly type all props and logic with TypeScript.
- Provide loading and error states for all async operations.
- Ensure accessibility (ARIA roles, keyboard navigation).
- Write tests first in `frontend/tests/` before implementing features.

## Error Handling

- Displays validation errors inline with form fields.
- Provides user-friendly error messages for server-side validation errors.
- Shows a loading indicator during form submission.

## Example Usage (optional)

```html
<Form
  :id="123"
  :schema="[{ field: 'name', type: 'string' }]"
