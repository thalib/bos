<script setup lang="ts">
// Apply authentication middleware to protect this page
definePageMeta({
  middleware: 'auth'
});

import { ref, onMounted, onBeforeUnmount, computed, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import ResourceMasterDetailDoc from '../../components/Resource/MasterDetailDoc.vue';
import ResourceSearch from '../../components/Resource/Search.vue';
import ResourcePagination from '../../components/Resource/Pagination.vue';
import ResourceSorting from '../../components/Resource/TableSorting.vue';
import ResourceHeader from '../../components/Resource/Header.vue';
import ResourceFilter from '../../components/Resource/Filter.vue';
import Toast from '../../components/Toast.vue';
import { useToast } from '~/utils/errorHandling';
import { useApiEndpoint } from '~/composables/useApplicationConfig';
import { useResourceState } from '~/composables/useResourceState';

import { getResourceColumns } from '~/utils/columnUtils';
import type { Column, PaginationMeta, FilterChangeEvent } from '../../types';
import { useApiService } from '~/services/api';

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

// Extract state and actions from composable
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

// State management
const items = ref<ResourceItem[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const searchComponent = ref<InstanceType<typeof ResourceSearch>>();
const masterDetailRef = ref<InstanceType<typeof ResourceMasterDetailDoc>>();

// Initialize API service for authenticated requests
const apiService = useApiService();

// Computed property for search disabled state to handle SSR hydration
const isSearchDisabled = computed(() => {
  return isSearching.value || isSorting.value;
});

// Use toast utility instead of direct toast component ref
const { showErrorToast, showSuccessToast } = useToast();

// Dynamic columns based on API response data structure
const processedColumns = ref<Column[]>([]);

// Computed pagination mapping from composable to existing interface
const pagination = computed<PaginationMeta>(() => ({
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

    // Add multi-field filters
    Object.entries(activeFilters.value).forEach(([field, value]) => {
      if (value && value !== 'all') {
        console.log(`Adding filter parameter: ${field} = ${value}`);
        queryParams.append(field, value);
      }
    });

    console.log('Query parameters being sent:', Object.fromEntries(queryParams));

    // Add sort parameters if provided
    if (sort && sort.trim()) {
      queryParams.append('sort', sort.trim());
      queryParams.append('direction', direction);
    } 
    
    const fullUrl = `${apiEndpoint.value}?${queryParams.toString()}`;
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
      current_page?: number;
      last_page?: number;
      per_page?: number;
      total?: number;
      from?: number;
      to?: number;
    }>(resourceName.value, {
      params: paramsObject
    });

    // Handle API response - check for errors first
    if (apiResponse.error || (apiResponse.data && !apiResponse.data.success && apiResponse.data.error)) {
      const errorInfo = apiResponse.error || apiResponse.data?.error;
      throw new Error((errorInfo && errorInfo.message) ? errorInfo.message : 'Failed to fetch data');
    }

    const response = apiResponse.data;
    
    if (!response) {
      throw new Error('No data received from API');
    }

    // Handle response data structure - support both new standardized and legacy formats
    let responseData: any[] = [];
    let responseMeta: any = {};

    // Check if this is the new standardized response format
    if (response.success !== undefined) {
      // New standardized format
      responseData = Array.isArray(response.data) ? response.data : [];
      responseMeta = response.meta || response.pagination || {};
    } else {
      // Legacy format support - ensure we get an array
      if (Array.isArray(response.data)) {
        responseData = response.data;
      } else if (Array.isArray(response)) {
        responseData = response;
      } else {
        responseData = [];
      }
      
      responseMeta = {
        current_page: response.current_page || page,
        last_page: response.last_page || 1,
        per_page: response.per_page || perPage,
        total: response.total || 0,
        from: response.from || 0,
        to: response.to || 0
      };
    }

    // Process items data
    items.value = Array.isArray(responseData) 
      ? responseData.map(item => ({ ...item, selected: false }))
      : [];

    // Auto-generate columns if not already set
    if (processedColumns.value.length === 0 && items.value.length > 0) {
      processedColumns.value = await getResourceColumns(resourceName.value, items.value);
    }

    // Update pagination data directly (like in the original list page)
    if (responseMeta && Object.keys(responseMeta).length > 0) {
      resourcePagination.value = {
        currentPage: responseMeta.current_page || page,
        totalPages: responseMeta.last_page || Math.ceil((responseMeta.total || 0) / (responseMeta.per_page || perPage)),
        perPage: responseMeta.per_page || perPage,
        total: responseMeta.total || 0,
        hasNextPage: responseMeta.has_more_pages !== undefined ? responseMeta.has_more_pages : (responseMeta.current_page || page) < (responseMeta.last_page || 1),
        hasPrevPage: (responseMeta.current_page || page) > 1,
        nextPage: responseMeta.has_more_pages !== undefined ? 
          (responseMeta.has_more_pages ? (responseMeta.current_page || 1) + 1 : null) : 
          ((responseMeta.current_page || page) < (responseMeta.last_page || 1) ? (responseMeta.current_page || 1) + 1 : null),
        prevPage: (responseMeta.current_page || page) > 1 ? (responseMeta.current_page || 1) - 1 : null,
        from: responseMeta.from || 0,
        to: responseMeta.to || 0
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

  } catch (err: any) {
    console.error('Error fetching data:', err);
    handleError(err);
  } finally {
    loading.value = false;
  }
};

// Handle search
const handleSearch = async (query: string) => {
  await updateSearch(query);
  fetchDataWithMultiFilters(1, currentPerPage.value, query, sortField.value, sortDirection.value);
};

// Handle search clear
const handleSearchClear = async () => {
  await updateSearch('');
  fetchDataWithMultiFilters(1, currentPerPage.value, '', sortField.value, sortDirection.value);
};

// Handle pagination
const handlePageChange = async (page: number) => {
  fetchDataWithMultiFilters(page, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);
};

// Handle per-page change
const handlePerPageChange = async (perPage: number) => {
  fetchDataWithMultiFilters(1, perPage, searchQuery.value, sortField.value, sortDirection.value);
};

// Handle sorting
const handleSort = async (column: Column) => {
  const newDirection = (sortField.value === column.key && sortDirection.value === 'asc') ? 'desc' : 'asc';
  await updateSort(column.key, newDirection);
  fetchDataWithMultiFilters(currentPage.value, currentPerPage.value, searchQuery.value, column.key, newDirection);
};

// Handle item click - for document view, just select the item
const handleItemClick = (item: any) => {
  console.log('Document selected:', item);
  // Additional logic for document selection can be added here
};

// Handle create - for document view, this might be disabled or redirect to list view
const handleCreate = () => {
  console.log('Create action triggered - redirect to list view or show message');
  // Could redirect to /list/[resource] for actual creation
  navigateTo(`/list/${resourceName.value}`);
};

// Handle export
const handleExport = () => {
  console.log('Export functionality not implemented');
  showErrorToast('Export functionality not implemented yet');
};

// Handle import
const handleImport = () => {
  console.log('Import functionality not implemented');
  showErrorToast('Import functionality not implemented yet');
};

// Handle clear sort
const handleClearSort = async () => {
  await updateSort('');
  fetchDataWithMultiFilters(1, currentPerPage.value, searchQuery.value, '', 'asc');
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
  showErrorToast(errorMessage);
};

// Handle update error events
const handleUpdateError = (errorValue: string | null) => {
  error.value = errorValue;
};

// Fetch data on component mount and add keyboard shortcuts
onMounted(() => {
  initializeFromURL();
  fetchDataWithMultiFilters(currentPage.value, currentPerPage.value, searchQuery.value, sortField.value, sortDirection.value);

  // Add keyboard shortcuts
  document.addEventListener('keydown', handleGlobalKeydown);
});

// Set page title dynamically
useHead({
  title: `${resourceTitle.value} Documents - Dashboard`
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
    <ResourceHeader :resource-name="resourceName" :title="`${resourceTitle} Documents`" :loading="loading"
      :total-results="pagination.total" :has-filters="hasActiveFilters" :from="pagination.from" :to="pagination.to"
      @create="handleCreate" @export="handleExport" @import="handleImport">
      
      <!-- Filter Component in Header -->
      <template #filter>
        <ResourceFilter 
          :resource="resourceName"
          :loading="loading || isSearching || isSorting || isFiltering"
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
          @search="handleSearch" @clear="handleSearchClear">
          <template #search-info="{ query }">
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
        <span v-if="isFiltering" class="badge bg-info">
          <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
          Filtering...
        </span>
      </template>
    </ResourceHeader>

    <!-- Active Sorting Display Component -->
    <div v-if="!error || !error.includes('not found')" class="mb-4">
      <ResourceSorting 
        :search-query="searchQuery" 
        :sort-field="sortField" 
        :sort-direction="sortDirection"
        :filter-count="filterCount"
        :loading="loading || isSearching || isSorting || isFiltering" 
        @clear-search="handleSearchClear" 
        @clear-sort="handleClearSort"
        @clear-filters-only="handleClearFiltersOnly"
        @clear-all="handleClearAllFilters" 
      />
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
    </div>

    <!-- Enhanced Loading State -->
    <div v-if="loading && !isSearching && !isSorting" class="d-flex justify-content-center align-items-center py-5">
      <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted">Loading {{ resourceTitle.toLowerCase() }}...</p>
      </div>
    </div>

    <!-- Enhanced Error State -->
    <div v-else-if="error && !error.includes('not found')" class="alert alert-danger d-flex align-items-start"
      role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
      <div class="flex-grow-1">
        <h6 class="alert-heading mb-2">Error Loading Data</h6>
        <p class="mb-2">{{ error }}</p>
        <button type="button" class="btn btn-outline-danger btn-sm"
          @click="() => fetchDataWithMultiFilters(currentPage, currentPerPage, searchQuery, sortField, sortDirection)"
          :disabled="loading">
          <i class="bi bi-arrow-clockwise me-1"></i>
          Try Again
        </button>
      </div>
    </div>

    <!-- Master-Detail Document View -->
    <ResourceMasterDetailDoc ref="masterDetailRef" v-else-if="!loading || items.length > 0" :resource="resourceName"
      :items="items" :loading="loading" :error="error" :columns="processedColumns" :pagination="pagination"
      :show-pagination="false" :resource-title="resourceTitle" :search-query="searchQuery"
      :has-search-results="hasSearchResults" :has-no-search-results="hasNoSearchResults" :sort-field="sortField"
      :sort-direction="sortDirection" @itemClick="handleItemClick" @create="handleCreate"
      @update:error="handleUpdateError" @sort="handleSort">
      
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
                Go to List View
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
                Go to List View
              </button>
            </div>
          </div>

          <div v-else class="mb-4">
            <i class="bi bi-file-earmark-text display-1 text-muted mb-3"></i>
            <h4 class="text-muted">No {{ resourceTitle }} Found</h4>
            <p class="text-muted mb-4">
              Select a {{ resourceTitle.toLowerCase() }} from the list to view its document
            </p>
            <button type="button" class="btn btn-primary btn-lg" @click="handleCreate">
              <i class="bi bi-list me-2"></i>
              Go to List View
            </button>
          </div>
        </div>
      </template>
    </ResourceMasterDetailDoc>

    <!-- Pagination Component -->
    <ResourcePagination v-if="!loading && items.length > 0" :current-page="pagination.currentPage"
      :total-pages="pagination.totalPages" :per-page="pagination.perPage" :total="pagination.total"
      :from="pagination.from" :to="pagination.to" :has-next-page="pagination.hasNextPage"
      :has-prev-page="pagination.hasPrevPage" :loading="loading || isSearching || isSorting"
      :per-page-options="[10, 20, 50, 100]" @page-change="handlePageChange" @per-page-change="handlePerPageChange" />

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
