# Resource List Page Design Specification

This document outlines the design and functionality of the `[resource].vue` page, ensuring consistency with the BOS frontend standards.

**File Location:** `frontend/app/pages/list/[resource].vue`


## Page Structure

```html
<template>
  <!-- Include the main component or layout here -->
  <MainComponent />
</template>
```

The page acts as a self-contained coordinator with the following structure:

- **Mode Determination**: Fetch menu configuration, determine mode (form/document), and load the appropriate components.
- **Component Coordination**: Manage Header, MasterDetail, and Pagination components.
- **URL State Management**: Synchronize route parameters, query parameters, and browser history.
- **Global Event Handling**: Aggregate events, enable cross-component communication, and manage errors.
- **API Integration**: Fetch menu configuration and handle global errors.

## Component Relationships

The following tree diagram represents the relationships between components:

```
Resource List Page
├── Header Component
│   ├── Search Component
│   └── Filter Component
├── MasterDetail Component
│   ├── List Component
│   ├── Form Component
│   └── DocumentView Component
└── Pagination Component
```

the component receive the data from the page and reders it as the data to ui 


## Key Features

### Self-Contained Architecture
- **Component Isolation**: Each child component manages its own state and API calls.
- **Minimal Props**: Components require minimal configuration from the parent.
- **Event Coordination**: The page handles high-level events without managing component internals.

## API Integration

### Error Handling

- Error Handling: Coordinated via `frontend/app/utils/notify.ts`

## Test-Driven Development (TDD)

For detailed TDD guidelines, refer to the [BOS Frontend Rules](design/rules-app.md).