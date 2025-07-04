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
 * Format currency value to INR format
 * @param value - The numeric value to format
 * @returns Formatted currency string or '₹0.00' if invalid
 */
export function formatCurrency(value: any): string {
  if (value === null || value === undefined || value === '' || value === 0) return '₹0.00';
  const numValue = parseFloat(value);
  return !isNaN(numValue) ?
    new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR'
    }).format(numValue) : '₹0.00';
}

/**
 * Format date value to YYYY-MM-DD format
 * @param value - The date string to format
 * @returns Formatted date string or '-' if invalid
 */
export function formatDate(value: string): string {
  if (!value) return '-';
  try {
    // Handle ISO date strings like 2025-06-23T00:00:00.000000Z
    const date = new Date(value);
    if (isNaN(date.getTime())) return '-';
    // Return YYYY-MM-DD format
    return date.toISOString().split('T')[0];
  } catch {
    return '-';
  }
}

/**
 * Create formatter function based on type
 */
export function createFormatter(formatterType: string | undefined, fieldKey: string, isClickable?: boolean): ((value: any, item?: any) => string) | undefined {
  // Handle clickable fields first, regardless of formatter type
  if (isClickable) {
    return (value: string, item: any) => 
      value ? `<button type="button" class="btn btn-link text-decoration-none p-0 text-primary fw-medium" data-item-id="${item.id}" data-field-key="${fieldKey}">${value}</button>` : '-';
  }
  
  if (!formatterType) return undefined;
  
  switch (formatterType) {
    case 'date':
      return (value: string) => {
        if (!value) return '-';
        try {
          // Handle ISO date strings like 2025-06-23T00:00:00.000000Z
          const date = new Date(value);
          if (isNaN(date.getTime())) return '-';
          // Return YYYY-MM-DD format
          return date.toISOString().split('T')[0];
        } catch {
          return '-';
        }
      };
    case 'datetime':
      return (value: string) => value ? new Date(value).toLocaleString() : '-';
    case 'currency':
      return (value: any) => {
        if (value === null || value === undefined || value === '' || value === 0) return '₹0.00';
        const numValue = parseFloat(value);
        return !isNaN(numValue) ?
          new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR'
          }).format(numValue) : '₹0.00';
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

  // Function to detect field type and create appropriate formatter
  const detectFieldFormatter = (key: string, value: any): ((value: any, item?: any) => string) | undefined => {
    const lowerKey = key.toLowerCase();
    
    // Date fields - detect ISO date strings or common date field names
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(value)) {
      return createFormatter('date', key);
    }
    
    // Common date field names (more comprehensive)
    const dateFields = ['date', 'created_at', 'updated_at', 'deleted_at', 'published_at', 'due_date', 'start_date', 'end_date', 'birth_date', 'expire_date', 'estimate_date', 'invoice_date', 'payment_date', 'delivery_date'];
    if (dateFields.some(field => lowerKey.includes(field))) {
      return createFormatter('date', key);
    }
    
    // Currency/amount fields - detect by field name (including null/empty values)
    const currencyFields = ['amount', 'total', 'price', 'cost', 'value', 'salary', 'fee', 'charge', 'total_amount', 'subtotal', 'grand_total', 'balance', 'payment', 'revenue', 'profit', 'loss'];
    if (currencyFields.some(field => lowerKey.includes(field))) {
      return createFormatter('currency', key);
    }
    
    // Number fields
    if (typeof value === 'number' && !Number.isInteger(value)) {
      return createFormatter('number', key);
    }
    
    // Boolean fields
    if (typeof value === 'boolean') {
      return createFormatter('boolean', key);
    }
    
    return undefined;
  };

  // Add other common fields
  const commonFields = ['email', 'username', 'title', 'status', 'phone', 'whatsapp', 'created_at', 'updated_at'];
  
  Object.keys(firstItem).forEach(key => {
    // Skip id and name as they're already added
    if (key === 'id' || key === 'name') return;
    
    const fieldValue = firstItem[key];
    
    // Add common fields first
    if (commonFields.includes(key)) {
      columns.push({
        key,
        label: key.toUpperCase().replace('_', ' '),
        sortable: !['created_at', 'updated_at'].includes(key),
        formatter: ['created_at', 'updated_at'].includes(key) 
          ? createFormatter('date', key)
          : detectFieldFormatter(key, fieldValue)
      });
    }
  });

  // Add any remaining fields (except internal/system fields)
  Object.keys(firstItem).forEach(key => {
    if (!columns.find(col => col.key === key) && 
        !['password', 'token', 'api_token', 'remember_token', 'email_verified_at'].includes(key)) {
      
      const fieldValue = firstItem[key];
      const formatter = detectFieldFormatter(key, fieldValue);
      
      columns.push({
        key,
        label: key.toUpperCase().replace('_', ' '),
        sortable: true,
        formatter
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
      const columns = Object.entries(response).map(([key, config]) => {
        let formatter = createFormatter(config.formatter, key, config.clickable);
        
        // If no formatter specified by backend, try to auto-detect based on field name
        if (!formatter && !config.clickable) {
          const lowerKey = key.toLowerCase();
          
          // Auto-detect date fields
          const dateFields = ['date', 'created_at', 'updated_at', 'deleted_at', 'published_at', 'due_date', 'start_date', 'end_date', 'birth_date', 'expire_date', 'estimate_date', 'invoice_date', 'payment_date', 'delivery_date'];
          if (dateFields.some(field => lowerKey.includes(field))) {
            formatter = createFormatter('date', key);
          }
          
          // Auto-detect currency fields
          const currencyFields = ['amount', 'total', 'price', 'cost', 'value', 'salary', 'fee', 'charge', 'total_amount', 'subtotal', 'grand_total', 'balance', 'payment', 'revenue', 'profit', 'loss'];
          if (currencyFields.some(field => lowerKey.includes(field))) {
            formatter = createFormatter('currency', key);
          }
        }
        
        return {
          key,
          label: config.label?.toUpperCase() || key.toUpperCase().replace('_', ' '),
          sortable: config.sortable ?? true,
          formatter,
          cellClass: config.clickable ? 'text-primary fw-medium' : undefined
        };
      });
      
      console.log(`Processed backend columns for ${resourceName}:`, columns);
      return columns;
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
    console.log(`Using backend columns for ${resourceName}:`, backendColumns);
    return backendColumns;
  }
  
  // Fallback to auto-generation if data is available
  if (fallbackData && fallbackData.length > 0) {
    console.log(`Auto-generating columns for ${resourceName} from data:`, fallbackData[0]);
    const autoColumns = generateColumnsFromData(fallbackData);
    console.log(`Generated columns:`, autoColumns);
    return autoColumns;
  }
  
  // Final fallback - basic columns
  console.log(`Using fallback columns for ${resourceName}`);
  return [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'NAME', sortable: true }
  ];
}
