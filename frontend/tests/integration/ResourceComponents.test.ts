import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import MasterDetail from '@/components/Resource/MasterDetail.vue'
import List from '@/components/Resource/List.vue'
import Form from '@/components/Resource/Form.vue'
import DocumentView from '@/components/Resource/DocumentView.vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

// Mock the services
vi.mock('@/utils/api')
vi.mock('@/utils/notify')

describe('Resource Components Integration', () => {
  let mockApiService: any
  let mockNotifyService: any

  beforeEach(() => {
    mockApiService = {
      get: vi.fn(),
      fetch: vi.fn(),
      create: vi.fn(),
      update: vi.fn(),
      delete: vi.fn(),
      request: vi.fn()
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

  it('should render MasterDetail component without errors', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'products',
        mode: 'form'
      },
      global: {
        components: {
          List,
          Form,
          DocumentView
        },
        stubs: {
          'List': { template: '<div data-testid="list-stub">List Component</div>' },
          'Form': { template: '<div data-testid="form-stub">Form Component</div>' },
          'DocumentView': { template: '<div data-testid="document-stub">Document Component</div>' }
        }
      }
    })

    expect(wrapper.find('.master-detail-container').exists()).toBe(true)
  })

  it('should render Form component without errors', async () => {
    mockApiService.get.mockResolvedValue({
      success: true,
      data: { fields: [] }
    })

    const wrapper = mount(Form, {
      props: {
        resource: 'products',
        resourceId: null
      }
    })

    expect(wrapper.find('.resource-form').exists()).toBe(true)
  })

  it('should render DocumentView component without errors', async () => {
    mockApiService.get.mockResolvedValue({
      success: true,
      data: { id: 1, title: 'Test Document' }
    })

    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    expect(wrapper.find('.document-viewer').exists()).toBe(true)
  })

  it('should handle component mode switching in MasterDetail', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'estimates',
        mode: 'document'
      },
      global: {
        stubs: {
          'List': { template: '<div>List</div>' },
          'Form': { template: '<div>Form</div>' },
          'DocumentView': { template: '<div>DocumentView</div>' }
        }
      }
    })

    expect(wrapper.props('mode')).toBe('document')

    await wrapper.setProps({ mode: 'form' })
    expect(wrapper.props('mode')).toBe('form')
  })
})