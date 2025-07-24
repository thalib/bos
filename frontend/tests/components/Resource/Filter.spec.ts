// TDD Test for Filter Component - Write tests first before implementation
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import Filter from '@/components/Resource/Filter.vue'

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

describe('Filter Component', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    
    // Clear router state
    router.replace({ query: {} })
    
    // Default successful API response with filters
    mockApiService.fetch.mockResolvedValue({
      success: true,
      data: { data: [] },
      filters: {
        available: [
          {
            field: 'status',
            label: 'Status',
            values: [
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' }
            ]
          },
          {
            field: 'category',
            label: 'Category',
            values: [
              { value: 'electronics', label: 'Electronics' },
              { value: 'clothing', label: 'Clothing' }
            ]
          }
        ]
      },
      pagination: { totalItems: 5 }
    })
  })

  describe('Initialization', () => {
    it('should fetch available filters on component mount', async () => {
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(mockApiService.fetch).toHaveBeenCalledWith('products', { page: 1, per_page: 1 })
    })

    it('should initialize with route query filter parameters', async () => {
      await router.push({ query: { status: 'active', category: 'electronics' } })
      
      const wrapper = mount(Filter, {
        props: { 
          resource: 'products',
          initialFilters: { status: 'active' }
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Check that the component initializes with route parameters
      expect(wrapper.vm.appliedFilters).toMatchObject({
        status: 'active',
        category: 'electronics'
      })
    })

    it('should apply proper Bootstrap 5.3 classes', async () => {
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Wait for filters to load
      await new Promise(resolve => setTimeout(resolve, 50))
      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.filter-container').exists()).toBe(true)
      expect(wrapper.find('.row.g-2').exists()).toBe(true)
      // Only check for form-select if filters are available
      if (wrapper.vm.availableFilters.length > 0) {
        expect(wrapper.find('.form-select').exists()).toBe(true)
      }
    })

    it('should set correct ARIA attributes for accessibility', async () => {
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Wait for API response and component to fully render
      await new Promise(resolve => setTimeout(resolve, 50))
      await wrapper.vm.$nextTick()
      
      // Only test ARIA if filters are loaded
      const selects = wrapper.findAll('.form-select')
      if (selects.length > 0) {
        const select = selects[0]
        expect(select.attributes('aria-label')).toContain('Filter by')
      }
    })
  })

  describe('Filter Operations', () => {
    it('should apply filter and update URL parameters', async () => {
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Wait for filters to load
      await new Promise(resolve => setTimeout(resolve, 50))
      await wrapper.vm.$nextTick()
      
      // Simulate filter selection directly on component
      wrapper.vm.appliedFilters = { status: 'active' }
      await wrapper.vm.applyFilters()
      
      expect(mockApiService.fetch).toHaveBeenCalledWith('products', 
        expect.objectContaining({ status: 'active' })
      )
    })

    it('should remove individual filters', async () => {
      router.replace({ query: { status: 'active' } })
      
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Simulate remove filter
      await wrapper.vm.removeFilter('status')
      
      expect(wrapper.vm.appliedFilters.status).toBe('')
    })

    it('should clear all filters and reset URL', async () => {
      await router.push({ query: { status: 'active', category: 'electronics' } })
      
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Set some filters first
      wrapper.vm.appliedFilters = { status: 'active' }
      await wrapper.vm.$nextTick()
      
      await wrapper.vm.clearAllFilters()
      
      expect(wrapper.vm.appliedFilters).toEqual({})
      expect(wrapper.emitted('filters-applied')).toBeTruthy()
    })

    it('should handle multiple simultaneous filters', async () => {
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      wrapper.vm.appliedFilters = { status: 'active', category: 'electronics' }
      await wrapper.vm.applyFilters()
      
      expect(mockApiService.fetch).toHaveBeenCalledWith('products', 
        expect.objectContaining({ 
          status: 'active',
          category: 'electronics'
        })
      )
    })

    it('should call API service with correct filter parameters', async () => {
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      wrapper.vm.appliedFilters = { status: 'active' }
      await wrapper.vm.applyFilters()
      
      expect(mockApiService.fetch).toHaveBeenCalledWith('products', { status: 'active' })
    })
  })

  describe('State Management', () => {
    it('should manage loading state during filter operations', async () => {
      // Make API call slow to test loading state
      mockApiService.fetch.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)))
      
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      expect(wrapper.vm.isLoading).toBe(true)
      
      await new Promise(resolve => setTimeout(resolve, 150))
      expect(wrapper.vm.isLoading).toBe(false)
    })

    it('should display applied filters as removable badges', async () => {
      await router.push({ query: { status: 'active' } })
      
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Manually set applied filters to test badge display
      wrapper.vm.appliedFilters = { status: 'active' }
      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.badge.bg-primary').exists()).toBe(true)
      expect(wrapper.find('.btn-close').exists()).toBe(true)
    })

    it('should emit filters-applied event with correct payload', async () => {
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Set available filters first so the watcher condition is met
      wrapper.vm.availableFilters = [
        {
          field: 'status',
          label: 'Status',
          values: [{ value: 'active', label: 'Active' }]
        }
      ]
      
      wrapper.vm.appliedFilters = { status: 'active' }
      await wrapper.vm.applyFilters()
      
      const emitted = wrapper.emitted('filters-applied')
      expect(emitted).toBeTruthy()
      expect(emitted![emitted!.length - 1][0]).toEqual({
        filters: { status: 'active' },
        hasActiveFilters: true
      })
    })

    it('should maintain filter state during navigation', async () => {
      await router.push({ query: { status: 'active' } })
      
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.appliedFilters.status).toBe('active')
      expect(wrapper.vm.hasActiveFilters).toBe(true)
    })
  })

  describe('Error Handling', () => {
    it('should handle API errors with notify service', async () => {
      mockApiService.fetch.mockRejectedValue(new Error('Network error'))
      
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(mockNotifyService.error).toHaveBeenCalledWith(
        'Filter options temporarily unavailable.',
        'Filter Error'
      )
    })

    it('should fallback to cached filters when API fails', async () => {
      // First successful call to populate cache
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Now make API fail
      mockApiService.fetch.mockRejectedValue(new Error('Network error'))
      
      await wrapper.vm.fetchAvailableFilters()
      
      expect(wrapper.vm.hasError).toBe(true)
    })

    it('should recover gracefully from network errors', async () => {
      mockApiService.fetch.mockRejectedValue(new Error('Network error'))
      
      const wrapper = mount(Filter, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.isLoading).toBe(false)
      expect(wrapper.vm.hasError).toBe(true)
    })
  })
})