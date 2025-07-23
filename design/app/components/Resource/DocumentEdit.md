# DocumentEdit Component Design Specification

## Overview

The `DocumentEdit` component is designed to edit documents (e.g., estimates, invoices). It provides a responsive and user-friendly interface for editing documents, complete with a header containing the document title on the left and a close icon on the right. The body contains form fields for editing, and the footer includes Save and Cancel buttons for user actions. The component integrates with the backend API to save or update document data.

```html
<DocumentEdit
  :loading="isLoading"
  :id="resourceID"
  :title="resourceID"
  :data="data"
/>
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

```text
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

## API Integration

- **Endpoint for New Documents**: `POST /api/v1/{resource}`
- **Endpoint for Existing Documents**: `PUT /api/v1/products/{id}`
- **Description**:
  - Save button triggers a `POST` request for new documents or a `PUT` request for existing documents.
  - On success: Display a success toast and exit edit mode.
  - On error: Display an error toast with retry functionality.

## Constraints

- **API Usage**: All API calls must use the shared API service (`useApiService`).
- **UI Styling**: Follow Bootstrap 5.3 classes for consistent styling.
- **TypeScript**: Ensure strict typing for all data structures and API responses.
- **Error Handling**: Use the Toast component for notifications and error messages.
- **Responsive Design**: Ensure the component adapts to various screen sizes.

## Error Handling

- Displays a user-friendly error message if the document fails to save.
- Provides a retry button for re-saving the document data.
- Integrates the Toast component for error and success notifications.
  - **Error Toast**: Displays a pinned error toast requiring manual dismissal.
  - **Success Toast**: Auto-dismisses after 5 seconds, confirming successful operations.

## Footer Actions

- **Save**:
  - Validates the form fields.
  - Sends a `POST` or `PUT` request to the backend API.
  - Displays a success toast on successful save.
  - Displays an error toast on failure, with retry functionality.
- **Cancel**:
  - Discards changes and exits edit mode.