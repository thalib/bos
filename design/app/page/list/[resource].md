# Resource List Page Design Specification

This document outlines the design and functionality of the `[resource].vue` page, ensuring consistency with the BOS frontend standards.

**File Location:** `frontend/app/pages/list/[resource].vue`

## Component Relationships

Component Relationships (Tree Diagram):

Resource List Page
├── Header Component (`design/app/components/Resource/Header.md`)
│ ├── Search Component (`design/app/components/Resource/Search.md`)
│ └── Filter Component (`design/app/components/Resource/Filter.md`)
├── MasterDetail Component (`design/app/components/Resource/MasterDetail.md`)
│ ├── List Component (`design/app/components/Resource/List.md`)
│ ├── Form Component (`design/app/components/Resource/Form.md`)
│ └── DocumentView Component (`design/app/components/Resource/DocumentView.md`)
└── Pagination Component (`design/app/components/Resource/Pagination.md`)

## Mode Determination Logic

The page determines whether to use Form or DocumentView components based on menu configuration:

## Key Features

- **Test-Driven Development (TDD)**: Follow the [BOS Frontend Rules](design/rules-app.md#test-driven-development-tdd) for detailed guidelines and requirements.
- **Centralized State Management**: Use the Pinia store (`frontend/app/stores/storeResource.ts`) to manage resource list state, including data, columns, loading, error, sort, filters, and pagination.
- **API Integration**: All HTTP requests **must** use the shared API service (`frontend/app/utils/api.ts`). This ensures consistent authentication and error handling. **Never** use direct `$fetch`, `fetch`, or other HTTP clients.
- **Error Handling and Notifications**: All error handling and notifications must use the shared notification service (`frontend/app/utils/notify.ts`).
- **URL State Management**: Synchronize child component states with the URL to support browser navigation and allow bookmarkable states.
