<script setup lang="ts">
// Apply authentication middleware to protect this page
definePageMeta({
  middleware: 'auth'
});

import { ref, onMounted, onBeforeUnmount, computed, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import ResourceMasterDetail from '../../components/Resource/MasterDetail.vue';
import ResourceSearch from '../../components/Resource/Search.vue';
import ResourcePagination from '../../components/Resource/PaginationS.vue';
import ResourceHeader from '../../components/Resource/Header.vue';
import ResourceFilter from '../../components/Resource/Filter.vue';
import Toast from '../../components/Toast.vue';
import { useToast } from '~/utils/errorHandling';
import { useApiEndpoint } from '~/composables/useApplicationConfig';
import { useResourceState } from '~/composables/useResourceState';
import { usePageLoading } from '~/composables/usePageLoading';
import { useAppLoading } from '~/composables/useAppLoading';
import { getResourceColumns, type Column } from '~/utils/columnUtils';
import { useApiService } from '~/services/api';
import type { PaginationMeta, FilterChangeEvent } from '~/types/index';

// Client-side rendering flag to prevent hydration mismatches
const isClient = ref(false);

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

// Extract state and actions from resource state composable
const {
  searchQuery,
  isSearching,
  sortField,
  sortDirection,
  isSorting,
  activeFilters,
  activeFilterField,
  isFiltering,
  pagination: resourcePagination,
  hasSearchQuery,
  hasSearchResults,
  hasNoSearchResults,
  hasSortApplied,
  hasActiveFilters,
  hasFiltersOnly,
  activeFiltersCount,
  filterCount,
  currentPage,
  currentPerPage,
  updateSearch,
  updateSort,
  updatePagination,
  updateFilters,
  clearFilters,
  clearFiltersOnly,
  initializeFromURL
} = resourceState;

// Initialize loading helpers
const { withApiLoading, withLoading } = usePageLoading();
const { canShowComponentContent } = useAppLoading();

// Page loading state management
const isPageReady = ref(false);
const canShowPageContent = computed(() => isPageReady.value);
const pageLoadingMessage = ref('Authenticating...');

// State management
const items = ref<ResourceItem[]>([]);
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
  // Always return true during SSR to prevent hydration mismatch
  if (!isClient.value) return true;
  // Only disable during actual loading operations, not initial SSR loading
  return isSearching.value || isSorting.value;
});

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

// Fetch data with multi-field filter support
const fetchDataWithMultiFilters = async (
  page: number = currentPage.value,
  perPage: number = currentPerPage.value,
  search: string = searchQuery.value,
  sort: string = sortField.value,
  direction: 'asc' | 'desc' = sortDirection.value
) => {
  return await withApiLoading(
    async () => {
      // Build query parameters
      const queryParams = new URLSearchParams({
        page: page.toString(),
        per_page: perPage.toString()
      });

      // Add search parameter if provided
      if (search && search.trim()) {
        queryParams.append('search', search.trim());
      }

      // Add multi-field filters
      Object.entries(activeFilters.value).forEach(([field, value]) => {
        if (value && value !== 'all') {
          queryParams.append(field, value);
        }
      });

      // Add sort parameters if provided
      if (sort && sort.trim()) {
        queryParams.append('sort', sort.trim());
        queryParams.append('direction', direction);
      } 
      
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
        to?: number;
      }>(resourceName.value, {
        params: paramsObject
      });

      return apiResponse;
    },
    {
      message: isInitialLoad.value ? `Loading ${resourceTitle.value.toLowerCase()}...` : 'Updating data...',
      type: 'page',
      onSuccess: async (apiResponse: any) => {
        if (!apiResponse) {
          throw new Error('No response received from API');
        }

        error.value = null;
        
        // @ts-ignore - Complex type checking for API response handling
        // Handle API response - check for errors first
        if (apiResponse.error || (apiResponse.data && typeof apiResponse.data === 'object' && !Array.isArray(apiResponse.data) && !apiResponse.data.success && apiResponse.data.error)) {
          const errorInfo = apiResponse.error || (typeof apiResponse.data === 'object' && !Array.isArray(apiResponse.data) ? apiResponse.data?.error : null);
          throw new Error((errorInfo && errorInfo.message) ? errorInfo.message : 'Failed to fetch data');
        }

        const response = apiResponse.data;
        if (!response) {
          throw new Error('No data received from API');
        }

        // Handle response data structure - support both new standardized and legacy formats
        let responseData: any[] = [];
        let responseMeta: any = {};

        // @ts-ignore - Flexible response format handling
        // Check if this is the new standardized response format
        if (typeof response === 'object' && !Array.isArray(response) && 'success' in response) {
          // New standardized format
          if (!response.success) {
            throw new Error(response.error?.message || 'API request failed');
          }
          
          responseData = response.data || [];
          responseMeta = response.pagination || response.meta || {};
        } else if (Array.isArray(response)) {
          // Simple array response (legacy)
          responseData = response;
        } else if (typeof response === 'object' && response.data && Array.isArray(response.data)) {
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

        // Get columns using utility (now properly async)
        try {
          processedColumns.value = await getResourceColumns(resourceName.value, responseData);
        } catch (error) {
          console.warn('Failed to get resource columns:', error);
          processedColumns.value = [];
        }

        // Update pagination data
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

        // Mark initial load as complete
        if (isInitialLoad.value) {
          isInitialLoad.value = false;
        }
      },
      onError: (error) => {
        // Handle different error types
        if (error.message?.includes('404')) {
          error.value = `Resource '${resourceName.value}' not found. Please check if the API endpoint exists.`;
        } else if (error.message?.includes('403')) {
          error.value = `Access denied to '${resourceName.value}' resource.`;
        } else if (error.message?.includes('500')) {
          error.value = `Server error when fetching '${resourceName.value}' data.`;
        } else {
          error.value = error.message || `Failed to fetch ${resourceName.value} data`;
        }
      }
    }
  );
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
    await fetchDataWithMultiFilters(currentPage.value, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
    return;
  }
  
  // This should only be called for creates now
  // Add the item temporarily to the list for immediate feedback
  items.value.unshift({ ...data, selected: false });
  
  // Then refresh in the background to get proper pagination and ordering
  await fetchDataWithMultiFilters(currentPage.value, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
  
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
  fetchDataWithMultiFilters(page, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
};

const handlePerPageChange = async (perPage: number) => {
  await updatePagination(1, perPage); // Reset to first page when changing perPage
  fetchDataWithMultiFilters(1, perPage, searchQuery.value, sortField.value, sortDirection.value);
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
  fetchDataWithMultiFilters(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
};

const handleSearchClear = async () => {
  await updateSearch('');
  fetchDataWithMultiFilters(1, currentPerPage.value, '', sortField.value, sortDirection.value);
};

// Sort handlers using composable
const handleSort = async (column: Column) => {
  // Only allow sorting on sortable columns
  if (!column.sortable) {
    return;
  }

  await updateSort(column.key);
  fetchDataWithMultiFilters(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
};

// Filter handlers using composable
const handleFilterChange = async (event: FilterChangeEvent) => {
  if (!event.field || !event.value) return;
  
  // For single filter policy, clear existing filters and set only the new one
  const newFilters = event.value === 'all' ? {} : { [event.field]: event.value };
  
  await updateFilters(newFilters);
  fetchDataWithMultiFilters(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
};

const handleFilterClearAll = async () => {
  await clearFiltersOnly();
  fetchDataWithMultiFilters(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
};

const handleFiltersClearedEvent = async () => {
  await clearFiltersOnly();
  fetchDataWithMultiFilters(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
};

const handleClearFiltersOnly = async () => {
  await clearFiltersOnly();
  fetchDataWithMultiFilters(1, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
};

const handleClearAllFilters = async () => {
  await clearFilters();
  fetchDataWithMultiFilters(1, currentPerPage.value, '', '', 'asc');
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
  await fetchDataWithMultiFilters(currentPage.value, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
  
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

// Handle page initialization sequence
onMounted(async () => {
  try {
    // Set client flag to enable reactive rendering
    await nextTick();
    isClient.value = true;
    
    // Page structure is ready
    pageLoadingMessage.value = 'Loading resource data...';
    isPageReady.value = true;

    // Small delay to show page structure before components
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Initialize data after page structure is ready
    await initializeFromURL();
    await fetchDataWithMultiFilters(currentPage.value, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
    
    // Mark initial load as complete
    isInitialLoad.value = false;
    
  } catch (err) {
    console.error('Page initialization error:', err);
    showErrorToast('Failed to initialize resource page');
  }
});
</script>

<template>
  <div class="container-fluid pt-3">
    <!-- Initial Loading State - show until client is ready -->
    <div v-if="!isClient || !canShowPageContent" class="d-flex justify-content-center align-items-center py-5" style="min-height: 400px;">
      <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted">{{ pageLoadingMessage }}</p>
      </div>
    </div>

    <!-- Main Content - only show when client is ready -->
    <div v-if="isClient && canShowPageContent">
      <!-- Page Header Skeleton -->
      <div v-if="!canShowComponentContent" class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="placeholder-glow">
            <h2 class="placeholder col-4" style="height: 2.5rem;"></h2>
          </div>
          <div class="d-flex gap-2">
            <div class="placeholder rounded" style="width: 100px; height: 38px;"></div>
            <div class="placeholder rounded" style="width: 100px; height: 38px;"></div>
            <div class="placeholder rounded" style="width: 140px; height: 38px;"></div>
          </div>
        </div>
        
        <!-- Search and Filter Skeleton -->
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="placeholder rounded" style="height: 38px;"></div>
          </div>
          <div class="col-md-6">
            <div class="placeholder rounded" style="height: 38px;"></div>
          </div>
        </div>

        <!-- Table Skeleton -->
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-center align-items-center py-5">
              <div class="text-center">
                <div class="spinner-border text-secondary mb-3" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted">Loading {{ resourceTitle.toLowerCase() }} data...</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content - Only show after components can load -->
      <div v-if="canShowComponentContent">
        <!-- Resource Header Component -->
        <ResourceHeader :resource-name="resourceName" :title="resourceTitle" :loading="false"
          :total-results="pagination.total" :has-filters="hasActiveFilters" :from="pagination.from" :to="pagination.to"
          @create="handleCreate" @export="handleExport" @import="handleImport">
        
        <!-- Filter Component in Header -->
        <template #filter>
          <ResourceFilter 
            :resource="resourceName"
            :loading="isSearching || isSorting || isFiltering"
            :active-filter-field="activeFilterField"
            :is-active="Object.keys(activeFilters).length > 0"
            :filter-count="filterCount"
            @filter-change="handleFilterChange"
            @filter-clear-all="handleFilterClearAll"
            @filters-cleared="handleFiltersClearedEvent"
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

        </ResourceHeader>
    
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
          @click="() => fetchDataWithMultiFilters(currentPage, currentPerPage, searchQuery, sortField, sortDirection)"
          :disabled="false">
          <i class="bi bi-arrow-clockwise me-1"></i>
          Try Again
        </button>
      </div>
    </div>    <!-- Master-Detail View -->
    <ResourceMasterDetail ref="masterDetailRef" v-if="items.length > 0" :resource="resourceName"
      :items="items" :loading="false" :error="error" :columns="processedColumns" :pagination="pagination"
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
        <ResourcePagination v-if="items.length > 0" :current-page="pagination.currentPage"
          :total-pages="pagination.totalPages" :per-page="pagination.perPage" :total="pagination.total"
          :from="pagination.from" :to="pagination.to" :has-next-page="pagination.hasNextPage"
          :has-prev-page="pagination.hasPrevPage" :loading="isSearching || isSorting"
          :per-page-options="[10, 20, 50, 100]" @page-change="handlePageChange" @per-page-change="handlePerPageChange" />
      </div>
    </div>
      
    <!-- Toast Notifications -->
    <Toast />
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
