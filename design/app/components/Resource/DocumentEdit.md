# DocumentEdit Component Design Specification

- The `DocumentEdit` component is designed to edit documents (e.g., estimates, invoices). It provides a responsive and user-friendly interface for editing documents, complete with a header containing the document title on the left and a close icon on the right. The body contains form fields for editing, and the footer includes Save and Cancel buttons for user actions. The component integrates with the backend API to save or update document data.

**File Location:** `frontend/app/components/Resource/DocumentEdit.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<DocumentEdit
  :loading="isLoading"
  :id="resourceID"
  :title="resourceID"
  :data="data"
/>
```

- **Props:**
  - `loading` (boolean): Indicates if the component is in a loading state.
  - `id` (string): The ID of the resource being edited.
  - `title` (string): The title of the document.
  - `data` (object): The data of the document to be edited.
- **Events:**
  - `save`: Emitted when the Save button is clicked.
  - `cancel`: Emitted when the Cancel button is clicked.

## Child Components (optional)

```txt
Parent
└── DocumentEdit
```

## Features

- **Header**:
  - Displays the document title on the left.
  - Includes a close icon on the right to exit the edit mode.
- **Body**:
  - Contains form fields for editing document data.
- **Footer**:
  - Includes Save and Cancel buttons.
  - Save button triggers API calls to save or update the document.
  - Cancel button discards changes and exits edit mode.
- Handles loading, error, and empty states gracefully.
- Provides retry functionality for loading errors.
- Responsive design for various screen sizes.

## UI Design

```txt
+------------------------------------------------+
| [Title]                                [Close] |
+------------------------------------------------+
|                                                |
|                [Form Fields]                   |
|                                                |
+------------------------------------------------+
| [Save Button] [Cancel]                         |
+------------------------------------------------+
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

- Displays a user-friendly error message if the document fails to save.
- Provides a retry button for re-saving the document data.
- Integrates the Toast component for error and success notifications.
  - **Error Toast**: Displays a pinned error toast requiring manual dismissal.
  - **Success Toast**: Auto-dismisses after 5 seconds, confirming successful operations.

## Example Usage (optional)

```html
<DocumentEdit
  :loading="true"
  :id="'123'"
  :title="'Invoice #123'"
  :data="{ field1: 'value1', field2: 'value2' }"
/>
```