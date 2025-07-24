// TDD Test for Resource Page - Write tests first before implementation
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import ResourcePage from '@/pages/list/[resource].vue'

// Mock the API service
const mockApiService = {
  fetch: vi.fn(),
  get: vi.fn(),
  create: vi.fn(),
  update: vi.fn(),
  delete: vi.fn(),
  request: vi.fn()
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

describe('Resource List Page', () => {
  beforeEach(async () => {
    vi.clearAllMocks()
    
    // Clear router state
    await router.replace({ path: '/list/products' })
    
    // Default successful API responses
    mockApiService.get.mockResolvedValue({
      success: true,
      data: {
        id: 60,
        name: 'Products',
        path: '/list/products',
        icon: 'bi-box',
        mode: 'form'
      }
    })
    
    mockApiService.fetch.mockResolvedValue({
      success: true,
      data: { data: [] },
      pagination: { totalItems: 0 }
    })
  })

  describe('Mode Determination', () => {
    it('should determine mode (form/document) from menu configuration', async () => {
      mockApiService.get.mockResolvedValue({
        success: true,
        data: { mode: 'form' }
      })
      
      const wrapper = mount(ResourcePage, {
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Wait for API call
      await new Promise(resolve => setTimeout(resolve, 50))
      
      expect(wrapper.vm.componentMode).toBe('form')
    })

    it('should render Header component with appropriate actions', async () => {
      const wrapper = mount(ResourcePage, {
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.findComponent({ name: 'Header' }).exists()).toBe(true)
    })

    it('should coordinate MasterDetail component correctly', async () => {
      const wrapper = mount(ResourcePage, {
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Check if main content area exists
      expect(wrapper.find('.main-content').exists()).toBe(true)
    })

    it('should handle pagination via Pagination component', async () => {
      const wrapper = mount(ResourcePage, {
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.pagination-container').exists()).toBe(true)
    })

    it('should sync URL state across all components', async () => {
      await router.push({ path: '/list/products', query: { page: '2', search: 'test' } })
      
      const wrapper = mount(ResourcePage, {
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.resourceName).toBe('products')
    })

    it('should handle component mode switching', async () => {
      const wrapper = mount(ResourcePage, {
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Initially form mode
      expect(wrapper.vm.componentMode).toBe('form')
      
      // Change to document mode
      wrapper.vm.menuConfiguration = { mode: 'doc' }
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.componentMode).toBe('document')
    })

    it('should manage global loading states', async () => {
      const wrapper = mount(ResourcePage, {
        global: { plugins: [router] }
      })

      // Should be loading initially
      expect(wrapper.vm.isInitializing).toBe(true)
      
      await new Promise(resolve => setTimeout(resolve, 100))
      
      expect(wrapper.vm.isInitializing).toBe(false)
    })

    it('should integrate error handling across components', async () => {
      mockApiService.get.mockRejectedValue(new Error('Failed to load'))
      
      const wrapper = mount(ResourcePage, {
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Wait for error handling
      await new Promise(resolve => setTimeout(resolve, 50))
      
      expect(wrapper.vm.hasGlobalError).toBe(true)
      expect(mockNotifyService.error).toHaveBeenCalled()
    })
  })
})