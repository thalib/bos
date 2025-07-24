// TDD Test for List Component - Write tests first before implementation
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import List from '@/components/Resource/List.vue'

// Mock the API service
const mockApiService = {
  fetch: vi.fn(),
  buildUrl: vi.fn(),
  handleError: vi.fn()
}

// Mock the notify service
const mockNotifyService = {
  error: vi.fn(),
  warning: vi.fn(),
  success: vi.fn(),
  info: vi.fn()
}

// Mock the composables
vi.mock('@/utils/api', () => ({
  useApiService: () => mockApiService
}))

vi.mock('@/utils/notify', () => ({
  useNotifyService: () => mockNotifyService
}))

// Mock router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: { template: '<div>Home</div>' } },
    { path: '/list/:resource', component: { template: '<div>List</div>' } }
  ]
})

describe('List Component', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    
    // Clear router state completely
    router.replace({ query: {} })
    
    // Default successful API response
    mockApiService.fetch.mockResolvedValue({
      success: true,
      data: [
        { id: 1, name: 'Item 1', status: 'active' },
        { id: 2, name: 'Item 2', status: 'inactive' }
      ],
      pagination: {
        totalItems: 2,
        currentPage: 1,
        itemsPerPage: 15,
        totalPages: 1
      },
      columns: [
        { field: 'id', label: 'ID', sortable: true, type: 'number' },
        { field: 'name', label: 'Name', sortable: true, type: 'text', clickable: true },
        { field: 'status', label: 'Status', sortable: false, type: 'text' }
      ]
    })
  })

  describe('Component Rendering', () => {
    it('should render responsive table with Bootstrap 5.3 styling', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        expect(wrapper.find('.table-responsive').exists()).toBe(true)
        expect(wrapper.find('.table.table-hover.table-striped').exists()).toBe(true)
        expect(wrapper.find('.table-light.sticky-top').exists()).toBe(true)
      })
    })

    it('should render loading state with skeleton placeholders', async () => {
      mockApiService.fetch.mockImplementation(() => new Promise(() => {})) // Never resolves
      
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      // Wait for component to mount and start loading
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.table-loading').exists()).toBe(true)
      expect(wrapper.find('.placeholder-glow').exists()).toBe(true)
      expect(wrapper.find('.spinner-border').exists()).toBe(false) // Should be skeleton, not spinner
    })

    it('should render empty state when no data available', async () => {
      mockApiService.fetch.mockResolvedValue({
        success: true,
        data: [],
        pagination: { totalItems: 0, currentPage: 1, itemsPerPage: 15, totalPages: 0 },
        columns: []
      })

      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        expect(wrapper.find('.empty-state').exists()).toBe(true)
        expect(wrapper.find('.bi-inbox').exists()).toBe(true)
        expect(wrapper.text()).toContain('No items found')
      })
    })

    it('should render error state on API failure', async () => {
      mockApiService.fetch.mockRejectedValue(new Error('API Error'))

      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        expect(wrapper.find('.error-state').exists()).toBe(true)
        expect(wrapper.find('.bi-exclamation-triangle').exists()).toBe(true)
        expect(wrapper.text()).toContain('Failed to load data')
      })
    })
  })

  describe('Data Loading', () => {
    it('should fetch data on component mount', async () => {
      mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      expect(mockApiService.fetch).toHaveBeenCalledWith('products', {
        page: 1
      })
    })

    it('should refetch data when props change', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products', filters: {}, search: '', page: 1 },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        expect(mockApiService.fetch).toHaveBeenCalledTimes(1)
      })

      // Change props
      await wrapper.setProps({ 
        filters: { status: 'active' },
        search: 'test',
        page: 2
      })

      await vi.waitFor(() => {
        expect(mockApiService.fetch).toHaveBeenCalledTimes(2)
        expect(mockApiService.fetch).toHaveBeenLastCalledWith('products', {
          page: 2,
          status: 'active',
          search: 'test'
        })
      })
    })

    it('should handle loading state during data fetch', async () => {
      let resolveFetch: (value: any) => void
      mockApiService.fetch.mockImplementation(() => new Promise(resolve => {
        resolveFetch = resolve
      }))

      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      // Wait for component to mount and start loading
      await wrapper.vm.$nextTick()

      // Should show loading state
      expect(wrapper.find('.table-loading').exists()).toBe(true)
      
      // Resolve the fetch
      resolveFetch!({
        success: true,
        data: [{ id: 1, name: 'Item 1' }],
        pagination: { totalItems: 1, currentPage: 1, itemsPerPage: 15, totalPages: 1 },
        columns: [{ field: 'name', label: 'Name', sortable: true }]
      })

      await vi.waitFor(() => {
        expect(wrapper.find('.table-loading').exists()).toBe(false)
        expect(wrapper.find('.table-responsive').exists()).toBe(true)
      })
    })

    it('should display data in table format', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        // Check table headers
        const headers = wrapper.findAll('th')
        expect(headers[0].text()).toContain('ID')
        expect(headers[1].text()).toContain('Name')
        expect(headers[2].text()).toContain('Status')

        // Check table data
        const rows = wrapper.findAll('tbody tr')
        expect(rows).toHaveLength(2)
        expect(rows[0].text()).toContain('Item 1')
        expect(rows[1].text()).toContain('Item 2')
      })
    })
  })

  describe('Sorting', () => {
    it('should handle column sort when header clicked', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const sortableHeader = wrapper.find('th.sortable-header')
        expect(sortableHeader.exists()).toBe(true)
      })

      const sortableHeader = wrapper.find('th.sortable-header')
      await sortableHeader.trigger('click')

      expect(mockApiService.fetch).toHaveBeenCalledWith('products', {
        page: 1,
        sort: 'id',
        dir: 'asc'
      })
    })

    it('should toggle sort direction on repeated clicks', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const sortableHeader = wrapper.find('th.sortable-header')
        expect(sortableHeader.exists()).toBe(true)
      })

      const sortableHeader = wrapper.find('th.sortable-header')
      
      // First click - ascending
      await sortableHeader.trigger('click')
      expect(mockApiService.fetch).toHaveBeenLastCalledWith('products', {
        page: 1,
        sort: 'id',
        dir: 'asc'
      })

      // Second click - descending
      await sortableHeader.trigger('click')
      expect(mockApiService.fetch).toHaveBeenLastCalledWith('products', {
        page: 1,
        sort: 'id',
        dir: 'desc'
      })
    })

    it('should emit sort-changed event with correct payload', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const sortableHeader = wrapper.find('th.sortable-header')
        expect(sortableHeader.exists()).toBe(true)
      })

      const sortableHeader = wrapper.find('th.sortable-header')
      await sortableHeader.trigger('click')

      expect(wrapper.emitted('sort-changed')).toBeTruthy()
      expect(wrapper.emitted('sort-changed')![0]).toEqual([{
        column: 'id',
        direction: 'asc'
      }])
    })

    it('should display sort indicators correctly', async () => {
      // Set up router with sort query params
      await router.push({ query: { sort: 'name', dir: 'desc' } })
      
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const sortIcon = wrapper.find('.bi-sort-down')
        expect(sortIcon.exists()).toBe(true)
      })
    })
  })

  describe('Row Interactions', () => {
    it('should handle row click when clickable columns exist', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const clickableRow = wrapper.find('tbody tr.clickable-row')
        expect(clickableRow.exists()).toBe(true)
      })

      const clickableRow = wrapper.find('tbody tr.clickable-row')
      await clickableRow.trigger('click')

      expect(wrapper.emitted('item-selected')).toBeTruthy()
      expect(wrapper.emitted('item-selected')![0]).toEqual([{
        item: { id: 1, name: 'Item 1', status: 'active' },
        index: 0
      }])
    })

    it('should apply correct CSS classes for clickable rows', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const rows = wrapper.findAll('tbody tr')
        expect(rows[0].classes()).toContain('clickable-row')
      })
    })
  })

  describe('Error Handling', () => {
    it('should handle API errors gracefully with notify service', async () => {
      mockApiService.fetch.mockRejectedValue({
        message: 'Network error',
        code: 'NETWORK_ERROR'
      })

      mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        expect(mockNotifyService.error).toHaveBeenCalledWith(
          'Failed to load data. Please try again.',
          'Data Loading Error'
        )
      })
    })

    it('should provide retry functionality on error', async () => {
      mockApiService.fetch.mockRejectedValueOnce(new Error('API Error'))
        .mockResolvedValueOnce({
          success: true,
          data: [{ id: 1, name: 'Item 1' }],
          pagination: { totalItems: 1, currentPage: 1, itemsPerPage: 15, totalPages: 1 },
          columns: [{ field: 'name', label: 'Name', sortable: true }]
        })

      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        expect(wrapper.find('.error-state').exists()).toBe(true)
      })

      const retryButton = wrapper.find('.error-state button')
      await retryButton.trigger('click')

      await vi.waitFor(() => {
        expect(wrapper.find('.table-responsive').exists()).toBe(true)
        expect(wrapper.find('.error-state').exists()).toBe(false)
      })
    })
  })

  describe('Accessibility', () => {
    it('should have proper ARIA labels for table', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const table = wrapper.find('table')
        expect(table.attributes('role')).toBe('table')
        expect(table.attributes('aria-label')).toContain('products data table')
      })
    })

    it('should support keyboard navigation for sortable headers', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const sortableHeaders = wrapper.findAll('th.sortable-header')
        expect(sortableHeaders[0].attributes('tabindex')).toBe('0')
        expect(sortableHeaders[0].attributes('role')).toBe('columnheader')
      })
    })

    it('should announce sort changes to screen readers', async () => {
      const wrapper = mount(List, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await vi.waitFor(() => {
        const sortableHeader = wrapper.find('th.sortable-header')
        expect(sortableHeader.attributes('aria-sort')).toBe('none')
      })

      const sortableHeader = wrapper.find('th.sortable-header')
      await sortableHeader.trigger('click')

      // Wait for component to update after click and API call
      await vi.waitFor(() => {
        const sortableHeader = wrapper.find('th.sortable-header')
        expect(sortableHeader.attributes('aria-sort')).toBe('ascending')
      }, { timeout: 2000 })
    })
  })
})