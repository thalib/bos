// TDD Test for Pagination Component - Write tests first before implementation
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import Pagination from '@/components/Resource/Pagination.vue'

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

describe('Pagination Component', () => {
  beforeEach(async () => {
    vi.clearAllMocks()
    
    // Clear router state completely and wait for navigation
    await router.replace({ query: {} })
    
    // Default successful API response with pagination
    mockApiService.fetch.mockResolvedValue({
      success: true,
      data: { data: [] },
      pagination: {
        totalItems: 100,
        currentPage: 1,
        itemsPerPage: 15,
        totalPages: 7,
        urlPath: '/api/v1/products',
        urlQuery: null,
        nextPage: '/api/v1/products?page=2',
        prevPage: null
      }
    })
  })

  describe('Initialization', () => {
    it('should initialize with route query page parameters', async () => {
      await router.push({ query: { page: '3', per_page: '50' } })
      
      const wrapper = mount(Pagination, {
        props: { 
          resource: 'products',
          initialPage: 3,
          initialPerPage: 50
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.currentPage).toBe(3)
      expect(wrapper.vm.itemsPerPage).toBe(50)
    })

    it('should fetch first page data on mount', async () => {
      // Clear any previous state
      await router.replace({ query: {} })
      
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(mockApiService.fetch).toHaveBeenCalledWith('products', 
        expect.objectContaining({ page: 1, per_page: 15 })
      )
    })

    it('should apply proper Bootstrap 5.3 classes', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.pagination-container').exists()).toBe(true)
      expect(wrapper.find('.pagination').exists()).toBe(true)
      expect(wrapper.find('.page-item').exists()).toBe(true)
      expect(wrapper.find('.page-link').exists()).toBe(true)
      expect(wrapper.find('.form-select').exists()).toBe(true)
    })

    it('should calculate total pages correctly', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Wait for API response
      await new Promise(resolve => setTimeout(resolve, 50))
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.totalPages).toBe(7) // 100 items / 15 per page
    })
  })

  describe('Navigation', () => {
    it('should navigate to specific page', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      await wrapper.vm.goToPage(3)
      
      expect(wrapper.vm.currentPage).toBe(3)
      expect(mockApiService.fetch).toHaveBeenCalledWith('products', 
        expect.objectContaining({ page: 3 })
      )
    })

    it('should handle first/previous page navigation', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Go to page 3 first
      await wrapper.vm.goToPage(3)
      
      // Then go to previous page
      await wrapper.vm.goToPage(2)
      
      expect(wrapper.vm.currentPage).toBe(2)
    })

    it('should handle next/last page navigation', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Go to next page
      await wrapper.vm.goToPage(2)
      
      expect(wrapper.vm.currentPage).toBe(2)
    })

    it('should disable navigation at boundaries', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // At first page
      expect(wrapper.vm.isFirstPage).toBe(true)
      expect(wrapper.vm.isLastPage).toBe(false)
      
      // Go to last page
      wrapper.vm.currentPage = 7
      wrapper.vm.totalItems = 100
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.isLastPage).toBe(true)
    })

    it('should update URL parameters when page changes', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      await wrapper.vm.goToPage(3)
      
      expect(router.currentRoute.value.query.page).toBe('3')
    })
  })

  describe('Per-Page Selection', () => {
    it('should change items per page and reset to page 1', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Go to page 3 first
      await wrapper.vm.goToPage(3)
      
      // Change per page
      wrapper.vm.itemsPerPage = 50
      await wrapper.vm.changePerPage()
      
      expect(wrapper.vm.currentPage).toBe(1)
      expect(wrapper.vm.itemsPerPage).toBe(50)
    })

    it('should update URL with new per_page parameter', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      wrapper.vm.itemsPerPage = 100
      await wrapper.vm.changePerPage()
      
      expect(router.currentRoute.value.query.per_page).toBe('100')
    })

    it('should recalculate total pages when per_page changes', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Set total items
      wrapper.vm.totalItems = 100
      wrapper.vm.itemsPerPage = 50
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.totalPages).toBe(2) // 100 items / 50 per page
    })
  })

  describe('State Management', () => {
    it('should manage loading state during navigation', async () => {
      // Make API call slow to test loading state
      mockApiService.fetch.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)))
      
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      expect(wrapper.vm.isLoading).toBe(true)
      
      await new Promise(resolve => setTimeout(resolve, 150))
      expect(wrapper.vm.isLoading).toBe(false)
    })

    it('should emit page-changed event with correct payload', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Wait for initial data fetch
      await new Promise(resolve => setTimeout(resolve, 50))
      
      await wrapper.vm.goToPage(2)
      
      const emitted = wrapper.emitted('page-changed')
      expect(emitted).toBeTruthy()
      expect(emitted![emitted!.length - 1][0]).toMatchObject({
        page: 2,
        perPage: 15,
        totalItems: expect.any(Number)
      })
    })

    it('should maintain pagination state during navigation', async () => {
      await router.push({ query: { page: '3', per_page: '50' } })
      
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.currentPage).toBe(3)
      expect(wrapper.vm.itemsPerPage).toBe(50)
    })
  })

  describe('Accessibility', () => {
    it('should have proper ARIA labels for navigation', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      const nav = wrapper.find('nav')
      expect(nav.attributes('aria-label')).toBe('Page navigation')
      
      const pageLinks = wrapper.findAll('.page-link')
      if (pageLinks.length > 0) {
        // Check that page links have aria-label
        const firstPageLink = pageLinks.find(link => 
          link.attributes('aria-label')?.includes('First page')
        )
        expect(firstPageLink).toBeTruthy()
      }
    })

    it('should support keyboard navigation', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      const pageLinks = wrapper.findAll('.page-link')
      if (pageLinks.length > 0) {
        // All page links should be focusable
        pageLinks.forEach(link => {
          expect(link.element.tagName).toBe('BUTTON')
        })
      }
    })

    it('should announce page changes to screen readers', async () => {
      const wrapper = mount(Pagination, {
        props: { resource: 'products' },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Set current page to test aria-current
      wrapper.vm.currentPage = 2
      await wrapper.vm.$nextTick()
      
      // Check that current page has aria-current
      const pageButtons = wrapper.findAll('.page-link')
      const currentPageButton = pageButtons.find(button => 
        button.text() === '2'
      )
      
      if (currentPageButton) {
        expect(currentPageButton.attributes('aria-current')).toBe('page')
      }
    })
  })
})