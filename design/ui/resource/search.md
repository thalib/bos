# Search Component Documentation

## Overview
The `Search.vue` component provides a search input field with real-time query handling. It receives the search value from the API response and manages search interactions internally.

## Features
- Real-time search input with debounce functionality
- Search button for explicit search triggering
- Clear button for resetting search
- Displays search result information
- Handles loading states during search
- Self-contained search logic
- Bootstrap-based responsive design

## Props
- `search` _(string|null)_: Current search query from API response
- `loading` _(boolean)_: Loading state for the component
- `disabled` _(boolean)_: Whether the search is disabled
- `placeholder` _(string)_: Placeholder text for search input (default: "Search...")
- `debounceMs` _(number)_: Debounce delay in milliseconds (default: 300)
- `minLength` _(number)_: Minimum search length (default: 2)

## Events
- `search-change`: Emitted when search query changes
  - Payload: `{ query: string }`
- `search-clear`: Emitted when search is cleared
- `search-submit`: Emitted when search is explicitly submitted
  - Payload: `{ query: string }`

## API Response Structure
The component expects the `search` prop to match the API response structure:

```json
{
  "search": "mobile phone" | null
}
```

## Usage
```vue
<Search
  :search="response.search"
  :loading="isLoading"
  :disabled="false"
  :placeholder="'Search products...'"
  :debounceMs="300"
  :minLength="2"
  @search-change="handleSearchChange"
  @search-clear="handleSearchClear"
  @search-submit="handleSearchSubmit"
/>
```

## Internal Logic
The component handles:
- Managing search input state with debounce
- Validating search length before emitting events
- Providing visual feedback for search state
- Handling keyboard events (Enter to search, Escape to clear)
- Maintaining focus management for better UX
- Displaying loading states during search operations

## Search Behavior
- **Real-time**: Emits `search-change` events with debounce
- **Explicit**: Emits `search-submit` on Enter key or button click
- **Clear**: Provides easy way to clear search with button or Escape key
- **Validation**: Respects minimum length requirements

## Display Features
- Shows current search query in input field
- Displays search and clear buttons
- Provides loading spinner during search
- Shows search result count (via slot)
- Maintains proper input focus and selection

## Error Handling
- Handles null or undefined search values gracefully
- Provides fallback behavior for invalid search queries
- Shows appropriate feedback for short search terms
- Maintains component stability during errors

## Bootstrap Classes Used
- `input-group` for search input wrapper
- `form-control` for search input styling
- `btn`, `btn-outline-secondary`, `btn-primary` for buttons
- `spinner-border` for loading states
- `bi` icons for search and clear buttons
- `text-muted` for placeholder and helper text

## Keyboard Shortcuts
- **Enter**: Submit search explicitly
- **Escape**: Clear search input
- **Ctrl+K**: Focus search input (if implemented)

## Notes
- The component is self-contained and manages its own state
- All search logic is handled internally with proper debouncing
- Follows Bootstrap 5.3 patterns for consistent styling
- Provides proper ARIA attributes for accessibility
- Supports keyboard navigation and shortcuts
- Handles various input methods (typing, pasting, etc.)
