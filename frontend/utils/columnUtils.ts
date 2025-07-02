/**
 * Column Management Utilities
 * Utilities for generating and managing table columns for resource listings
 */

// Column interface
interface Column {
  key: string;
  label?: string;
  sortable?: boolean;
  formatter?: (value: any, item?: any) => string;
  cellClass?: string;
}

// Export the Column type for use in other files
export type { Column }

/**
 * Create formatter function based on type
 */
export function createFormatter(formatterType: string | undefined, fieldKey: string, isClickable?: boolean) {
  // Handle clickable fields first, regardless of formatter type
  if (isClickable && fieldKey === 'name') {
    return (value: string, item: any) => 
      value ? `<button type="button" class="btn btn-link text-decoration-none p-0 text-primary fw-medium" data-user-id="${item.id}">${value}</button>` : '-';
  }
  
  if (!formatterType) return undefined;
  
  switch (formatterType) {
    case 'date':
      return (value: string) => value ? new Date(value).toLocaleDateString() : '-';
    case 'datetime':
      return (value: string) => value ? new Date(value).toLocaleString() : '-';
    case 'currency':
      return (value: any) => {
        const numValue = parseFloat(value);
        const config = useRuntimeConfig()
        return !isNaN(numValue) && numValue !== null ?
          new Intl.NumberFormat((config.public.currencyLocale as string) || 'en-IN', {
            style: 'currency',
            currency: (config.public.currencyCode as string) || 'INR'
          }).format(numValue) : '-';
      };
    case 'boolean':
      return (value: boolean) => value ? '✓' : '✗';
    case 'badge':
      return (value: string) => value ? `<span class="badge bg-secondary">${value}</span>` : '-';
    case 'number':
      return (value: any) => {
        const numValue = parseFloat(value);
        return !isNaN(numValue) && numValue !== null ? numValue.toLocaleString() : '0';
      };
    default:
      return undefined;
  }
}

/**
 * Generate columns dynamically from first item in response
 */
export function generateColumnsFromData(data: any[]): Column[] {
  if (!data || data.length === 0) {
    return [];
  }

  const firstItem = data[0];
  const columns: Column[] = [];

  // Always include ID if it exists
  if ('id' in firstItem) {
    columns.push({ key: 'id', label: 'ID', sortable: true });
  }

  // Add name with special formatter if it exists
  if ('name' in firstItem) {
    columns.push({
      key: 'name',
      label: 'NAME',
      sortable: true,
      formatter: (value: string, item: any) => 
        value ? `<button type="button" class="btn btn-link text-decoration-none p-0 text-primary fw-medium" data-user-id="${item.id}">${value}</button>` : '-',
      cellClass: 'text-primary fw-medium'
    });
  }

  // Add other common fields
  const commonFields = ['email', 'username', 'title', 'status', 'phone', 'whatsapp', 'created_at', 'updated_at'];
  
  Object.keys(firstItem).forEach(key => {
    // Skip id and name as they're already added
    if (key === 'id' || key === 'name') return;
    
    // Add common fields first
    if (commonFields.includes(key)) {
      columns.push({
        key,
        label: key.toUpperCase().replace('_', ' '),
        sortable: !['created_at', 'updated_at'].includes(key),
        formatter: ['created_at', 'updated_at'].includes(key) 
          ? (value: string) => value ? new Date(value).toLocaleDateString() : '-'
          : undefined
      });
    }
  });

  // Add any remaining fields (except internal/system fields)
  Object.keys(firstItem).forEach(key => {
    if (!columns.find(col => col.key === key) && 
        !['password', 'token', 'api_token', 'remember_token', 'email_verified_at'].includes(key)) {
      columns.push({
        key,
        label: key.toUpperCase().replace('_', ' '),
        sortable: true
      });
    }
  });

  return columns;
}

/**
 * Column configuration type
 */
interface ColumnConfig {
  label: string;
  sortable?: boolean;
  clickable?: boolean;
  search?: boolean;
  formatter?: string;
}

/**
 * Backend columns response types
 */
type ColumnsResponse = Record<string, ColumnConfig>;

interface StandardizedColumnsResponse {
  success: boolean;
  data: ColumnsResponse;
  error?: {
    code: string;
    message: string;
  };
}

/**
 * Fetch columns configuration from backend
 */
export async function fetchColumnsConfig(resourceName: string): Promise<Column[] | null> {
  try {
    // Import API service dynamically to avoid circular dependencies
    const { useApiService } = await import('~/services/api');
    const apiService = useApiService();
    
    // Use authenticated API service instead of $fetch
    const apiResponse = await apiService.request<ColumnsResponse | StandardizedColumnsResponse>(`${resourceName}/columns`);

    // Handle API response - check for errors first
    if (apiResponse.error) {
      throw new Error(apiResponse.error.message || 'Failed to fetch column configuration');
    }

    let response: ColumnsResponse | null = null;
    const data = apiResponse.data;

    // Handle new standardized response format
    if (data && typeof data === 'object' && 'success' in data) {
      const standardizedData = data as StandardizedColumnsResponse;
      if (!standardizedData.success) {
        throw new Error(standardizedData.error?.message || 'Failed to fetch column configuration');
      }
      response = standardizedData.data;
    } else {
      // Legacy format - direct columns response
      response = data as ColumnsResponse;
    }

    if (response && typeof response === 'object') {
      return Object.entries(response).map(([key, config]) => ({
        key,
        label: config.label?.toUpperCase() || key.toUpperCase().replace('_', ' '),
        sortable: config.sortable ?? true,
        formatter: createFormatter(config.formatter, key, config.clickable),
        cellClass: config.clickable ? 'text-primary fw-medium' : undefined
      }));
    }
  } catch (error) {
    // Fallback to auto-generation if columns config fails
    console.warn('Failed to fetch column configuration:', error);
  }
  
  return null;
}

/**
 * Get columns for a resource (backend config or auto-generated)
 */
export async function getResourceColumns(resourceName: string, fallbackData?: any[]): Promise<Column[]> {
  // Try to fetch from backend first
  const backendColumns = await fetchColumnsConfig(resourceName);
  
  if (backendColumns && backendColumns.length > 0) {
    return backendColumns;
  }
  
  // Fallback to auto-generation if data is available
  if (fallbackData && fallbackData.length > 0) {
    return generateColumnsFromData(fallbackData);
  }
  
  // Final fallback - basic columns
  return [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'NAME', sortable: true }
  ];
}
