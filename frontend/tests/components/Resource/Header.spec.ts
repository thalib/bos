// TDD Test for Header Component - Write tests first before implementation
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import Header from '@/components/Resource/Header.vue'

// Mock the API service
const mockApiService = {
  fetch: vi.fn(),
  create: vi.fn(),
  get: vi.fn(),
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

describe('Header Component', () => {
  beforeEach(async () => {
    vi.clearAllMocks()
    
    // Clear router state
    await router.replace({ query: {} })
  })

  describe('Layout Coordination', () => {
    it('should render responsive header layout with Bootstrap 5.3 styling', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products'
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.header-container').exists()).toBe(true)
      expect(wrapper.find('.row.align-items-center').exists()).toBe(true)
      expect(wrapper.find('h2').text()).toBe('Products')
      expect(wrapper.find('.btn-group').exists()).toBe(true)
    })

    it('should coordinate child components in proper slots', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products'
        },
        slots: {
          search: '<div class="test-search">Search Component</div>',
          filters: '<div class="test-filters">Filter Component</div>'
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.test-search').exists()).toBe(true)
      expect(wrapper.find('.test-filters').exists()).toBe(true)
      expect(wrapper.text()).toContain('Search Component')
      expect(wrapper.text()).toContain('Filter Component')
    })

    it('should handle action button events correctly', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showCreate: true,
          showExport: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Test create button
      const createBtn = wrapper.find('.btn-primary')
      expect(createBtn.exists()).toBe(true)
      
      await createBtn.trigger('click')
      
      expect(wrapper.emitted('action-triggered')).toBeTruthy()
      const emitted = wrapper.emitted('action-triggered')
      expect(emitted![0][0]).toEqual({ action: 'create' })
    })

    it('should adapt layout for mobile and desktop screens', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showCreate: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Check responsive classes
      expect(wrapper.find('.d-none.d-lg-block').exists()).toBe(true) // Desktop actions
      expect(wrapper.find('.d-lg-none').exists()).toBe(true) // Mobile actions
      expect(wrapper.find('.col-lg-6').exists()).toBe(true) // Responsive columns
    })

    it('should manage loading states across child components', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          loading: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Check loading indicator
      expect(wrapper.find('.spinner-border').exists()).toBe(true)
      
      // Check buttons are disabled when loading
      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.element.disabled).toBe(true)
      })
    })

    it('should display component title and action counts', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          itemCount: 42
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('h2').text()).toBe('Products')
      expect(wrapper.find('.badge.bg-secondary').text()).toBe('42')
    })

    it('should be accessible (ARIA labels, keyboard navigation)', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showCreate: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Check ARIA labels
      const btnGroup = wrapper.find('.btn-group')
      expect(btnGroup.attributes('role')).toBe('group')
      expect(btnGroup.attributes('aria-label')).toBe('Page actions')
      
      // Check buttons are focusable
      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.element.tagName).toBe('BUTTON')
      })
    })
  })

  describe('Action Handling', () => {
    it('should handle create action button click', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showCreate: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      const createBtn = wrapper.find('.btn-primary')
      await createBtn.trigger('click')
      
      const emitted = wrapper.emitted('action-triggered')
      expect(emitted).toBeTruthy()
      expect(emitted![0][0]).toEqual({ action: 'create' })
    })

    it('should handle export action with API service', async () => {
      mockApiService.request.mockResolvedValue({ success: true, data: 'csv data' })
      
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showExport: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Trigger export action
      await wrapper.vm.handleExportAction()
      
      expect(wrapper.emitted('action-triggered')).toBeTruthy()
      const emitted = wrapper.emitted('action-triggered')
      expect(emitted![0][0]).toEqual({ action: 'export' })
    })

    it('should handle import action with file selection', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showImport: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      await wrapper.vm.handleImportAction()
      
      expect(wrapper.emitted('action-triggered')).toBeTruthy()
      const emitted = wrapper.emitted('action-triggered')
      expect(emitted![0][0]).toEqual({ action: 'import' })
    })

    it('should emit action-triggered events correctly', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products'
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      await wrapper.vm.handleRefreshAction()
      
      expect(wrapper.emitted('action-triggered')).toBeTruthy()
      const emitted = wrapper.emitted('action-triggered')
      expect(emitted![0][0]).toEqual({ action: 'refresh' })
    })

    it('should handle errors gracefully with notify service', async () => {
      mockApiService.request.mockRejectedValue(new Error('Export failed'))
      
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showExport: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      await wrapper.vm.handleExportAction()
      
      expect(mockNotifyService.error).toHaveBeenCalledWith(
        'Failed to export. Please try again.',
        'Export Error'
      )
    })
  })

  describe('Responsive Behavior', () => {
    it('should show mobile action bar on small screens', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showCreate: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Mobile action bar should exist
      expect(wrapper.find('.d-lg-none').exists()).toBe(true)
      
      // Mobile create button should exist
      const mobileActions = wrapper.find('.d-lg-none')
      expect(mobileActions.find('.btn-primary').exists()).toBe(true)
    })

    it('should use dropdown actions on desktop', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products',
          showExport: true,
          showImport: true
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Desktop dropdown should exist
      expect(wrapper.find('.dropdown-toggle').exists()).toBe(true)
      expect(wrapper.find('.dropdown-menu').exists()).toBe(true)
      
      // Dropdown items should exist
      expect(wrapper.find('.dropdown-item').exists()).toBe(true)
    })

    it('should adapt child component layout responsively', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products'
        },
        slots: {
          search: '<div class="test-search">Search</div>',
          filters: '<div class="test-filters">Filters</div>'
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Check responsive grid for child components
      const searchCol = wrapper.find('.col-lg-6')
      expect(searchCol.exists()).toBe(true)
    })
  })

  describe('Child Component Integration', () => {
    it('should provide search slot for Search component', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products'
        },
        slots: {
          search: '<div class="custom-search">Custom Search</div>'
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.custom-search').exists()).toBe(true)
      expect(wrapper.text()).toContain('Custom Search')
    })

    it('should provide filters slot for Filter component', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products'
        },
        slots: {
          filters: '<div class="custom-filters">Custom Filters</div>'
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('.custom-filters').exists()).toBe(true)
      expect(wrapper.text()).toContain('Custom Filters')
    })

    it('should handle child component events correctly', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products'
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Test slot binding for loading state
      expect(wrapper.vm.loading).toBeDefined()
    })

    it('should show fallback content when slots are empty', async () => {
      const wrapper = mount(Header, {
        props: { 
          title: 'Products',
          resource: 'products'
        },
        global: { plugins: [router] }
      })

      await wrapper.vm.$nextTick()
      
      // Should show fallback messages
      expect(wrapper.text()).toContain('Search component not provided')
      expect(wrapper.text()).toContain('Filter component not provided')
    })
  })
})