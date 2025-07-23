# Navbar Component Design Specification

- The `Navbar` component is a key UI element that provides navigation and user interaction capabilities. It dynamically updates its content based on the user's authentication state and supports toggling between light and dark themes. The component is designed for Nuxt 4, uses strict TypeScript, and follows the BOS project guidelines for structure, testing, and styling.

**File Location:** `frontend/app/components/Menu/Navbar.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Navbar :title="'Home'" />
```

- **Props:**
  - `title` (String, required): The name of the current page to display in the navbar.
- **Events:** None

## Child Components

```txt
Page
└── Navbar
    └── Sidebar
```

- **Sidebar:**
  - Design specification reference: `design\app\components\Menu\Sidebar.md`
  - File path: `frontend/app/components/Menu/Sidebar.vue`

## Features

- **Authentication Awareness:** Only displayed when the user is authenticated (use composable from `frontend/app/utils/auth.ts`).
- **Loading States:** Provides visual feedback during loading or error states (use shared loading components and notify service from `frontend/app/utils/notify.ts`).
- **Centralized API Calls:** All HTTP requests must use `frontend/app/utils/api.ts`.
- **Bootstrap First:** Uses Bootstrap 5.3 classes for layout and components. Avoid custom CSS unless necessary.
- **TypeScript Strict:** All logic and props must be strictly typed.
- **Accessibility:** Follows ARIA and keyboard navigation best practices.
- **Testing:** Must have tests in `frontend/tests/` before implementation.

## UI Design

```txt
Navbar
├── Left Section
│   ├── Menu Toggle Button (Bootstrap icon: <i class="bi bi-list"></i>)
│   └── Current Page Name (from `title` prop)
└── Right Section
    └── User Information Dropdown Toggle Button (Bootstrap icon: <i class="bi bi-person-circle"></i>, uses Bootstrap Dropdown)
```

- **Navbar:** Uses [Bootstrap Navbar](https://getbootstrap.com/docs/5.3/components/navbar/)
- **Menu Toggle Button:**
  - Uses `<i class="bi bi-list"></i>` icon
- **User Information Dropdown Button:**
  - Uses [Bootstrap Dropdown](https://getbootstrap.com/docs/5.3/components/dropdowns/)
  - Uses `<i class="bi bi-person-circle"></i>` icon
- **Current Page Name:**
  - Displays the current page name from the `title` prop

## Implementation Rules

- All HTTP requests must use the shared API service (`frontend/app/utils/api.ts`).
- All notifications and error handling must use the Notify Service (`frontend/app/utils/notify.ts`).
- Use Bootstrap 5.3 classes for all layout and UI elements.
- Strictly type all props and logic with TypeScript.
- Provide loading and error states for all async operations.
- Ensure accessibility (ARIA roles, keyboard navigation).
- Write tests first in `frontend/tests/` before implementing features.

## References

- [Frontend Rules](design/rules-app.md)
- [GitHub Copilot Instructions](.github/copilot-instructions.md)
