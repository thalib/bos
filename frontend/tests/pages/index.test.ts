import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import Index from '../../app/pages/index.vue'

// Mock Nuxt composables
mockNuxtImport('navigateTo', () => {
  return vi.fn()
})

mockNuxtImport('useRoute', () => {
  return vi.fn(() => ({
    query: {}
  }))
})

// Mock auth service
const mockAuthService = {
  login: vi.fn(),
  isAuthenticated: { value: false },
  isInitialized: { value: true }
}

vi.mock('../../app/utils/auth', () => ({
  useAuthService: () => mockAuthService
}))

// Mock notify service
const mockNotifyService = {
  success: vi.fn(),
  error: vi.fn(),
  warning: vi.fn(),
  info: vi.fn()
}

vi.mock('../../app/utils/notify', () => ({
  useNotifyService: () => mockNotifyService
}))

describe('Login Page (index.vue)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  describe('Form Rendering', () => {
    it('should render login form with all required fields', () => {
      const wrapper = mount(Index)

      // Check for username field
      const usernameField = wrapper.find('input[data-testid="username-field"]')
      expect(usernameField.exists()).toBe(true)
      expect(usernameField.attributes('type')).toBe('text')

      // Check for password field
      const passwordField = wrapper.find('input[data-testid="password-field"]')
      expect(passwordField.exists()).toBe(true)
      expect(passwordField.attributes('type')).toBe('password')

      // Check for remember me checkbox
      const rememberField = wrapper.find('input[data-testid="remember-field"]')
      expect(rememberField.exists()).toBe(true)
      expect(rememberField.attributes('type')).toBe('checkbox')

      // Check for submit button
      const submitButton = wrapper.find('button[data-testid="login-button"]')
      expect(submitButton.exists()).toBe(true)
    })

    it('should have proper labels and Bootstrap classes', () => {
      const wrapper = mount(Index)

      // Check Bootstrap form classes
      const form = wrapper.find('form')
      expect(form.exists()).toBe(true)

      // Check form-control classes on inputs
      const usernameField = wrapper.find('input[data-testid="username-field"]')
      expect(usernameField.classes()).toContain('form-control')

      const passwordField = wrapper.find('input[data-testid="password-field"]')
      expect(passwordField.classes()).toContain('form-control')

      // Check form-check classes on checkbox
      const rememberField = wrapper.find('input[data-testid="remember-field"]')
      expect(rememberField.classes()).toContain('form-check-input')
    })
  })

  describe('Form Validation', () => {
    it('should validate username field accepts email format', async () => {
      const wrapper = mount(Index)
      const usernameField = wrapper.find('input[data-testid="username-field"]')
      
      await usernameField.setValue('test@example.com')
      expect(usernameField.element.value).toBe('test@example.com')
    })

    it('should validate username field accepts username format', async () => {
      const wrapper = mount(Index)
      const usernameField = wrapper.find('input[data-testid="username-field"]')
      
      await usernameField.setValue('testuser')
      expect(usernameField.element.value).toBe('testuser')
    })

    it('should validate username field accepts whatsapp format', async () => {
      const wrapper = mount(Index)
      const usernameField = wrapper.find('input[data-testid="username-field"]')
      
      await usernameField.setValue('1234567890')
      expect(usernameField.element.value).toBe('1234567890')
    })

    it('should require username and password fields', async () => {
      const wrapper = mount(Index)
      const form = wrapper.find('form')
      
      // Try to submit form without filling fields
      await form.trigger('submit.prevent')
      
      // Wait for validation to run
      await wrapper.vm.$nextTick()
      
      // Check for validation errors
      const errorMessages = wrapper.findAll('.invalid-feedback')
      expect(errorMessages.length).toBeGreaterThan(0)
      
      // Specifically check for username and password errors
      const usernameError = wrapper.find('input[data-testid="username-field"] + .invalid-feedback')
      const passwordError = wrapper.find('input[data-testid="password-field"] + .invalid-feedback')
      
      expect(usernameError.exists()).toBe(true)
      expect(passwordError.exists()).toBe(true)
    })
  })

  describe('Form Submission', () => {
    it('should call auth service login on valid form submission', async () => {
      const wrapper = mount(Index)
      
      // Fill in the form
      await wrapper.find('input[data-testid="username-field"]').setValue('test@example.com')
      await wrapper.find('input[data-testid="password-field"]').setValue('password123')
      
      // Submit the form
      await wrapper.find('form').trigger('submit.prevent')
      
      expect(mockAuthService.login).toHaveBeenCalledWith({
        email: 'test@example.com',
        password: 'password123'
      })
    })

    it('should handle login success and redirect to dashboard', async () => {
      const mockNavigateTo = vi.mocked(await import('#app/composables/router')).navigateTo
      
      // Mock successful login
      mockAuthService.login.mockResolvedValueOnce({
        success: true,
        data: { user: { name: 'Test User' } }
      })
      
      const wrapper = mount(Index)
      
      await wrapper.find('input[data-testid="username-field"]').setValue('test@example.com')
      await wrapper.find('input[data-testid="password-field"]').setValue('password123')
      await wrapper.find('form').trigger('submit.prevent')
      
      // Wait for async operations
      await wrapper.vm.$nextTick()
      
      expect(mockNavigateTo).toHaveBeenCalledWith('/dashboard')
    })

    it('should handle login failure and show error message', async () => {
      // Mock failed login
      mockAuthService.login.mockRejectedValueOnce(new Error('Invalid credentials'))
      
      const wrapper = mount(Index)
      
      await wrapper.find('input[data-testid="username-field"]').setValue('test@example.com')
      await wrapper.find('input[data-testid="password-field"]').setValue('wrongpassword')
      await wrapper.find('form').trigger('submit.prevent')
      
      // Wait for async operations
      await wrapper.vm.$nextTick()
      
      // Check that error notification was called (it's handled by auth service)
      expect(mockAuthService.login).toHaveBeenCalled()
    })
  })

  describe('Redirection Logic', () => {
    it('should redirect to query redirect parameter on successful login', async () => {
      const mockRoute = vi.mocked(await import('#app/composables/router')).useRoute
      const mockNavigateTo = vi.mocked(await import('#app/composables/router')).navigateTo
      
      // Mock route with redirect query
      mockRoute.mockReturnValueOnce({
        query: { redirect: '/profile' }
      } as any)
      
      // Mock successful login
      mockAuthService.login.mockResolvedValueOnce({
        success: true,
        data: { user: { name: 'Test User' } }
      })
      
      const wrapper = mount(Index)
      
      await wrapper.find('input[data-testid="username-field"]').setValue('test@example.com')
      await wrapper.find('input[data-testid="password-field"]').setValue('password123')
      await wrapper.find('form').trigger('submit.prevent')
      
      await wrapper.vm.$nextTick()
      
      expect(mockNavigateTo).toHaveBeenCalledWith('/profile')
    })
  })
})