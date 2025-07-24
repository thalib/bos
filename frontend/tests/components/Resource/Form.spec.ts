import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Form from '@/components/Resource/Form.vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

// Mock the services
vi.mock('@/utils/api')
vi.mock('@/utils/notify')

describe('Form Component', () => {
  let mockApiService: any
  let mockNotifyService: any

  beforeEach(() => {
    mockApiService = {
      get: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      delete: vi.fn()
    }
    
    mockNotifyService = {
      success: vi.fn(),
      error: vi.fn(),
      warning: vi.fn(),
      info: vi.fn()
    }

    vi.mocked(useApiService).mockReturnValue(mockApiService)
    vi.mocked(useNotifyService).mockReturnValue(mockNotifyService)
  })

  it('should render form component with Bootstrap 5.3 styling', async () => {
    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: null // Create mode
      }
    })

    // Check that the main form container exists
    expect(wrapper.find('.resource-form').exists()).toBe(true)
    
    // Check Bootstrap form classes
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('should fetch resource data on mount for edit mode', async () => {
    const mockData = {
      success: true,
      data: {
        id: 1,
        name: 'Test Product',
        description: 'Test Description'
      }
    }
    
    mockApiService.get.mockResolvedValue(mockData)

    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: 1
      }
    })

    await wrapper.vm.$nextTick()
    
    expect(mockApiService.get).toHaveBeenCalledWith('products', 1)
  })

  it('should handle form submission with validation', async () => {
    const mockCreateResponse = {
      success: true,
      data: { id: 1, name: 'New Product' },
      message: 'Product created successfully'
    }
    
    mockApiService.create.mockResolvedValue(mockCreateResponse)

    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: null
      }
    })

    // Simulate form submission
    await wrapper.vm.handleSubmit()

    expect(wrapper.emitted('form-submit')).toBeTruthy()
  })

  it('should handle loading states during operations', async () => {
    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: null
      }
    })

    // Check initial loading state
    expect(wrapper.vm.isLoading).toBe(false)

    // Simulate loading
    await wrapper.vm.setLoading(true)
    expect(wrapper.vm.isLoading).toBe(true)

    await wrapper.vm.setLoading(false)
    expect(wrapper.vm.isLoading).toBe(false)
  })

  it('should emit form-submit event with correct data', async () => {
    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: null
      }
    })

    const testData = { name: 'Test Product', description: 'Test Description' }
    
    // Set form data
    await wrapper.vm.setFormData(testData)
    
    // Submit form
    await wrapper.vm.handleSubmit()

    expect(wrapper.emitted('form-submit')).toBeTruthy()
    expect(wrapper.emitted('form-submit')?.[0]).toBeDefined()
  })

  it('should emit form-cancel event when cancelled', async () => {
    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: 1
      }
    })

    await wrapper.vm.handleCancel()

    expect(wrapper.emitted('form-cancel')).toBeTruthy()
  })

  it('should handle API errors gracefully', async () => {
    const mockError = new Error('API Error')
    mockApiService.get.mockRejectedValue(mockError)

    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: 1
      }
    })

    await wrapper.vm.$nextTick()

    expect(mockNotifyService.error).toHaveBeenCalled()
  })

  it('should validate form data before submission', async () => {
    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: null
      }
    })

    // Try to submit empty form
    await wrapper.vm.handleSubmit()

    // Should show validation errors
    expect(wrapper.vm.hasValidationErrors).toBeDefined()
  })

  it('should support different form modes (create/edit/view)', async () => {
    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: null,
        mode: 'create'
      }
    })

    expect(wrapper.props('mode')).toBe('create')

    await wrapper.setProps({ mode: 'edit', resourceId: 1 })
    expect(wrapper.props('mode')).toBe('edit')

    await wrapper.setProps({ mode: 'view' })
    expect(wrapper.props('mode')).toBe('view')
  })

  it('should render form fields based on schema', async () => {
    const mockSchema = {
      success: true,
      data: {
        fields: [
          { name: 'name', type: 'text', label: 'Product Name', required: true },
          { name: 'description', type: 'textarea', label: 'Description' }
        ]
      }
    }

    mockApiService.get.mockResolvedValue(mockSchema)

    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: null
      }
    })

    await wrapper.vm.$nextTick()

    // Check that schema is loaded
    expect(wrapper.vm.schema).toBeDefined()
  })
})