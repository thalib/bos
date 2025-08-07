# Login Component Design Specification

- The Login component provides a user interface for authentication, allowing users to log in with their credentials. It includes fields for username, password, and a "Remember me" option. It also handles form validation, error display, and loading states.
- This component is essential for user authentication and redirects authenticated users to the dashboard or a specified path.

**File Location:** `frontend/app/components/Common/Login.vue`

## Component Structure

Below is the exact structure and an example of how the component should be used:

```html
<Login />
```

- **Props:** None
- **Events:** None
- **Composables Used:**
  - `useAuthService`: Handles authentication logic.
  - `useNotifyService`: Manages notifications.
  - `useRoute`: Provides route information.

## Features

- Displays a login form with fields for username, password, and a "Remember me" checkbox.
- Validates form inputs with error messages for invalid or missing fields.
- Shows a loading spinner during the login process.
- Redirects authenticated users to the dashboard or a specified path.
- Handles login errors and displays appropriate notifications.

## UI Design

```txt
Container
└── Row (centered)
    └── Column (responsive)
        └── Card
            ├── Card Header (Title)
            └── Card Body
                ├── Username Field
                ├── Password Field
                ├── Remember Me Checkbox
                └── Submit Button
```

- **Bootstrap Classes:**
  - `container`, `row`, `col-md-6`, `col-lg-4`, `card`, `card-header`, `card-body`, `form-control`, `form-check`, `btn btn-primary`, `spinner-border`.
- **Accessibility:**
  - ARIA roles for spinner and error messages.
  - Keyboard navigation support.

## Implementation Rules

- All HTTP requests must use the shared API service (`frontend/app/utils/api.ts`).
- All notifications and error handling must use the Notify Service (`frontend/app/utils/notify.ts`).
- Use Bootstrap 5.3 classes for all layout and UI elements.
- Strictly type all props and logic with TypeScript.
- Provide loading and error states for all async operations.
- Ensure accessibility (ARIA roles, keyboard navigation).
- Write tests first in `frontend/tests/` before implementing features.

## Error Handling

- Displays error messages for invalid username or password inputs.
- Shows a notification if login fails due to incorrect credentials or server issues.

## Accessibility

- ARIA attributes for spinner (`role="status"`) and error messages.
- Keyboard navigation for form fields and buttons.

## Example Usage

```html
<Login />
```

## References

- [Frontend Rules](design/rules-app.md)
- [GitHub Copilot Instructions](.github/copilot-instructions.md)