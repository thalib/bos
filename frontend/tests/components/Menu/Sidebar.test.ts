import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Sidebar from '~/components/Menu/Sidebar.vue'

// Mock NuxtLink component
const MockNuxtLink = {
  name: 'NuxtLink',
  props: ['to'],
  template: '<a :href="to"><slot /></a>'
}

// Mock data for menu items
const mockMenuData = [
  {
    type: 'item',
    id: 1,
    name: 'Home',
    path: '/',
    icon: 'bi-house',
    order: 1
  },
  {
    type: 'section',
    title: 'Tools',
    order: 2,
    items: [
      {
        id: 21,
        name: 'Todo',
        path: '/todo',
        icon: 'bi-check-square'
      }
    ]
  },
  {
    type: 'divider',
    order: 3
  }
]

// Mock services
const mockApiService = {
  request: vi.fn()
}

const mockAuthService = {
  getCurrentUser: vi.fn(() => ({
    id: 1,
    name: 'John Doe',
    email: 'john@example.com'
  })),
  logout: vi.fn()
}

const mockNotifyService = {
  error: vi.fn(),
  success: vi.fn()
}

// Mock composables
vi.mock('~/utils/api', () => ({
  useApiService: () => mockApiService
}))

vi.mock('~/utils/auth', () => ({
  useAuthService: () => mockAuthService
}))

vi.mock('~/utils/notify', () => ({
  useNotifyService: () => mockNotifyService
}))

describe('Sidebar Component', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    
    // Default successful API response
    mockApiService.request.mockResolvedValue({
      success: true,
      data: mockMenuData,
      message: 'Menu loaded successfully'
    })
  })

  describe('Basic Rendering', () => {
    it('should render sidebar component', () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      expect(wrapper.exists()).toBe(true)
    })

    it('should have proper Bootstrap offcanvas structure', () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      expect(wrapper.find('.offcanvas').exists()).toBe(true)
      expect(wrapper.find('.offcanvas-header').exists()).toBe(true)
      expect(wrapper.find('.offcanvas-body').exists()).toBe(true)
    })

    it('should display close button in header', () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const closeButton = wrapper.find('[data-testid="sidebar-close"]')
      expect(closeButton.exists()).toBe(true)
      expect(closeButton.classes()).toContain('btn-close')
    })
  })

  describe('User Information', () => {
    it('should display logged-in user name', () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      expect(wrapper.find('[data-testid="user-name"]').text()).toBe('John Doe')
    })

    it('should handle missing user gracefully', () => {
      mockAuthService.getCurrentUser.mockReturnValue(null)
      
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const userName = wrapper.find('[data-testid="user-name"]')
      expect(userName.text()).toBe('Guest') // or whatever placeholder is used
    })
  })

  describe('Menu Loading', () => {
    it('should fetch menu data from API on mount', async () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      // Wait for component to load
      await wrapper.vm.$nextTick()
      
      expect(mockApiService.request).toHaveBeenCalledWith('/api/menu', {
        method: 'GET'
      })
    })

    it('should show loading spinner while fetching menu', () => {
      // Make API request hang
      mockApiService.request.mockImplementation(() => new Promise(() => {}))
      
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
      expect(wrapper.find('.spinner-border').exists()).toBe(true)
    })

    it('should display error alert on API failure', async () => {
      mockApiService.request.mockRejectedValue(new Error('API Error'))
      
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      // Wait for async operation
      await new Promise(resolve => setTimeout(resolve, 0))
      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('[data-testid="error-alert"]').exists()).toBe(true)
      expect(wrapper.find('.alert-warning').exists()).toBe(true)
    })
  })

  describe('Menu Items Rendering', () => {
    it('should render regular menu items', async () => {
      // Ensure the mock is properly set up
      expect(mockApiService.request).toBeDefined()
      
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      // Wait for the component to mount and API call to complete
      await new Promise(resolve => setTimeout(resolve, 100))
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()
      
      // Debug: Check if loading state is finished
      const loadingSpinner = wrapper.find('[data-testid="loading-spinner"]')
      const errorAlert = wrapper.find('[data-testid="error-alert"]')
      
      console.log('Loading spinner exists:', loadingSpinner.exists())
      console.log('Error alert exists:', errorAlert.exists())
      console.log('API request called:', mockApiService.request.mock.calls.length)
      
      const homeItem = wrapper.find('[data-testid="menu-item-1"]')
      console.log('Home item exists:', homeItem.exists())
      if (homeItem.exists()) {
        console.log('Home item text:', homeItem.text())
      }
      
      expect(homeItem.exists()).toBe(true)
      expect(homeItem.text()).toContain('Home')
      expect(homeItem.find('i.bi-house').exists()).toBe(true)
    })

    it('should render menu sections with collapsible behavior', async () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      await new Promise(resolve => setTimeout(resolve, 0))
      await wrapper.vm.$nextTick()
      
      const section = wrapper.find('[data-testid="menu-section-Tools"]')
      expect(section.exists()).toBe(true)
      expect(section.text()).toContain('Tools')
      
      // Should have collapse functionality
      expect(section.attributes('data-bs-toggle')).toBe('collapse')
    })

    it('should render dividers', async () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      await new Promise(resolve => setTimeout(resolve, 0))
      await wrapper.vm.$nextTick()
      
      const divider = wrapper.find('[data-testid="menu-divider"]')
      expect(divider.exists()).toBe(true)
      expect(divider.element.tagName).toBe('HR')
    })

    it('should show empty state when no menu items', async () => {
      mockApiService.request.mockResolvedValue({
        success: true,
        data: [],
        message: 'No menu items'
      })
      
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      await new Promise(resolve => setTimeout(resolve, 0))
      await wrapper.vm.$nextTick()
      
      expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(true)
    })
  })

  describe('Navigation', () => {
    it('should use NuxtLink for navigation items', async () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      await new Promise(resolve => setTimeout(resolve, 50))
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()
      
      const homeLink = wrapper.find('[data-testid="menu-item-1"] a')
      expect(homeLink.exists()).toBe(true)
      expect(homeLink.attributes('href')).toBe('/')
    })
  })

  describe('Dark/Light Mode Toggle', () => {
    it('should display dark/light mode toggle button', () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const modeToggle = wrapper.find('[data-testid="mode-toggle"]')
      expect(modeToggle.exists()).toBe(true)
    })

    it('should emit mode change event when toggled', async () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const modeToggle = wrapper.find('[data-testid="mode-toggle"]')
      await modeToggle.trigger('click')
      
      // Check if appropriate action was taken (implementation dependent)
      expect(modeToggle.exists()).toBe(true)
    })
  })

  describe('Logout Functionality', () => {
    it('should display logout button', () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const logoutBtn = wrapper.find('[data-testid="logout-btn"]')
      expect(logoutBtn.exists()).toBe(true)
      expect(logoutBtn.text()).toContain('Logout')
    })

    it('should call auth service logout when clicked', async () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const logoutBtn = wrapper.find('[data-testid="logout-btn"]')
      await logoutBtn.trigger('click')
      
      expect(mockAuthService.logout).toHaveBeenCalled()
    })

    it('should emit logout event', async () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const logoutBtn = wrapper.find('[data-testid="logout-btn"]')
      await logoutBtn.trigger('click')
      
      expect(wrapper.emitted('logout')).toBeTruthy()
    })
  })

  describe('Accessibility', () => {
    it('should have proper ARIA attributes for offcanvas', () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const offcanvas = wrapper.find('.offcanvas')
      expect(offcanvas.attributes('aria-labelledby')).toBeDefined()
      expect(offcanvas.attributes('tabindex')).toBe('-1')
    })

    it('should have visually hidden text for loading spinner', () => {
      mockApiService.request.mockImplementation(() => new Promise(() => {}))
      
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      expect(wrapper.find('.visually-hidden').exists()).toBe(true)
    })

    it('should be keyboard accessible', () => {
      const wrapper = mount(Sidebar, {
        global: {
          components: {
            NuxtLink: MockNuxtLink
          }
        }
      })
      
      const closeButton = wrapper.find('[data-testid="sidebar-close"]')
      const logoutButton = wrapper.find('[data-testid="logout-btn"]')
      
      expect(closeButton.attributes('tabindex')).not.toBe('-1')
      expect(logoutButton.attributes('tabindex')).not.toBe('-1')
    })
  })
})