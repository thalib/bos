# Resource Store Design Specification

Centralized Pinia store for managing resource list state, including data, columns, loading, error, sort, filters, and pagination. Enables shared, reactive state across pages and components (List, Filter, Pagination, etc.).

**File Location:** `frontend/app/stores/storeResource.ts`

## Store Structure

```typescript
import { defineStore } from "pinia";
// All API calls must use useApiService from frontend/app/utils/api.ts
// All notifications must use useNotifyService from frontend/app/utils/notify.ts

export const useStoreResource = defineStore("resource", {
  state: () => ({
    success: true,
    message: "",
    data: [], // array of resource objects
    pagination: {
      totalItems: 0,
      currentPage: 1,
      itemsPerPage: 15,
      totalPages: 1,
      urlPath: "",
      urlQuery: null,
      nextPage: null,
      prevPage: null,
    },
    search: null,
    sort: { column: "", dir: "asc" },
    filters: { applied: null, available: null },
    schema: null,
    columns: [],
    notifications: [],
    error: null,
    isLoading: false,
    hasError: false,
  }),
  actions: {
    async fetchData(params) {
      // TDD: Write tests for fetchData before implementation
      // Use useApiService() for all API calls
      // Use useNotifyService() for all notifications and error handling
      // Set isLoading, hasError, error, notifications, etc. accordingly
    },
    setSort(column: string, dir: "asc" | "desc") {
      // TDD: Write tests for sort state changes
      this.sort = { column, dir };
    },
    setFilters(filters: Record<string, any>) {
      // TDD: Write tests for filter state changes
      this.filters.applied = filters;
    },
    setPagination(page: number, perPage: number) {
      // TDD: Write tests for pagination state changes
      this.pagination.currentPage = page;
      this.pagination.itemsPerPage = perPage;
    },
    setSearch(search: string) {
      // TDD: Write tests for search state changes
      this.search = search;
    },
    setColumns(columns: any[]) {
      // TDD: Write tests for columns state changes
      this.columns = columns;
    },
    setSchema(schema: any) {
      // TDD: Write tests for schema state changes
      this.schema = schema;
    },
    setNotifications(notifications: any[]) {
      // TDD: Write tests for notifications state changes
      this.notifications = notifications;
    },
    setError(error: any) {
      // TDD: Write tests for error state changes
      this.error = error;
      this.hasError = !!error;
    },
    setMessage(message: string) {
      // TDD: Write tests for message state changes
      this.message = message;
    },
    // Add more actions as needed
  },
});
```

## Usage

```typescript
import { useStoreResource } from "@/stores/storeResource";
const resourceStore = useStoreResource();
```

## Features and Implementation Rules

- Follows Test-Driven Development (TDD) and Design-Driven Development (DDD) as defined in [Frontend Rules](design/rules-app.md).
- Strict TypeScript typing for all state, actions, and usage.
- All HTTP requests must use the shared API service (`frontend/app/utils/api.ts`).
- All notifications and error handling must use the Notify Service (`frontend/app/utils/notify.ts`).
- Use Bootstrap 5.3 classes for any UI state (if applicable).
- Write tests first in `frontend/tests/` before implementing features.
- Store is imported and used in any component or page; components read/write state directly (no prop drilling).
- All resource list state (data, loading, error, sort, filters, pagination) is managed centrally in the store; components must not duplicate or fetch data independently.
- Ensures a single source of truth and shared, reactive state for List, Filter, Pagination, and other components.
