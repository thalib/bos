# DocumentViewer Component Design Specification

## Overview

The `DocumentViewer` component is designed to preview documents in printable formats (A4/A5). It provides a responsive and user-friendly interface for viewing documents, complete with a header containing action buttons for editing, downloading, and copying the document. The component fetches document data from the backend API and renders it using CanvasJS.

```html
<DocumentViewer
  :loading="isLoading"
  :id="resourceID"
  :template="'template123'"
/>
```

## Features

- Previews documents in A4/A5 formats.
- Fetches document data from the `GET /api/v1/estimate/{id}` endpoint.
- Renders the document dynamically using CanvasJS.
- Includes a header with the following action buttons:
  - **Edit**: Opens the document in edit mode.
  - **Download**: Allows downloading the document as:
    - JPG image
    - PDF file
  - **Copy**: Copies the document content to clipboard.
- Handles loading, error, and empty states gracefully.
- Provides retry functionality for loading errors.
- Responsive design for various screen sizes.

## UI Design

```text
+------------------------------------------------+
| [Header with buttons: Edit | Download | Copy]  |
+------------------------------------------------+
|                                                |
|                [Document Preview]              |
|                                                |
+------------------------------------------------+
```

- **Header Buttons**:
  - Positioned at the top of the component.
  - Includes buttons for editing, downloading, and copying the document.
- **Document Preview**:
  - Rendered in A4 format using CanvasJS.
  - Dynamically adjusts to fit the screen size.

## API Integration

- Fetches the document data for the given resource ID. **Endpoint**: `GET /api/v1/estimate/{id}`
- On success: Render the document using CanvasJS. On error: Display an error message with a retry button.

## Constraints


- **API Usage**: All API calls must use the shared API service (`useApiService`).
- **UI Styling**: Follow Bootstrap 5.3 classes for consistent styling.
- **TypeScript**: Ensure strict typing for all data structures and API responses.
- for notifcation and errors use Toast Component design\ui\component\Toast.md
- Displays a user-friendly error message if the document fails to load.
- Provides a retry button for re-fetching the document data.
- **CanvasJS**: Used for rendering the document dynamically based on template and data from backend in A4 format.
- **Responsive Design**: Ensures the document preview adapts to various screen sizes.