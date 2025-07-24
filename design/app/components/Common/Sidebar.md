# Sidebar Component Design Specification

The Sidebar component provides a responsive, off-canvas data-driven navigation menu for the BOS frontend. It displays user info, menu items (including sections, dividers, and regular links). It is designed for use in the main navigation and is optimized for both desktop and mobile.

**File Location:** `frontend/app/components/Menu/Sidebar.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Sidebar />
```

- **Props:** None
- **Events:** None

## Child Components (optional)

```txt
Navbar
└── Sidebar
    └── (uses NuxtLink for navigation)
```

- **NuxtLink:**
  - Used for navigation between routes.

## Features

- Fetch menu items from the API endpoint `/api/menu` (defined in `MenuController.php`).
- Responsive off-canvas sidebar navigation.
- Fetches and displays the logged-in user's name using `authService.getCurrentUser()`.
- Supports menu sections (collapsible), dividers, and regular items.
- Dark/light mode toggle with event emission.
- Loading and error states for async menu loading.
- Accessibility: ARIA roles, keyboard navigation, visually hidden text for spinners.
- Uses Bootstrap 5.3 for all styling.

## API Integration

- Fetch menu data from the API endpoint `/api/menu` using the shared API service (`frontend/app/utils/api.ts`).

  ```json
  [
    {
      "type": "item",
      "id": 1,
      "name": "Home",
      "path": "/",
      "icon": "bi-house",
      "order": 1
    },
    {
      "type": "section",
      "title": "Tools",
      "order": 2,
      "items": [
        {
          "id": 21,
          "name": "Todo",
          "path": "/todo",
          "icon": "bi-check-square"
        }
      ]
    },
    {
      "type": "divider",
      "order": 3
    }
  ]
  ```

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

## Error Handling

- Displays a Bootstrap warning alert if the API request fails.
- Shows a loading spinner while fetching menu data.
- Shows an empty state if there are no menu items and not loading or error.

## Accessibility

- Uses ARIA attributes for offcanvas and spinner.
- Visually hidden text for loading spinner.
- Keyboard accessible buttons and navigation.

## References

- [Frontend Rules](design/rules-app.md)
- [GitHub Copilot Instructions](.github/copilot-instructions.md)
