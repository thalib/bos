// TDD Test for Search Component - Write tests first before implementation
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import Search from '@/components/Resource/Search.vue'

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

// Mock lodash debounce
vi.mock('lodash-es', () => ({
  debounce: vi.fn((fn) => {
    // Return the function directly for testing (no actual debouncing)
    return fn
  })
}))

// Mock router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: { template: '<div>Home</div>' } },
    { path: '/list/:resource', component: { template: '<div>List</div>' } }
  ]
})

describe('Search Component', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    
    // Clear router state completely
    router.replace({ query: {} })
    
    // Default successful API response
    mockApiService.fetch.mockResolvedValue({
      success: true,
      data: [
        { id: 1, name: 'Test Item 1' },
        { id: 2, name: 'Test Item 2' }
      ],
      pagination: {
        totalItems: 2,
        currentPage: 1,
        itemsPerPage: 15,
        totalPages: 1
      }
    })
  })

  describe('Component Rendering', () => {
    it('should render search input with proper Bootstrap 5.3 styling', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      expect(wrapper.find('.search-container').exists()).toBe(true)
      expect(wrapper.find('.input-group').exists()).toBe(true)
      expect(wrapper.find('input.form-control').exists()).toBe(true)
      expect(wrapper.find('input[type="search"]').exists()).toBe(true)
    })

    it('should have proper Bootstrap classes for input group', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const inputGroup = wrapper.find('.input-group')
      expect(inputGroup.exists()).toBe(true)
      expect(inputGroup.classes()).toContain('mb-3')

      const input = wrapper.find('input')
      expect(input.classes()).toContain('form-control')
    })

    it('should show clear button when search has value', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products', initialSearch: 'test query' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()

      const clearButton = wrapper.find('button[aria-label="Clear search"]')
      expect(clearButton.exists()).toBe(true)
      expect(clearButton.find('.bi-x-lg').exists()).toBe(true)
    })

    it('should not show clear button when search is empty', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const clearButton = wrapper.find('button[aria-label="Clear search"]')
      expect(clearButton.exists()).toBe(false)
    })

    it('should display loading indicator during search', async () => {
      // Make API call never resolve to test loading state
      mockApiService.fetch.mockImplementation(() => new Promise(() => {}))

      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test')
      await input.trigger('input')

      await wrapper.vm.$nextTick()

      const loadingButton = wrapper.find('button .spinner-border')
      expect(loadingButton.exists()).toBe(true)
    })
  })

  describe('Search Initialization', () => {
    it('should initialize with empty search when no route query', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('')
    })

    it('should initialize with route query search parameter', async () => {
      await router.push({ query: { search: 'initial search' } })

      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('initial search')
    })

    it('should initialize with initialSearch prop', async () => {
      // Ensure route has no search query for this test
      await router.replace({ query: {} })
      
      const wrapper = mount(Search, {
        props: { resource: 'products', initialSearch: 'prop search' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('prop search')
    })

    it('should prioritize route query over initialSearch prop', async () => {
      await router.push({ query: { search: 'route search' } })

      const wrapper = mount(Search, {
        props: { resource: 'products', initialSearch: 'prop search' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      expect(input.element.value).toBe('route search')
    })
  })

  describe('Search Behavior', () => {
    it('should call API service when search term is entered', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test search')
      await input.trigger('input')

      expect(mockApiService.fetch).toHaveBeenCalledWith('products', {
        search: 'test search'
      })
    })

    it('should not search with less than 2 characters', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('t')
      await input.trigger('input')

      expect(mockNotifyService.warning).toHaveBeenCalledWith(
        'Search term must be at least 2 characters'
      )
      // Should not call API for short search
      expect(mockApiService.fetch).not.toHaveBeenCalled()
    })

    it('should update URL parameters when search changes', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test search')
      await input.trigger('input')

      await vi.waitFor(() => {
        expect(router.currentRoute.value.query.search).toBe('test search')
      })
    })

    it('should handle API errors gracefully with notify service', async () => {
      mockApiService.fetch.mockRejectedValue(new Error('API Error'))

      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test search')
      await input.trigger('input')

      await vi.waitFor(() => {
        expect(mockNotifyService.error).toHaveBeenCalledWith(
          'Search temporarily unavailable. Please try again.',
          'Search Error'
        )
      })
    })

    it('should clear search and reset URL when clear button clicked', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products', initialSearch: 'test query' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()

      const clearButton = wrapper.find('button[aria-label="Clear search"]')
      await clearButton.trigger('click')

      expect(wrapper.find('input').element.value).toBe('')
      expect(router.currentRoute.value.query.search).toBeUndefined()
    })

    it('should emit search-applied event with correct payload', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test search')
      await input.trigger('input')

      await vi.waitFor(() => {
        expect(wrapper.emitted('search-applied')).toBeTruthy()
        expect(wrapper.emitted('search-applied')![0]).toEqual([{
          search: 'test search',
          hasResults: true
        }])
      })
    })

    it('should emit event with hasResults false when no results', async () => {
      mockApiService.fetch.mockResolvedValue({
        success: true,
        data: [],
        pagination: { totalItems: 0 }
      })

      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('no results')
      await input.trigger('input')

      await vi.waitFor(() => {
        expect(wrapper.emitted('search-applied')).toBeTruthy()
        expect(wrapper.emitted('search-applied')![0]).toEqual([{
          search: 'no results',
          hasResults: false
        }])
      })
    })

    it('should handle empty search correctly', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products', initialSearch: 'test' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('')
      await input.trigger('input')

      await vi.waitFor(() => {
        expect(wrapper.emitted('search-applied')).toBeTruthy()
        expect(wrapper.emitted('search-applied')![0]).toEqual([{
          search: '',
          hasResults: false
        }])
      })

      expect(router.currentRoute.value.query.search).toBeUndefined()
    })
  })

  describe('Accessibility', () => {
    it('should have proper ARIA labels', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      expect(input.attributes('aria-label')).toBe('Search')
      expect(input.attributes('aria-describedby')).toBe('search-help')

      const helpText = wrapper.find('#search-help')
      expect(helpText.exists()).toBe(true)
    })

    it('should support keyboard navigation', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test')
      await input.trigger('keyup.enter')

      expect(mockApiService.fetch).toHaveBeenCalledWith('products', {
        search: 'test'
      })
    })

    it('should be accessible to screen readers', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      expect(input.attributes('placeholder')).toBe('Search resources...')

      // Should have help text for screen readers
      const helpText = wrapper.find('#search-help')
      expect(helpText.text()).toContain('Search results update as you type')
    })
  })

  describe('State Management', () => {
    it('should display result count when search has results', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test')
      await input.trigger('input')

      await vi.waitFor(() => {
        const resultBadge = wrapper.find('.badge')
        expect(resultBadge.exists()).toBe(true)
        expect(resultBadge.text()).toContain('2 results')
      })
    })

    it('should not display result count when search is empty', async () => {
      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const resultBadge = wrapper.find('.badge')
      expect(resultBadge.exists()).toBe(false)
    })

    it('should manage loading state correctly', async () => {
      let resolveSearch: (value: any) => void
      mockApiService.fetch.mockImplementation(() => new Promise(resolve => {
        resolveSearch = resolve
      }))

      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test')
      await input.trigger('input')

      // Should show loading state
      await wrapper.vm.$nextTick()
      expect(wrapper.find('.spinner-border').exists()).toBe(true)
      expect(input.attributes('disabled')).toBeDefined()

      // Resolve search
      resolveSearch!({
        success: true,
        data: [{ id: 1 }],
        pagination: { totalItems: 1 }
      })

      await vi.waitFor(() => {
        expect(wrapper.find('.spinner-border').exists()).toBe(false)
        expect(input.attributes('disabled')).toBeUndefined()
      })
    })
  })

  describe('Error Handling', () => {
    it('should handle network errors gracefully', async () => {
      mockApiService.fetch.mockRejectedValue({
        message: 'Network error',
        code: 'NETWORK_ERROR'
      })

      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test')
      await input.trigger('input')

      await vi.waitFor(() => {
        expect(mockNotifyService.error).toHaveBeenCalledWith(
          'Search temporarily unavailable. Please try again.',
          'Search Error'
        )
      })
    })

    it('should show error state in input when API fails', async () => {
      mockApiService.fetch.mockRejectedValue(new Error('API Error'))

      const wrapper = mount(Search, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      const input = wrapper.find('input')
      await input.setValue('test')
      await input.trigger('input')

      await vi.waitFor(() => {
        expect(input.classes()).toContain('is-invalid')
      })
    })
  })
})