# TableSorting Component Design Specification

⚠️ **DEPRECATED COMPONENT** ⚠️

This component is **deprecated** and should not be used in new development. Sorting functionality has been integrated directly into the `List` component for better performance and self-contained architecture.

**Migration Path:**
- Remove `TableSorting` component usage
- Use the self-contained `List` component which includes built-in sorting
- Sorting state is managed automatically by the List component with URL synchronization

**File Location:** ~~`frontend/app/components/Resource/TableSorting.vue`~~ (Remove this file)

## Why This Component Was Deprecated

### Issues with Original Design:
1. **Separation of Concerns**: Sorting logic was separated from the table display
2. **State Management**: Required complex prop passing and event handling
3. **Performance**: Additional components added unnecessary rendering overhead
4. **User Experience**: Sorting indicators were disconnected from table headers

### Better Architecture:
The `List` component now provides:
- **Integrated Sorting**: Sort indicators directly in table headers
- **Self-Contained State**: No props/events needed for sorting
- **URL Synchronization**: Sorting state automatically synced with browser URL
- **Better UX**: Click table headers to sort, visual feedback in place

## Migration Example

### ❌ Old Pattern (Deprecated)
```html
<!-- Don't use this -->
<TableSorting
  :sort="sortConfig"
  :filters="filters"
  :search="searchQuery"
  @sort-clear="handleSortClear"
/>
<List :data="data" :columns="columns" />
```

### ✅ New Pattern (Recommended)
```html
<!-- Use this instead -->
<List
  resource="products"
  :filters="currentFilters"
  :search="currentSearch"
  @sort-changed="handleSortUpdate"
/>
```

## Implementation Rules

**DO NOT implement this component.** Use the self-contained `List` component instead.

If you need custom sorting display for specific use cases:
1. Extend the `List` component with additional slots
2. Create a specialized table component for that specific domain
3. Always keep sorting logic integrated with table display

## Testing Migration

When migrating tests from TableSorting to List component:

```typescript
// Update test files
// FROM: frontend/tests/components/Resource/TableSorting.spec.ts
// TO: Add sorting tests to frontend/tests/components/Resource/List.spec.ts

describe('List Component Sorting', () => {
  it('should display sort indicators in table headers')
  it('should handle column sorting when header clicked')
  it('should update URL with sort parameters')
  it('should toggle sort direction on repeated clicks')
})
```

---

**For current sorting needs, see:** `design/app/components/Resource/List.md`
