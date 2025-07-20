# Search Component Design Specification

## Overview

The `Search` component provides a search input field with real-time query handling. It dynamically manages search interactions and displays search results.

```vue
<Search
  :search="searchQuery"
  :loading="false"
  :disabled="false"
  :placeholder="'Search...'"
  :debounceMs="300"
  :minLength="2"
/>
```

## Features

- Real-time search input with debounce functionality.
- Search button for explicit search triggering.
- Clear button for resetting search.
- Displays search result information.
- Handles loading states during search.

## Props

- `search`: Current search query.
- `loading`: Boolean indicating loading state.
- `disabled`: Boolean to disable the search input.
- `placeholder`: Placeholder text for the search input.
- `debounceMs`: Debounce delay in milliseconds.
- `minLength`: Minimum search length.

## Events

- `search-change`: Triggered when the search query changes.
- `search-clear`: Triggered when the search is cleared.
- `search-submit`: Triggered when the search is explicitly submitted.
