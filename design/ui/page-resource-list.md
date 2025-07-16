# Resource List Page Documentation

## Overview

The `[resource].vue` page provides a comprehensive interface for managing resources with a data-driven approach. It receives the complete API response and delegates functionality to self-contained components that handle their own logic based on the API data structure.

## Features

- Dynamic resource management based on route parameters
- Self-contained components that handle API response nodes
- Integrated search, filtering, and pagination
- Responsive design for all device types
- Comprehensive error handling and loading states
- Real-time data updates with optimistic UI
- Bootstrap 5.3 based styling

## API Integration

The page integrates with the standardized API endpoints following the design specification:

- **GET /api/v1/{resource}**: Retrieves resource list with pagination, sorting, filtering, and search
- **POST /api/v1/{resource}**: Creates new resources
- **PUT /api/v1/{resource}/{id}**: Updates existing resources
- **DELETE /api/v1/{resource}/{id}**: Deletes resources

## Data Flow

The page receives the complete API response and passes specific nodes to components:

```json
{
  "success": true,
  "message": "Resources retrieved successfully",
  "data": [ /* resource items */ ],
  "pagination": { /* pagination metadata */ },
  "search": "query string" | null,
  "sort": { "column": "name", "dir": "asc" } | null,
  "filters": { 
    "applied": { "field": "status", "value": "active" },
    "available": [ /* filter options */ ]
  } | null,
  "schema": [ /* form schema */ ] | null,
  "columns": [ /* table columns */ ],
  "notifications": [ /* user notifications */ ] | null,
  "error": { /* error details */ } | null
}
```

## Component Integration

### Header Component
```vue
<Header
  :resourceTitle="resourceTitle"
  :resourceCount="response.pagination?.totalItems"
  :loading="isLoading"
  @action-create="handleCreate"
  @action-import="handleImport"
  @action-export="handleExport"
>
  <template #filters>
    <Filter
      :filters="response.filters"
      :loading="isLoading"
      @filter-change="handleFilterChange"
      @filter-clear="handleFilterClear"
    />
  </template>
  
  <template #search>
    <Search
      :search="response.search"
      :loading="isLoading"
      @search-change="handleSearchChange"
      @search-clear="handleSearchClear"
    />
  </template>
</Header>
```

### List Component
```vue
<List
  :data="response.data"
  :columns="response.columns"
  :sort="response.sort"
  :loading="isLoading"
  :error="response.error"
  @item-click="handleItemClick"
  @item-select="handleItemSelect"
  @sort-change="handleSortChange"
/>
```

### Pagination Component
```vue
<PaginationS
  :pagination="response.pagination"
  :loading="isLoading"
  @page-change="handlePageChange"
  @per-page-change="handlePerPageChange"
/>
```

### Table Sorting Component
```vue
<TableSorting
  :sort="response.sort"
  :filters="response.filters"
  :search="response.search"
  :loading="isLoading"
  @sort-clear="handleSortClear"
  @filters-clear="handleFiltersClear"
  @search-clear="handleSearchClear"
/>
```

### Form Component (for Create/Edit)
```vue
<Form
  :schema="response.schema"
  :data="selectedItem"
  :loading="isFormLoading"
  :mode="formMode"
  :resourceTitle="resourceTitle"
  @form-submit="handleFormSubmit"
  @form-cancel="handleFormCancel"
  @form-error="handleFormError"
/>
```

## Event Handling

### Search Events
```javascript
const handleSearchChange = ({ query }) => {
  // Update URL parameters and fetch data
  updateUrlParams({ search: query, page: 1 });
  fetchData();
};

const handleSearchClear = () => {
  updateUrlParams({ search: null, page: 1 });
  fetchData();
};
```

### Filter Events
```javascript
const handleFilterChange = ({ field, value }) => {
  updateUrlParams({ 
    filter: `${field}:${value}`, 
    page: 1 
  });
  fetchData();
};

const handleFilterClear = ({ field }) => {
  updateUrlParams({ filter: null, page: 1 });
  fetchData();
};
```

### Pagination Events
```javascript
const handlePageChange = ({ page }) => {
  updateUrlParams({ page });
  fetchData();
};

const handlePerPageChange = ({ perPage }) => {
  updateUrlParams({ per_page: perPage, page: 1 });
  fetchData();
};
```

### Sort Events
```javascript
const handleSortChange = ({ column, direction }) => {
  updateUrlParams({ 
    sort: column, 
    dir: direction,
    page: 1 
  });
  fetchData();
};
```

### Item Events
```javascript
const handleItemClick = ({ item, index }) => {
  // Navigate to detail view or open modal
  navigateTo(`/${resourceName}/${item.id}`);
};

const handleItemSelect = ({ selectedItems }) => {
  // Handle bulk operations
  selectedItems.value = selectedItems;
};
```

### Form Events
```javascript
const handleFormSubmit = ({ data, mode }) => {
  if (mode === 'create') {
    createResource(data);
  } else {
    updateResource(data);
  }
};

const handleFormError = ({ errors }) => {
  // Display validation errors
  showToast('error', 'Please correct the errors and try again.');
};
```

## State Management

```javascript
const state = reactive({
  response: null,
  isLoading: false,
  selectedItem: null,
  formMode: 'create',
  isFormLoading: false,
  urlParams: {
    page: 1,
    per_page: 15,
    sort: null,
    dir: 'asc',
    filter: null,
    search: null
  }
});
```

## API Communication

```javascript
const fetchData = async () => {
  try {
    state.isLoading = true;
    const params = new URLSearchParams(state.urlParams);
    const response = await $fetch(`/api/v1/${resourceName}?${params}`);
    
    if (response.success) {
      state.response = response;
      // Handle notifications
      if (response.notifications) {
        response.notifications.forEach(notification => {
          showToast(notification.type, notification.message);
        });
      }
    } else {
      handleError(response.error);
    }
  } catch (error) {
    handleError(error);
  } finally {
    state.isLoading = false;
  }
};
```

## Error Handling

```javascript
const handleError = (error) => {
  if (error.code === 'UNAUTHORIZED') {
    // Redirect to login
    navigateTo('/login');
  } else if (error.code === 'FORBIDDEN') {
    showToast('error', 'You do not have permission to access this resource.');
  } else {
    showToast('error', error.message || 'An unexpected error occurred.');
  }
};
```

## URL Management

```javascript
const updateUrlParams = (params) => {
  Object.assign(state.urlParams, params);
  
  const searchParams = new URLSearchParams();
  Object.entries(state.urlParams).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      searchParams.set(key, value);
    }
  });
  
  const newUrl = `${window.location.pathname}?${searchParams}`;
  window.history.replaceState({}, '', newUrl);
};
```

## Component Lifecycle

```javascript
onMounted(() => {
  // Parse URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  Object.keys(state.urlParams).forEach(key => {
    if (urlParams.has(key)) {
      state.urlParams[key] = urlParams.get(key);
    }
  });
  
  // Initial data fetch
  fetchData();
});
```

## Toast Notifications

```javascript
const showToast = (type, message) => {
  // Use Toast component or notification service
  $toast.show({
    type,
    message,
    duration: 5000
  });
};
```

## Bootstrap Classes Used

- `container-fluid` for full-width layout
- `row`, `col-*` for responsive grid
- `card`, `card-header`, `card-body` for content containers
- `btn`, `btn-primary`, `btn-outline-secondary` for actions
- `d-flex`, `justify-content-between` for layout
- `mb-3`, `mt-3` for spacing
- `spinner-border` for loading states
- `alert` for error messages

## Notes

- The page acts as a coordinator, delegating functionality to self-contained components
- All API data is passed directly to components as complete nodes
- Components handle their own logic based on the API response structure
- Follows the new API specification for consistent data handling
- Uses Bootstrap 5.3 classes for responsive design
- Implements proper error handling and loading states
- Maintains URL state for bookmarking and navigation
- Provides comprehensive user feedback through notifications
