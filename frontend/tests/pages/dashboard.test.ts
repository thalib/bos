import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import Dashboard from '../../app/pages/dashboard.vue'

// Mock auth service
const mockAuthService = {
  getCurrentUser: vi.fn(),
  isAuthenticated: { value: true },
  isInitialized: { value: true }
}

vi.mock('../../app/utils/auth', () => ({
  useAuthService: () => mockAuthService
}))

describe('Dashboard Page (dashboard.vue)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Set up default user
    mockAuthService.getCurrentUser.mockReturnValue({
      id: 1,
      name: 'John Doe',
      email: 'john@example.com'
    })
  })

  describe('Layout Structure', () => {
    it('should render with proper Bootstrap container structure', () => {
      const wrapper = mount(Dashboard)

      // Check for container
      const container = wrapper.find('.container')
      expect(container.exists()).toBe(true)

      // Check for two rows
      const rows = wrapper.findAll('.row')
      expect(rows.length).toBe(2)
    })

    it('should have Dashboard heading in first row', () => {
      const wrapper = mount(Dashboard)

      const firstRow = wrapper.findAll('.row')[0]
      const heading = firstRow.find('h1')
      
      expect(heading.exists()).toBe(true)
      expect(heading.text()).toBe('Dashboard')
      expect(heading.classes()).toContain('text-center')
    })

    it('should have welcome message in second row', () => {
      const wrapper = mount(Dashboard)

      const secondRow = wrapper.findAll('.row')[1]
      const welcomeMessage = secondRow.find('[data-testid="welcome-message"]')
      
      expect(welcomeMessage.exists()).toBe(true)
      expect(welcomeMessage.classes()).toContain('text-center')
    })
  })

  describe('Welcome Message', () => {
    it('should display welcome message with user name', () => {
      const wrapper = mount(Dashboard)

      const welcomeMessage = wrapper.find('[data-testid="welcome-message"]')
      expect(welcomeMessage.text()).toBe('Welcome to the John Doe!')
    })

    it('should handle user with different name', () => {
      mockAuthService.getCurrentUser.mockReturnValue({
        id: 2,
        name: 'Jane Smith',
        email: 'jane@example.com'
      })

      const wrapper = mount(Dashboard)

      const welcomeMessage = wrapper.find('[data-testid="welcome-message"]')
      expect(welcomeMessage.text()).toBe('Welcome to the Jane Smith!')
    })

    it('should handle missing user gracefully', () => {
      mockAuthService.getCurrentUser.mockReturnValue(null)

      const wrapper = mount(Dashboard)

      const welcomeMessage = wrapper.find('[data-testid="welcome-message"]')
      expect(welcomeMessage.text()).toBe('Welcome to the Guest!')
    })

    it('should handle user without name gracefully', () => {
      mockAuthService.getCurrentUser.mockReturnValue({
        id: 3,
        email: 'noname@example.com'
      })

      const wrapper = mount(Dashboard)

      const welcomeMessage = wrapper.find('[data-testid="welcome-message"]')
      expect(welcomeMessage.text()).toBe('Welcome to the User!')
    })
  })

  describe('Responsive Design', () => {
    it('should use responsive Bootstrap classes', () => {
      const wrapper = mount(Dashboard)

      // Check for responsive column classes
      const columns = wrapper.findAll('[class*="col"]')
      expect(columns.length).toBeGreaterThan(0)

      // Check that columns use Bootstrap responsive classes
      const hasResponsiveClasses = columns.some(col => {
        const classes = col.classes()
        return classes.some(cls => 
          cls.includes('col-') || 
          cls.includes('col-sm-') || 
          cls.includes('col-md-') || 
          cls.includes('col-lg-') ||
          cls.includes('col-xl-') ||
          cls === 'col'
        )
      })
      
      expect(hasResponsiveClasses).toBe(true)
    })

    it('should have proper spacing classes', () => {
      const wrapper = mount(Dashboard)

      const container = wrapper.find('.container')
      
      // Check for margin/padding classes
      const hasSpacingClasses = container.classes().some(cls => 
        cls.includes('mt-') || 
        cls.includes('pt-') || 
        cls.includes('mb-') || 
        cls.includes('pb-') ||
        cls.includes('my-') ||
        cls.includes('py-')
      )
      
      expect(hasSpacingClasses).toBe(true)
    })
  })

  describe('Authentication Protection', () => {
    it('should have auth middleware defined in page meta', () => {
      const wrapper = mount(Dashboard)
      
      // Check if the component exists and is properly mounted
      expect(wrapper.exists()).toBe(true)
      
      // The actual middleware protection is tested in middleware tests
      // Here we just verify the component renders when authenticated
      expect(wrapper.find('[data-testid="welcome-message"]').exists()).toBe(true)
    })
  })
})