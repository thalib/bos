# DocumentViewer Component Documentation

## Overview
The `DocumentViewer.vue` component is designed to display document templates in a viewer with loading, error, and empty states.

## Features
- Displays a document template dynamically.
- Handles loading and error states.
- Provides retry functionality for loading errors.
- Responsive design for various screen sizes.

## Props
- `templateLoading`: Boolean indicating if the template is loading.
- `error`: Object containing error details.
- `currentTemplateId`: ID of the currently selected template.
- `templateComponent`: Component to render the template.

## Events
- `retryLoadTemplate`: Triggered to retry loading the template.

## Usage
```vue
<DocumentViewer
  :templateLoading="false"
  :error="null"
  :currentTemplateId="'template123'"
  :templateComponent="MyTemplateComponent"
/>
```

## Notes
- Ensure `templateComponent` is dynamically loaded based on `currentTemplateId`.
- Use the retry button for error recovery.
