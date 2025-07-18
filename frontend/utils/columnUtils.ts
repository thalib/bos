/**
 * Column Management Utilities
 * Utilities for generating and managing table columns for resource listings
 */

import type { Column } from '~/types';

// Re-export Column type for compatibility
export type { Column };

/**
 * Format currency value with configurable currency
 * @param value - The numeric value to format
 * @param currency - The currency code (default: 'INR')
 * @returns Formatted currency string or '₹0.00' if invalid
 */
export function formatCurrency(value: any, currency: string = 'INR'): string {
  if (value === null || value === undefined || value === '' || value === 0) return '₹0.00';
  const numValue = parseFloat(value);
  return !isNaN(numValue) ?
    new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: currency
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
    const date = new Date(value);
    if (isNaN(date.getTime())) return '-';
    return date.toISOString().split('T')[0];
  } catch {
    return '-';
  }
}

/**
 * Create formatter function based on type from API
 * @param formatterType - The type of formatter to create from API
 * @param fieldKey - The field key (for context)
 * @param isClickable - Whether the field is clickable
 * @returns Formatter function or undefined
 */
export function createFormatter(
  formatterType: string | undefined, 
  fieldKey: string, 
  isClickable?: boolean
): ((value: any, item?: any) => string) | undefined {
  // For clickable fields, let the component handle button rendering
  if (isClickable) {
    return undefined;
  }
  
  if (!formatterType) return undefined;
  
  switch (formatterType) {
    case 'date':
      return (value: string) => formatDate(value);
      
    case 'datetime':
      return (value: string) => value ? new Date(value).toLocaleString() : '-';
      
    case 'currency':
      return (value: any) => formatCurrency(value);
      
    case 'boolean':
      return (value: boolean) => value ? 'Yes' : 'No';
      
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
 * Backend columns response types
 */
interface ColumnConfig {
  label: string;
  sortable?: boolean;
  clickable?: boolean;
  search?: boolean;
  formatter?: string;
  type?: string;
  width?: string;
  align?: string;
  hidden?: boolean;
}

type ColumnsResponse = Record<string, ColumnConfig>;

interface StandardizedColumnsResponse {
  success: boolean;
  data: any[];
  columns?: Array<{
    field: string;
    label: string;
    sortable?: boolean;
    clickable?: boolean;
    search?: boolean;
    type?: string;
    format?: string;
    width?: string;
    align?: string;
    hidden?: boolean;
  }>;
  error?: {
    code: string;
    message: string;
  };
}

/**
 * Fetch columns configuration from backend
 * @param resourceName - Name of the resource
 * @returns Promise with Column array or throws error
 */
export async function fetchColumnsConfig(resourceName: string): Promise<Column[]> {
  try {
    const { useApiService } = await import('~/services/api');
    const apiService = useApiService();
    
    // Fetch columns from main resource endpoint
    const apiResponse = await apiService.request<StandardizedColumnsResponse>(`${resourceName}`, {
      method: 'GET',
      params: { per_page: 1 }
    });

    if (apiResponse.error) {
      throw new Error(apiResponse.error.message || 'Failed to fetch column configuration');
    }

    const data = apiResponse.data;
    if (!data || typeof data !== 'object') {
      throw new Error('Invalid API response format');
    }

    // Handle standardized API response format
    if ('success' in data) {
      const standardizedData = data as StandardizedColumnsResponse;
      
      if (!standardizedData.success) {
        throw new Error(standardizedData.error?.message || 'Failed to fetch column configuration');
      }
      
      if (!standardizedData.columns || !Array.isArray(standardizedData.columns)) {
        throw new Error('No columns configuration found in API response');
      }

      return standardizedData.columns.map(col => ({
        key: col.field,
        label: col.label || col.field.toUpperCase().replace('_', ' '),
        sortable: col.sortable ?? false,
        clickable: col.clickable ?? false,
        search: col.search ?? false,
        type: col.type || 'string',
        width: col.width,
        align: col.align || 'left',
        hidden: col.hidden ?? false,
        formatter: createFormatter(col.format, col.field, col.clickable),
        cellClass: col.clickable ? 'text-primary fw-medium' : undefined
      }));
    }

    throw new Error('Invalid API response format - missing success field');

  } catch (error) {
    console.error('Failed to fetch column configuration:', error);
    throw error;
  }
}

/**
 * Get columns for a resource from backend API
 * @param resourceName - Name of the resource
 * @returns Promise with Column array or throws error
 */
export async function getResourceColumns(resourceName: string): Promise<Column[]> {
  // Only fetch from backend - no fallback logic in frontend
  return await fetchColumnsConfig(resourceName);
}
