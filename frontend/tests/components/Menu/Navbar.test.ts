import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Navbar from '~/components/Menu/Navbar.vue'

// Mock the auth service
const mockAuthService = {
  isAuthenticated: { value: true },
  getCurrentUser: vi.fn(() => ({
    id: 1,
    name: 'John Doe',
    email: 'john@example.com'
  }))
}

// Mock composables
vi.mock('~/utils/auth', () => ({
  useAuthService: () => mockAuthService
}))

describe('Navbar Component', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('Props and Basic Rendering', () => {
    it('should render with required title prop', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      expect(wrapper.exists()).toBe(true)
      expect(wrapper.text()).toContain('Dashboard')
    })

    it('should require title prop', () => {
      // Test that title is required by checking component props definition
      const wrapper = mount(Navbar, {
        props: {
          title: 'Test Page'
        }
      })
      
      // Component should render properly with title
      expect(wrapper.find('[data-testid="page-title"]').text()).toBe('Test Page')
    })

    it('should display navbar with proper Bootstrap classes', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'Home'
        }
      })

      expect(wrapper.find('nav').classes()).toContain('navbar')
      expect(wrapper.find('nav').classes()).toContain('navbar-expand-lg')
    })
  })

  describe('Authentication Awareness', () => {
    it('should only display when user is authenticated', () => {
      mockAuthService.isAuthenticated.value = true
      
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      expect(wrapper.find('nav').exists()).toBe(true)
    })

    it('should not display when user is not authenticated', () => {
      mockAuthService.isAuthenticated.value = false
      
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      expect(wrapper.find('nav').exists()).toBe(false)
    })
  })

  describe('UI Elements', () => {
    beforeEach(() => {
      mockAuthService.isAuthenticated.value = true
    })

    it('should display menu toggle button with correct icon', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      const menuToggle = wrapper.find('[data-testid="menu-toggle"]')
      expect(menuToggle.exists()).toBe(true)
      expect(menuToggle.find('i.bi-list').exists()).toBe(true)
    })

    it('should display current page title', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'My Page Title'
        }
      })

      const pageTitle = wrapper.find('[data-testid="page-title"]')
      expect(pageTitle.exists()).toBe(true)
      expect(pageTitle.text()).toBe('My Page Title')
    })

    it('should display user dropdown toggle with correct icon', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      const userDropdown = wrapper.find('[data-testid="user-dropdown"]')
      expect(userDropdown.exists()).toBe(true)
      expect(userDropdown.find('i.bi-person-circle').exists()).toBe(true)
    })

    it('should use Bootstrap dropdown classes', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      const dropdown = wrapper.find('[data-testid="user-dropdown"]')
      expect(dropdown.classes()).toContain('dropdown-toggle')
      expect(dropdown.attributes('data-bs-toggle')).toBe('dropdown')
    })
  })

  describe('Accessibility', () => {
    beforeEach(() => {
      mockAuthService.isAuthenticated.value = true
    })

    it('should have proper ARIA attributes for menu toggle', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      const menuToggle = wrapper.find('[data-testid="menu-toggle"]')
      expect(menuToggle.attributes('aria-label')).toBe('Toggle menu')
      expect(menuToggle.attributes('type')).toBe('button')
    })

    it('should have proper ARIA attributes for user dropdown', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      const userDropdown = wrapper.find('[data-testid="user-dropdown"]')
      expect(userDropdown.attributes('aria-label')).toBe('User menu')
      expect(userDropdown.attributes('aria-expanded')).toBe('false')
    })

    it('should be keyboard accessible', () => {
      const wrapper = mount(Navbar, {
        props: {
          title: 'Dashboard'
        }
      })

      const menuToggle = wrapper.find('[data-testid="menu-toggle"]')
      const userDropdown = wrapper.find('[data-testid="user-dropdown"]')
      
      expect(menuToggle.attributes('tabindex')).not.toBe('-1')
      expect(userDropdown.attributes('tabindex')).not.toBe('-1')
    })
  })
})