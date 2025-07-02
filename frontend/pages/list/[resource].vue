<script setup lang="ts">
// Apply authentication middleware to protect this page
definePageMeta({
  middleware: 'auth'
});

import { ref, onMounted, onBeforeUnmount, computed, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import ResourceMasterDetail from '../../components/Resource/MasterDetail.vue';
import ResourceSearch from '../../components/Resource/Search.vue';
import ResourcePagination from '../../components/Resource/Pagination.vue';
import ResourceSorting from '../../components/Resource/TableSorting.vue';
import ResourceHeader from '../../components/Resource/Header.vue';
import ResourceFilter from '../../components/Resource/Filter.vue';
import Toast from '../../components/Toast.vue';
import { useToast } from '~/utils/errorHandling';
import { useApiEndpoint } from '~/composables/useApplicationConfig';
import { useResourceState } from '~/composables/useResourceState';
import { useResourceFilter } from '~/composables/useResourceFilter';
import { getResourceColumns, type Column } from '~/utils/columnUtils';
import { useApiService } from '~/services/api';
import type { PaginationMeta } from '~/types';

// Define the Column interface locally
// interface Column {
//   key: string;
//   label?: string;
//   sortable?: boolean;
//   formatter?: (value: any, item?: any) => string;
//   cellClass?: string;
// }

// Extended pagination interface with from/to fields
interface ExtendedPaginationMeta extends PaginationMeta {
  from: number;
  to: number;
}

// Enhanced item interface with selected property for UI state
interface ResourceItem {
  id?: string | number;
  selected?: boolean;
  [key: string]: any;
}

// Get route to extract resource name
const route = useRoute();
const resourceName = computed(() => route.params.resource as string);

// Get runtime config for API base URL
const config = useRuntimeConfig();

// Dynamic title based on resource name
const resourceTitle = computed(() => {
  const name = resourceName.value;
  return name.charAt(0).toUpperCase() + name.slice(1);
});

// Initialize resource state management composable
const resourceState = useResourceState({
  resource: resourceName.value,
  defaults: {
    perPage: 20,
    sortDirection: 'asc'
  },
  persistence: {
    enabled: true
  },
  debounce: {
    enabled: true,
    delay: 300
  }
});

// Initialize filter state management composable
const resourceFilter = useResourceFilter({
  defaultFilter: 'active',
  paramName: 'filter'
});

// Extract state and actions from composable
const {
  searchQuery,
  isSearching,
  sortField,
  sortDirection,
  isSorting,
  pagination: resourcePagination,
  hasSearchQuery,
  hasSearchResults,
  hasNoSearchResults,
  hasSortApplied,
  hasActiveFilters,
  activeFiltersCount,
  currentPage,
  currentPerPage,
  updateSearch,
  updateSort,
  updatePagination,
  clearFilters,
  initializeFromURL
} = resourceState;

// Extract filter state and actions from filter composable
const {
  currentFilter,
  setFilter,
  resetFilter
} = resourceFilter;

// State management
const items = ref<ResourceItem[]>([]);
const loading = ref(true); // Start as true to prevent content flash
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const searchComponent = ref<InstanceType<typeof ResourceSearch>>();
const masterDetailRef = ref<InstanceType<typeof ResourceMasterDetail>>();

// Track if this is the initial load to prevent flash
const isInitialLoad = ref(true);

// Initialize API service for authenticated requests
const apiService = useApiService();

// Computed property for search disabled state to handle SSR hydration
const isSearchDisabled = computed(() => {
  // Only disable during actual loading operations, not initial SSR loading
  return isSearching.value || isSorting.value;
});

// Clear all filters handler using composable
const handleClearAllFilters = async () => {
  await clearFilters();
  await resetFilter();
  // Fetch clean data
  fetchData(1, currentPerPage.value, '', '', 'asc', currentFilter.value);
};

// Filter change handler
const handleFilterChange = async (filterValue: 'all' | 'active' | 'inactive') => {
  await setFilter(filterValue);
  fetchData(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value, filterValue);
};

// Use toast utility instead of direct toast component ref
const { showErrorToast, showSuccessToast } = useToast();

// Dynamic columns based on API response data structure
const processedColumns = ref<Column[]>([]);

// Computed pagination mapping from composable to existing interface
const pagination = computed<ExtendedPaginationMeta>(() => ({
  currentPage: resourcePagination.value.currentPage || 1,
  totalPages: resourcePagination.value.totalPages || 1,
  perPage: resourcePagination.value.perPage || 20,
  total: resourcePagination.value.total || 0,
  hasNextPage: resourcePagination.value.hasNextPage || false,
  hasPrevPage: resourcePagination.value.hasPrevPage || false,
  nextPage: resourcePagination.value.hasNextPage ? (resourcePagination.value.currentPage || 1) + 1 : null,
  prevPage: resourcePagination.value.hasPrevPage ? (resourcePagination.value.currentPage || 1) - 1 : null,
  from: resourcePagination.value.from || 0,
  to: resourcePagination.value.to || 0
}));

// Dynamic API endpoint based on resource
const apiEndpoint = computed(() => useApiEndpoint(resourceName.value));

// Fetch data using Nuxt's $fetch
const fetchData = async (
  page: number = currentPage.value,
  perPage: number = currentPerPage.value,
  search: string = searchQuery.value,
  sort: string = sortField.value,
  direction: 'asc' | 'desc' = sortDirection.value,
  filter: string = currentFilter.value
) => {
  loading.value = true;
  error.value = null;
  try {
    // Build query parameters
    const queryParams = new URLSearchParams({
      page: page.toString(),
      per_page: perPage.toString()
    });

    // Add search parameter if provided
    if (search && search.trim()) {
      queryParams.append('search', search.trim());
    }

    // Add filter parameter if not 'all'
    if (filter && filter !== 'all') {
      queryParams.append('filter', filter);
    }

    // Add sort parameters if provided
    if (sort && sort.trim()) {
      queryParams.append('sort', sort.trim());
      queryParams.append('direction', direction);    } 
    
    const fullUrl = `${apiEndpoint.value}?${queryParams.toString()}`;
        // Use API service instead of $fetch to ensure auth headers are included
    const paramsObject = Object.fromEntries(queryParams);
    const apiResponse = await apiService.request<{
      success?: boolean;
      data?: any[];
      message?: string;
      meta?: any;
      pagination?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        has_more_pages: boolean;
        path: string;
        first_page_url: string;
        last_page_url: string;
        next_page_url: string | null;
        prev_page_url: string | null;
      };
      error?: {
        code: string;
        message: string;
        details?: any;
        validation_errors?: any;
      };
      // Legacy format support
      current_page?: number;
      last_page?: number;
      per_page?: number;
      total?: number;
      from?: number;
      to?: number;    }>(resourceName.value, {
      params: paramsObject
    });

    // Handle API response - check for errors first
    if (apiResponse.error || (apiResponse.data && !apiResponse.data.success && apiResponse.data.error)) {
      const errorInfo = apiResponse.error || apiResponse.data?.error;
      throw new Error((errorInfo && errorInfo.message) ? errorInfo.message : 'Failed to fetch data');
    }

    const response = apiResponse.data;    if (!response) {
      throw new Error('No data received from API');
    }    // Handle response data structure - support both new standardized and legacy formats
    let responseData: any[] = [];
    let responseMeta: any = {};

    // Check if this is the new standardized response format
    if (response.success !== undefined) {
      // New standardized format
      if (!response.success) {
        throw new Error(response.error?.message || 'API request failed');
      }
      
      responseData = response.data || [];
      responseMeta = response.pagination || response.meta || {};
    } else if (Array.isArray(response)) {
      // Simple array response (legacy)
      responseData = response;
    } else if (response.data && Array.isArray(response.data)) {
      // Laravel paginated response (legacy)
      responseData = response.data;

      // Laravel puts pagination data directly in the response root, not in meta
      if (response.current_page !== undefined) {
        responseMeta = {
          current_page: response.current_page,
          last_page: response.last_page,
          per_page: response.per_page,
          total: response.total,
          from: response.from,
          to: response.to
        };
      } else {
        // Fallback to nested meta/pagination structure
        responseMeta = response.meta || response.pagination || {};
      }
    } else {
      // Object response - convert to array
      responseData = [response];
    }

    // Map items and add selected property for UI state
    items.value = responseData.map((item: any) => ({ ...item, selected: false }));

    // Get columns using utility
    processedColumns.value = await getResourceColumns(resourceName.value, items.value);    // Update pagination data
    if (responseMeta && Object.keys(responseMeta).length > 0) {
      resourcePagination.value = {
        currentPage: responseMeta.current_page || page,
        totalPages: responseMeta.last_page || 1,
        perPage: responseMeta.per_page || perPage,
        total: responseMeta.total || responseData.length,
        hasNextPage: responseMeta.has_more_pages !== undefined ? responseMeta.has_more_pages : (responseMeta.current_page < responseMeta.last_page),
        hasPrevPage: responseMeta.current_page > 1,
        nextPage: responseMeta.has_more_pages !== undefined ? 
          (responseMeta.has_more_pages ? (responseMeta.current_page || 1) + 1 : null) : 
          (responseMeta.current_page < responseMeta.last_page ? (responseMeta.current_page || 1) + 1 : null),
        prevPage: responseMeta.current_page > 1 ? (responseMeta.current_page || 1) - 1 : null,
        from: responseMeta.from || 0,
        to: responseMeta.to || responseData.length
      };
    } else {
      // No pagination metadata - single page
      resourcePagination.value = {
        currentPage: 1,
        totalPages: 1,
        perPage: responseData.length,
        total: responseData.length,
        hasNextPage: false,
        hasPrevPage: false,
        nextPage: null,
        prevPage: null,
        from: responseData.length > 0 ? 1 : 0,
        to: responseData.length
      };
    }

    // Pagination data is already updated above - no need to set currentPage/currentPerPage separately
  } catch (err: any) {
    // Handle different error types
    if (err.status === 404) {
      error.value = `Resource '${resourceName.value}' not found. Please check if the API endpoint exists.`;
    } else if (err.status === 403) {
      error.value = `Access denied to '${resourceName.value}' resource.`;
    } else if (err.status === 500) {
      error.value = `Server error when fetching '${resourceName.value}' data.`;
    } else {
      error.value = err.message || `Failed to fetch ${resourceName.value} data`;
    }
    // Show toast notification
    if (error.value) {
      showErrorToast(error.value);
    }
  } finally {
    loading.value = false;
  }
};

// Handle item click from table
const handleItemClick = (item: ResourceItem) => {
  // Item clicked - handled by MasterDetail component
};

// Handle create button click
const handleCreate = () => {
  // Trigger create functionality in MasterDetail component
  if (masterDetailRef.value) {
    masterDetailRef.value.handleCreate();
  }
};

// Handle export action
const handleExport = () => {
  // TODO: Implement export functionality
  console.log('Export requested for', resourceName.value);
  showSuccessToast(`${resourceTitle.value} export will be available soon`);
};

// Handle import action
const handleImport = () => {
  // TODO: Implement import functionality
  console.log('Import requested for', resourceName.value);
  showSuccessToast(`${resourceTitle.value} import will be available soon`);
};

// Handle success events - now only for creates since updates are handled locally
const handleSuccess = async (data: any) => {
  if (!data || !data.id) {
    // No data or ID - fallback to full refresh
    await fetchData(currentPage.value, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value, currentFilter.value);
    return;
  }
  
  // This should only be called for creates now
  // Add the item temporarily to the list for immediate feedback
  items.value.unshift({ ...data, selected: false });
  
  // Then refresh in the background to get proper pagination and ordering
  await fetchData(currentPage.value, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value, currentFilter.value);
  
  // Select the newly created item
  if (masterDetailRef.value && data.id) {
    await nextTick();
    masterDetailRef.value.directRestoreSelection(data.id);
  }
};

// Handle in-memory updates from MasterDetail component
const handleUpdateItemInMemory = (data: any) => {
  if (!data || !data.id) {
    return;
  }
  
  // Find and update the item in the list in-memory
  const existingItemIndex = items.value.findIndex(item => item.id == data.id);
  
  if (existingItemIndex !== -1) {
    // Update the item in the list in-memory
    items.value[existingItemIndex] = { 
      ...items.value[existingItemIndex], 
      ...data,
      selected: items.value[existingItemIndex].selected // Preserve UI state
    };
  }
};

// Handle pagination with composable
const handlePageChange = async (page: number) => {
  await updatePagination(page);
  fetchData(page, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value, currentFilter.value);
};

const handlePerPageChange = async (perPage: number) => {
  await updatePagination(1, perPage); // Reset to first page when changing perPage
  fetchData(1, perPage, searchQuery.value, sortField.value, sortDirection.value, currentFilter.value);
};

// Search handlers using composable
const handleSearchUpdate = (query: string) => {
  // This is handled by the v-model binding to searchQuery from composable
};

const handleSearch = async (query: string) => {
  if (!query.trim()) {
    await handleSearchClear();
    return;
  }

  await updateSearch(query);
  fetchData(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value, currentFilter.value);
};

const handleSearchClear = async () => {
  await updateSearch('');
  fetchData(1, currentPerPage.value, '', sortField.value, sortDirection.value, currentFilter.value);
};

// Sort handlers using composable
const handleSort = async (column: Column) => {
  // Only allow sorting on sortable columns
  if (!column.sortable) {
    return;
  }

  await updateSort(column.key);
  fetchData(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value, currentFilter.value);
};

const handleClearSort = async () => {
  await updateSort('');
  fetchData(1, currentPerPage.value, searchQuery.value, '', 'asc', currentFilter.value);
};

// Handle error events
const handleError = (err: any) => {
  let errorMessage = '';
  if (err instanceof Error) {
    errorMessage = err.message;
  } else if (typeof err === 'string') {
    errorMessage = err;
  } else if (err.message) {
    errorMessage = err.message;
  } else {
    errorMessage = `An error occurred with ${resourceTitle.value}`;
  }

  error.value = errorMessage;
  // Show error toast
  showErrorToast(errorMessage);
};

// Handle update error events
const handleUpdateError = (errorValue: string | null) => {
  error.value = errorValue;
};

// Fetch data on component mount and add keyboard shortcuts
onMounted(async () => {
  // Wait for authentication check to complete before showing content
  await nextTick();
  
  // Initialize from URL state
  initializeFromURL();
  
  // Fetch initial data
  await fetchData(currentPage.value, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value, currentFilter.value);
  
  // Mark initial load as complete
  isInitialLoad.value = false;

  // Add keyboard shortcuts
  document.addEventListener('keydown', handleGlobalKeydown);
});

// Set page title dynamically
useHead({
  title: `${resourceTitle.value} - Dashboard`
});

// Keyboard shortcuts
const handleGlobalKeydown = (event: KeyboardEvent) => {
  // Ctrl/Cmd + K to focus search
  if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
    event.preventDefault();
    if (searchComponent.value) {
      searchComponent.value.focus();
    }
  }
};

// Cleanup keyboard event listeners
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleGlobalKeydown);
});
</script>

<template>
  <div class="container-fluid pt-3">
    <!-- Initial Loading State - Show spinner during authentication and initial data load -->
    <div v-if="isInitialLoad" class="d-flex justify-content-center align-items-center py-5" style="min-height: 400px;">
      <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted">Loading {{ resourceTitle.toLowerCase() }}...</p>
      </div>
    </div>

    <!-- Main Content - Only show after initial load is complete -->
    <template v-else>
      <!-- Global Loading Overlay for Actions -->
      <div v-if="isSearching || isSorting"
        class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-10"
        style="z-index: 1050;">
        <div class="bg-white rounded shadow p-3 d-flex align-items-center">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
          <span v-if="isSearching">Searching...</span>
          <span v-else-if="isSorting">Sorting...</span>
        </div>
      </div>
      
      <!-- Resource Header Component -->
      <ResourceHeader :resource-name="resourceName" :title="resourceTitle" :loading="loading"
        :total-results="pagination.total" :has-filters="hasActiveFilters" :from="pagination.from" :to="pagination.to"
        @create="handleCreate" @export="handleExport" @import="handleImport">
      
      <!-- Filter Component in Header -->
      <template #filter>
        <ResourceFilter 
          :model-value="currentFilter" 
          :loading="loading || isSearching || isSorting"
          @filter-change="handleFilterChange" 
        />
      </template>

      <!-- Search Component in Header -->
      <template #search>
        <ResourceSearch ref="searchComponent" v-model="searchQuery" :loading="isSearching" :disabled="isSearchDisabled"
          @search="handleSearch" @clear="handleSearchClear"><template #search-info="{ query }">
            <!-- Empty template - search info handled elsewhere -->
          </template>
        </ResourceSearch>
      </template>

      <!-- Custom Action Indicators -->
      <template #indicators>
        <span v-if="isSearching" class="badge bg-primary">
          <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
          Searching...
        </span>
        <span v-if="isSorting" class="badge bg-secondary">
          <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
          Sorting...
        </span>
      </template>
    </ResourceHeader>    <!-- Active Sorting Display Component -->
    <div v-if="!error || !error.includes('not found')" class="mb-4">
      <ResourceSorting :search-query="searchQuery" :sort-field="sortField" :sort-direction="sortDirection"
        :loading="loading || isSearching || isSorting" @clear-search="handleSearchClear" @clear-sort="handleClearSort"
        @clear-all="handleClearAllFilters" />
    </div>

    <!-- Error Alert for Resource Not Found -->
    <div v-if="error && error.includes('not found')" class="alert alert-warning" role="alert">
      <h4 class="alert-heading">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Resource Not Available
      </h4>
      <p>{{ error }}</p>
      <hr>
      <p class="mb-0">
        <small class="text-muted">
          Make sure the API endpoint <code>{{ apiEndpoint }}</code> exists and is accessible.
        </small>
      </p>
    </div>    <!-- Enhanced Error State -->
    <div v-if="error && !error.includes('not found')" class="alert alert-danger d-flex align-items-start"
      role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
      <div class="flex-grow-1">
        <h6 class="alert-heading mb-2">Error Loading Data</h6>
        <p class="mb-2">{{ error }}</p>
        <button type="button" class="btn btn-outline-danger btn-sm"
          @click="() => fetchData(currentPage, currentPerPage, searchQuery, sortField, sortDirection)"
          :disabled="loading">
          <i class="bi bi-arrow-clockwise me-1"></i>
          Try Again
        </button>
      </div>
    </div>    <!-- Master-Detail View -->
    <ResourceMasterDetail ref="masterDetailRef" v-if="!loading || items.length > 0" :resource="resourceName"
      :items="items" :loading="loading" :error="error" :columns="processedColumns" :pagination="pagination"
      :show-pagination="false" :resource-title="resourceTitle" :search-query="searchQuery"
      :has-search-results="hasSearchResults" :has-no-search-results="hasNoSearchResults" :sort-field="sortField"
      :sort-direction="sortDirection" @itemClick="handleItemClick" @create="handleCreate"
      @update:error="handleUpdateError"
      @success="handleSuccess" @updateItemInMemory="handleUpdateItemInMemory" @error="handleError" @sort="handleSort">
      <!-- Enhanced Empty State Slot -->
      <template #empty-state>
        <div class="text-center py-5">
          <div v-if="hasNoSearchResults" class="mb-4">
            <i class="bi bi-search display-1 text-muted mb-3"></i>
            <h4 class="text-muted">No Results Found</h4>
            <p class="text-muted mb-4">
              No {{ resourceTitle.toLowerCase() }} match your search for "<strong>{{ searchQuery }}</strong>"
            </p>
            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-outline-primary" @click="handleSearchClear">
                <i class="bi bi-x-circle me-1"></i>
                Clear Search
              </button>
              <button type="button" class="btn btn-primary" @click="handleCreate">
                <i class="bi bi-plus-circle me-1"></i>
                Create New {{ resourceTitle }}
              </button>
            </div>
          </div>

          <div v-else-if="hasActiveFilters" class="mb-4">
            <i class="bi bi-funnel display-1 text-muted mb-3"></i>
            <h4 class="text-muted">No Filtered Results</h4>
            <p class="text-muted mb-4">
              No {{ resourceTitle.toLowerCase() }} match your current filters
            </p>
            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-outline-primary" @click="handleClearAllFilters">
                <i class="bi bi-funnel-fill me-1"></i>
                Clear All Filters
              </button>
              <button type="button" class="btn btn-primary" @click="handleCreate">
                <i class="bi bi-plus-circle me-1"></i>
                Create New {{ resourceTitle }}
              </button>
            </div>
          </div>

          <div v-else class="mb-4">
            <i class="bi bi-inbox display-1 text-muted mb-3"></i>
            <h4 class="text-muted">No {{ resourceTitle }} Found</h4>
            <p class="text-muted mb-4">
              Get started by creating your first {{ resourceTitle.toLowerCase() }}
            </p>
            <button type="button" class="btn btn-primary btn-lg" @click="handleCreate">
              <i class="bi bi-plus-circle me-2"></i>
              Create Your First {{ resourceTitle }}
            </button>
          </div>
        </div>
      </template>
    </ResourceMasterDetail>

    <!-- Pagination Component -->
    <ResourcePagination v-if="!loading && items.length > 0" :current-page="pagination.currentPage"
      :total-pages="pagination.totalPages" :per-page="pagination.perPage" :total="pagination.total"
      :from="pagination.from" :to="pagination.to" :has-next-page="pagination.hasNextPage"
      :has-prev-page="pagination.hasPrevPage" :loading="loading || isSearching || isSorting"
      :per-page-options="[10, 20, 50, 100]" @page-change="handlePageChange" @per-page-change="handlePerPageChange" />
      
    <!-- Toast Notifications -->
    <Toast />
    </template>
  </div>
</template>

<style scoped>
/* Only use custom CSS when absolutely necessary */

/* Responsive design improvements */
@media (max-width: 768px) {
  .container-fluid {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  h2 {
    font-size: 1.5rem;
  }
}

@media (max-width: 576px) {
  .btn-sm {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>
