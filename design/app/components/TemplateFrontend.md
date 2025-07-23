
# [Component Name] Component Design Specification

- Brief description of the component's purpose and functionality.
- Mention its role in the application and any unique features.

**File Location:** `frontend/app/components/[Component Name].vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Filter
  :filters="response.filters"
  :loading="isLoading"
  @filter-change="handleFilterChange"
  @filter-clear="handleFilterClear"
/>
```

- **Props:** List of props the component accepts, including their types and default values.
- **Events:** List of events emitted by the component, including their payloads.
- Mention any composables, utilities, or child components used.

## Child Components (optional)

```txt
Parent
└── [Component Name]
    └── [ChildComponent]
```

- **[ChildComponent]:**
  - Design specification reference: `design/app/components/[ChildComponent].md`
  - File path: `frontend/app/components/[ChildComponent].vue`

## Features

- List of key features provided by the component.
- Highlight any responsive or accessibility features.

## UI Design

```txt
[Component Layout Diagram or Description]
```

- Provide a textual or visual representation of the component's layout.
- Include any relevant Bootstrap classes or styling details.

## Implementation Rules

- All HTTP requests must use the shared API service (`frontend/app/utils/api.ts`).
- All notifications and error handling must use the Notify Service (`frontend/app/utils/notify.ts`).
- Use Bootstrap 5.3 classes for all layout and UI elements.
- Strictly type all props and logic with TypeScript.
- Provide loading and error states for all async operations.
- Ensure accessibility (ARIA roles, keyboard navigation).
- Write tests first in `frontend/tests/` before implementing features.

## Error Handling (optional)

- Describe how the component handles errors or invalid states.

## Accessibility (optional)

- Highlight any ARIA attributes or accessibility considerations.

## Example Usage (optional)

```html
<!-- Example usage snippet -->
```

## References

- [Frontend Rules](design/rules-app.md)
- [GitHub Copilot Instructions](.github/copilot-instructions.md)
