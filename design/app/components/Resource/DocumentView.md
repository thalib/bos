# DocumentViewer Component Design Specification

- The `DocumentViewer` component is designed to preview documents in printable formats (A4/A5). It provides a responsive and user-friendly interface for viewing documents, complete with a header containing action buttons for editing, downloading, and copying the document. The component fetches document data from the backend API and renders it using CanvasJS.

**File Location:** `frontend/app/components/Resource/DocumentViewer.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<DocumentViewer
  :loading="isLoading"
  :id="resourceID"
  :template="'template123'"
/>
```

- **Props:**
  - `loading` (boolean): Indicates if the component is in a loading state.
  - `id` (string): The ID of the resource being viewed.
  - `template` (string): The template used for rendering the document.
- **Events:**
  - `edit`: Triggered when the Edit button is clicked.
  - `download`: Triggered when the Download button is clicked.
  - `copy`: Triggered when the Copy button is clicked.

## Child Components (optional)

```txt
Parent
└── DocumentViewer
```

## Features

- Previews documents in A4/A5 formats.
- Fetches document data from the `GET /api/v1/estimate/{id}` endpoint.
- Renders the document dynamically using CanvasJS.
- Includes a header with the following action buttons:
  - **Edit**: Opens the document in edit mode.
  - **Download**: Allows downloading the document as JPG or PDF.
  - **Copy**: Copies the document content to clipboard.
- Handles loading, error, and empty states gracefully.
- Provides retry functionality for loading errors.
- Responsive design for various screen sizes.

## UI Design

```txt
+------------------------------------------------+
| [Header with buttons: Edit | Download | Copy]  |
+------------------------------------------------+
|                                                |
|                [Document Preview]              |
|                                                |
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

- Displays a user-friendly error message if the document fails to load.
- Provides a retry button for re-fetching the document data.
- Integrates the Toast component for error and success notifications.
  - **Error Toast**: Displays a pinned error toast requiring manual dismissal.
  - **Success Toast**: Auto-dismisses after 5 seconds, confirming successful operations.

## Example Usage (optional)

```html
<DocumentViewer
  :loading="true"
  :id="'123'"
  :template="'template123'"
/>
```