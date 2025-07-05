/**
 * CRUD API Service for Thanzil project
 * Extends base API service with generic CRUD methods
 */
import { useApiService } from './api'
import type { ApiResponse, PaginatedResponse, BaseEntity } from '~/types'

/**
 * Pagination query parameters
 */
export interface PaginationParams {
  /** Current page number */
  page?: number
  /** Number of items per page */
  perPage?: number
  /** Sort field */
  sortBy?: string
  /** Sort direction */
  sortDirection?: 'asc' | 'desc'
  /** Search query */
  search?: string
  /** Any additional filter parameters */
  [key: string]: any
}

/**
 * Schema response from API
 */
export interface SchemaResponse {
  /** Form fields with their properties (new format) */
  fields?: Record<string, SchemaField>
  /** Form fields with their properties (legacy format) */
  properties?: Record<string, SchemaField>
  /** Form layout information */
  layout?: SchemaLayout
  /** Any validation rules */
  rules?: Record<string, any>
}

/**
 * Schema field definition
 */
export interface SchemaField {
  /** Field type (text, number, select, etc.) */
  type: string
  /** Field label */
  label: string
  /** Field placeholder */
  placeholder?: string
  /** Whether the field is required */
  required?: boolean
  /** Default value */
  default?: any
  /** Available options for select fields */
  options?: Array<{ label: string; value: any }>
  /** Field validation rules */
  rules?: string[]
  /** Additional attributes */
  attributes?: Record<string, any>
  /** Maximum length for text inputs */
  maxLength?: number
  /** Minimum length for text inputs */
  minLength?: number
  /** Pattern for input validation */
  pattern?: string
  /** Whether field should be unique */
  unique?: boolean
}

/**
 * Schema layout information
 */
export interface SchemaLayout {
  /** Order of fields */
  order?: string[]
  /** Field groups */
  groups?: Record<string, string[]>
  /** Field widths */
  widths?: Record<string, number>
  /** Field dependencies */
  dependencies?: Record<string, { field: string; values: any[] }>
}

/**
 * CRUD API service that extends the base API service
 * Provides generic methods for common API operations
 */
export const useApiCrud = () => {
  // Get base API service
  const api = useApiService()
  /**
   * Fetch a list of resources with optional pagination
   * @param resource - The API resource/endpoint
   * @param params - Optional pagination and filtering parameters
   * @param v - Optional API version override
   * @returns Paginated response with data items and metadata
   */  const apiFetch = async <T = any>(
    resource: string,
    params?: PaginationParams,
    v?: string
  ): Promise<ApiResponse<PaginatedResponse<T>>> => {
    const response = await api.request<StandardizedApiResponse<T> | LaravelPaginationResponse<T>>(resource, {
      method: 'GET',
      version: v,
      params
    })

    // Transform API response if successful
    if (response.data && !response.error) {
      try {
        return {
          ...response,
          data: transformApiResponse(response.data)
        }
      } catch (transformError: any) {
        // Handle transformation errors (e.g., API errors in new format)
        return {
          data: null,
          error: transformError,
          loading: response.loading
        }
      }
    }

    // Return error response with null data
    return {
      data: null,
      error: response.error,
      loading: response.loading
    }
  }

  /**
   * Retrieve the schema or form definition for a resource
   * @param resource - The API resource/endpoint
   * @param v - Optional API version override
   * @returns Schema definition for the resource
   */
  const apiGetSchema = async (
    resource: string,
    v?: string
  ): Promise<ApiResponse<SchemaResponse>> => {
    return api.request<SchemaResponse>(`${resource}/schema`, {
      method: 'GET',
      version: v
    })
  }

  /**
   * Fetch a single resource by its ID
   * @param resource - The API resource/endpoint
   * @param id - Resource identifier
   * @param v - Optional API version override
   * @returns Single resource data
   */
  const apiGet = async <T = any>(
    resource: string,
    id: string | number,
    v?: string
  ): Promise<ApiResponse<T>> => {
    return api.request<T>(resource, {
      method: 'GET',
      version: v
    }, id)
  }

  /**
   * Create a new resource
   * @param resource - The API resource/endpoint
   * @param data - Resource data to create
   * @param v - Optional API version override
   * @returns Created resource data
   */
  const apiCreate = async <T = any, D = any>(
    resource: string,
    data: D,
    v?: string
  ): Promise<ApiResponse<T>> => {
    return api.request<T>(resource, {
      method: 'POST',
      body: data,
      version: v
    })
  }

  /**
   * Update an existing resource
   * @param resource - The API resource/endpoint
   * @param id - Resource identifier
   * @param data - Resource data to update
   * @param v - Optional API version override
   * @returns Updated resource data
   */
  const apiUpdate = async <T = any, D = any>(
    resource: string,
    id: string | number,
    data: D,
    v?: string
  ): Promise<ApiResponse<T>> => {
    return api.request<T>(resource, {
      method: 'PUT',
      body: data,
      version: v
    }, id)
  }

  /**
   * Delete a resource
   * @param resource - The API resource/endpoint
   * @param id - Resource identifier
   * @param v - Optional API version override
   * @returns API response after deletion
   */
  const apiDelete = async <T = any>(
    resource: string,
    id: string | number,
    v?: string
  ): Promise<ApiResponse<T>> => {
    return api.request<T>(resource, {
      method: 'DELETE',
      version: v
    }, id)
  }

  /**
   * Fetch available filters for a resource
   * @param resource - The API resource/endpoint
   * @param v - Optional API version override
   * @returns Available filter configurations
   */
  const apiGetFilters = async (
    resource: string,
    v?: string
  ): Promise<ApiResponse<import('~/types').FiltersResponse>> => {
    return api.request<import('~/types').FiltersResponse>(`${resource}/filters`, {
      method: 'GET',
      version: v
    })
  }

  /**
   * Example of using a form schema to dynamically create a form
   * @param resource - The API resource/endpoint
   * @param id - Optional resource ID for edit forms
   * @param v - Optional API version override
   * @returns Form schema and associated data
   */
  const getFormData = async <T = any>(
    resource: string,
    id?: string | number,
    v?: string
  ): Promise<{
    schema: ApiResponse<SchemaResponse>;
    data?: ApiResponse<T>;
  }> => {
    // Get the schema first
    const schema = await apiGetSchema(resource, v)
    
    // If an ID is provided, also fetch the data for editing
    let data: ApiResponse<T> | undefined
    
    if (id !== undefined) {
      data = await apiGet<T>(resource, id, v)
    }
    
    return {
      schema,
      data
    }
  }

  return {
    apiFetch,
    apiGetSchema,
    apiGet,
    apiCreate,
    apiUpdate,
    apiDelete,
    apiGetFilters,
    getFormData
  }
}

/**
 * Laravel pagination response structure (legacy format)
 */
interface LaravelPaginationResponse<T = any> {
  data: T[]
  current_page: number
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  links: Array<{
    url: string | null
    label: string
    active: boolean
  }>
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

/**
 * New standardized API response structure
 */
interface StandardizedApiResponse<T = any> {
  success: boolean
  data?: T[]
  message?: string
  meta?: any
  pagination?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
    has_more_pages: boolean
    path: string
    first_page_url: string
    last_page_url: string
    next_page_url: string | null
    prev_page_url: string | null
  }
  error?: {
    code: string
    message: string
    details?: any
    validation_errors?: any
  }
}

/**
 * Transform API response to frontend format
 * Handles both new standardized and legacy Laravel pagination formats
 */
function transformApiResponse<T>(apiResponse: StandardizedApiResponse<T> | LaravelPaginationResponse<T>): PaginatedResponse<T> {
  // Check if this is the new standardized format
  if ('success' in apiResponse) {
    const standardized = apiResponse as StandardizedApiResponse<T>
    
    if (!standardized.success) {
      throw new Error(standardized.error?.message || 'API request failed')
    }

    // Handle paginated response
    if (standardized.pagination) {
      return {
        data: standardized.data || [],
        meta: {
          currentPage: standardized.pagination.current_page,
          totalPages: standardized.pagination.last_page,
          perPage: standardized.pagination.per_page,
          total: standardized.pagination.total,
          hasNextPage: standardized.pagination.has_more_pages,
          hasPrevPage: standardized.pagination.current_page > 1,
          nextPage: standardized.pagination.next_page_url ? standardized.pagination.current_page + 1 : null,
          prevPage: standardized.pagination.prev_page_url ? standardized.pagination.current_page - 1 : null,
          from: standardized.pagination.from || 0,
          to: standardized.pagination.to || 0
        }
      }
    }
    
    // Handle simple data response
    return {
      data: standardized.data || [],
      meta: {
        currentPage: 1,
        totalPages: 1,
        perPage: (standardized.data || []).length,
        total: (standardized.data || []).length,
        hasNextPage: false,
        hasPrevPage: false,
        nextPage: null,
        prevPage: null,
        from: (standardized.data || []).length > 0 ? 1 : 0,
        to: (standardized.data || []).length
      }
    }
  }
  
  // Legacy Laravel pagination format
  const laravelResponse = apiResponse as LaravelPaginationResponse<T>
  return {
    data: laravelResponse.data,
    meta: {
      currentPage: laravelResponse.current_page,
      totalPages: laravelResponse.last_page,
      perPage: laravelResponse.per_page,
      total: laravelResponse.total,
      hasNextPage: !!laravelResponse.next_page_url,
      hasPrevPage: !!laravelResponse.prev_page_url,
      nextPage: laravelResponse.next_page_url ? laravelResponse.current_page + 1 : null,
      prevPage: laravelResponse.prev_page_url ? laravelResponse.current_page - 1 : null,
      from: laravelResponse.from || 0,
      to: laravelResponse.to || 0
    }
  }
}
