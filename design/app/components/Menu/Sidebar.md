# Sidebar Component Design Specification

The Sidebar component provides a responsive, off-canvas navigation menu for the BOS frontend. It displays user info, menu items (including sections, dividers, and regular links). It is designed for use in the main navigation and is optimized for both desktop and mobile.

**File Location:** `frontend/app/components/Menu/Sidebar.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Sidebar />
```

- **Props:**
- **Events:** 

## Child Components (optional)

```txt
Navbar
└── Sidebar
    └── (uses NuxtLink for navigation)
```

- **NuxtLink:**
  - Used for navigation between routes.

## Features

- Responsive off-canvas sidebar navigation.
- Displays user name and avatar icon.
- Supports menu sections (collapsible), dividers, and regular items.
- Dark/light mode toggle with event emission.
- Logout button with redirect and offcanvas close.
- Loading and error states for async menu loading.
- Accessibility: ARIA roles, keyboard navigation, visually hidden text for spinners.
- Uses Bootstrap 5.3 for all styling.

## UI Design

```txt
------------------------------------------------------
| [User Name]        [Close Button]                  |
------------------------------------------------------
| [Loading Spinner / Error Alert]                    |
| [Menu Items / Sections / Dividers]                 |
|   - Section (collapsible)                          |
|     - Item                                         |
|   - Item                                           |
|   - Divider                                        |
| [Empty State if no items]                          |
------------------------------------------------------
| [Dark/Light Mode Toggle]                           |
| [Logout Button]                                    |
------------------------------------------------------
```

- Uses `.offcanvas`, `.nav`, `.nav-link`, `.nav-section`, `.btn-close`, `.spinner-border`, `.alert`, and other Bootstrap classes.
- Collapsible sections use Bootstrap collapse classes and icons.

## Implementation Rules

- All HTTP requests must use the shared API service (`frontend/app/utils/api.ts`).
- All notifications and error handling must use the Notify Service (`frontend/app/utils/notify.ts`).
- Use Bootstrap 5.3 classes for all layout and UI elements.
- Strictly type all props and logic with TypeScript.
- Provide loading and error states for all async operations.
- Ensure accessibility (ARIA roles, keyboard navigation, visually hidden text for spinners).
- Write tests first in `frontend/tests/` before implementing features.

## Error Handling (optional)

- Displays a Bootstrap warning alert if the `error` prop is set.
- Shows a loading spinner if `isLoading` is true.
- Shows an empty state if there are no menu items and not loading or error.

## Accessibility (optional)

- Uses ARIA attributes for offcanvas and spinner.
- Visually hidden text for loading spinner.
- Keyboard accessible buttons and navigation.

## References

- [Frontend Rules](design/rules-app.md)
- [GitHub Copilot Instructions](.github/copilot-instructions.md)
