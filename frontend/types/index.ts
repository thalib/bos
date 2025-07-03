/**
 * Common TypeScript types and interfaces for the Thanzil project
 */

// Re-export document types
export * from './document';

/**
 * Column Interface
 * Represents a table column configuration for resource listings
 */
export interface Column {
  /** The data key to display */
  key: string;
  /** Optional display label for the column */
  label?: string;
  /** Whether the column is sortable */
  sortable?: boolean;
  /** Optional formatter function for the column value */
  formatter?: (value: any, item?: any) => string;
  /** Optional CSS class for the cell */
  cellClass?: string;
}

/**
 * API Response Structure
 * Represents the standardized structure of API responses
 */
export interface ApiResponse<T = any> {
  /** The actual data returned from the API */
  data: T | null;
  /** Any error that occurred during the API call */
  error: Error | null;
  /** Loading state indicator */
  loading: boolean;
}

/**
 * Error Interface
 * Represents an API error with additional context
 */
export interface ApiError extends Error {
  /** HTTP status code of the error */
  statusCode?: number;
  /** Validation errors returned from backend */
  validationErrors?: Record<string, string[]>;
}

/**
 * Pagination Metadata
 * Contains information about the current pagination state
 */
export interface PaginationMeta {
  /** Current page number */
  currentPage: number;
  /** Total number of pages */
  totalPages: number;
  /** Number of items per page */
  perPage: number;
  /** Total number of items across all pages */
  total: number;
  /** Whether there is a next page */
  hasNextPage: boolean;
  /** Whether there is a previous page */
  hasPrevPage: boolean;
  /** Next page number if it exists */
  nextPage: number | null;
  /** Previous page number if it exists */
  prevPage: number | null;
  /** Starting item number for current page */
  from: number;
  /** Ending item number for current page */
  to: number;
}

/**
 * Paginated Response
 * Represents a paginated API response
 */
export interface PaginatedResponse<T = any> {
  /** Array of data items */
  data: T[];
  /** Pagination metadata */
  meta: PaginationMeta;
}

/**
 * Base Entity Interface
 * Common properties that all entities should have
 */
export interface BaseEntity {
  /** Unique identifier */
  id: number | string;
  /** Creation timestamp */
  createdAt?: string | Date;
  /** Last update timestamp */
  updatedAt?: string | Date;
}

/**
 * User Interface
 * Represents a user in the system
 */
export interface User extends BaseEntity {
  /** User's email address */
  email: string;
  /** User's full name */
  name: string;
  /** User's role in the system */
  role?: string;
  /** Whether the user's email is verified */
  emailVerified?: boolean;
}

/**
 * Company Interface
 * Represents a company in the system
 */
export interface Company extends BaseEntity {
  /** Company name */
  name: string;
  /** Company registration number */
  registrationNumber?: string;
  /** Company address */
  address?: string;
  /** Company contact email */
  email?: string;
  /** Company phone number */
  phone?: string;
  /** Company tax ID */
  taxId?: string;
}

/**
 * Bank Interface
 * Represents a bank in the system
 */
export interface Bank extends BaseEntity {
  /** Bank name */
  name: string;
  /** Bank code */
  code?: string;
  /** Bank account number */
  accountNumber?: string;
  /** Bank routing number */
  routingNumber?: string;
  /** Bank address */
  address?: string;
  /** SWIFT/BIC code for international transfers */
  swiftCode?: string;
}

/**
 * Base Menu Item Interface
 * Common properties for all menu items
 */
export interface BaseMenuItem {
  /** Unique identifier for the menu item */
  id?: number;
  /** Type of menu item */
  type: 'item' | 'section' | 'divider';
  /** Display order for sorting */
  order: number;
}

/**
 * Menu Item Interface
 * Represents a navigation menu item
 */
export interface MenuItem extends BaseMenuItem {
  type: 'item';
  /** Display name of the menu item */
  name: string;
  /** Route path for navigation */
  path: string;
  /** Bootstrap icon class name */
  icon: string;
}

/**
 * Menu Section Interface
 * Represents a grouped section of menu items
 */
export interface MenuSection extends BaseMenuItem {
  type: 'section';
  /** Section title */
  title: string;
  /** Items within this section */
  items: readonly MenuSectionItem[];
}

/**
 * Menu Section Item Interface
 * Represents an item within a menu section
 */
export interface MenuSectionItem {
  /** Unique identifier for the menu item */
  readonly id: number;
  /** Display name of the menu item */
  readonly name: string;
  /** Route path for navigation */
  readonly path: string;
  /** Bootstrap icon class name */
  readonly icon: string;
}

/**
 * Menu Divider Interface
 * Represents a visual divider in the menu
 */
export interface MenuDivider extends BaseMenuItem {
  type: 'divider';
}

/**
 * Menu Structure Union Type
 * Represents any type of menu item
 */
export type MenuItemType = MenuItem | MenuSection | MenuDivider;

/**
 * Menu API Response
 * Represents the response structure from the menu API endpoint
 */
export interface MenuResponse {
  /** Array of menu items */
  data: MenuItemType[];
  /** Success message from the API */
  message: string;
}

/**
 * Page Configuration Interface
 * Represents the configuration for dynamic pages
 */
export interface PageConfig {
  /** Page slug/identifier */
  slug: string;
  /** Page title */
  title: string;
  /** Page description */
  description?: string;
  /** Component name to render */
  component: string;
  /** Page icon class */
  icon?: string;
  /** Middleware requirements */
  middleware?: string[];
}

/**
 * Dynamic Page Props Interface
 * Props passed to dynamic page components
 */
export interface DynamicPageProps {
  /** Current page configuration */
  config: PageConfig;
  /** Page slug */
  slug: string;
}
