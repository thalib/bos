import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import DocumentView from '@/components/Resource/DocumentView.vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

// Mock the services
vi.mock('@/utils/api')
vi.mock('@/utils/notify')

// Mock HTML5 Canvas
const mockCanvas = {
  getContext: vi.fn(() => ({
    fillStyle: '',
    fillRect: vi.fn(),
    font: '',
    fillText: vi.fn(),
    clearRect: vi.fn()
  })),
  toDataURL: vi.fn(() => 'data:image/png;base64,mock-image-data'),
  width: 794,
  height: 1123
}

Object.defineProperty(HTMLCanvasElement.prototype, 'getContext', {
  value: mockCanvas.getContext
})

Object.defineProperty(HTMLCanvasElement.prototype, 'toDataURL', {
  value: mockCanvas.toDataURL
})

describe('DocumentView Component', () => {
  let mockApiService: any
  let mockNotifyService: any

  beforeEach(() => {
    mockApiService = {
      get: vi.fn(),
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

    // Mock window.open for print functionality
    Object.defineProperty(window, 'open', {
      value: vi.fn(() => ({
        document: {
          write: vi.fn(),
          close: vi.fn()
        },
        print: vi.fn()
      })),
      writable: true
    })

    // Mock URL.createObjectURL
    Object.defineProperty(URL, 'createObjectURL', {
      value: vi.fn(() => 'mock-blob-url'),
      writable: true
    })

    Object.defineProperty(URL, 'revokeObjectURL', {
      value: vi.fn(),
      writable: true
    })
  })

  it('should render document viewer with Bootstrap 5.3 styling', async () => {
    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    // Check main container exists
    expect(wrapper.find('.document-viewer').exists()).toBe(true)
    
    // Check toolbar exists
    expect(wrapper.find('.toolbar-container.bg-light.border-bottom').exists()).toBe(true)
    
    // Check view controls exist
    expect(wrapper.find('.view-controls.bg-light.border-bottom').exists()).toBe(true)
    
    // Check preview container exists
    expect(wrapper.find('.preview-container').exists()).toBe(true)
  })

  it('should fetch document data and template on mount', async () => {
    const mockDocumentData = {
      success: true,
      data: { id: 1, title: 'Test Estimate', status: 'draft' }
    }
    
    const mockTemplateData = {
      success: true,
      data: { name: 'default', content: '<html>...</html>' }
    }

    mockApiService.get
      .mockResolvedValueOnce(mockDocumentData) // Document data
      .mockResolvedValueOnce(mockTemplateData) // Template data

    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    await wrapper.vm.$nextTick()

    expect(mockApiService.get).toHaveBeenCalledWith('estimates', 1)
    expect(mockApiService.get).toHaveBeenCalledWith('estimates/template', 'default')
  })

  it('should render document preview with correct formatting', async () => {
    const mockData = {
      success: true,
      data: { id: 1, title: 'Test Document' }
    }

    mockApiService.get.mockResolvedValue(mockData)

    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    await wrapper.vm.$nextTick()

    // Check that canvas element exists
    expect(wrapper.find('canvas.document-canvas').exists()).toBe(true)
  })

  it('should handle print and download actions', async () => {
    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    // Test print action
    await wrapper.vm.handlePrint()
    expect(window.open).toHaveBeenCalled()

    // Test download actions
    await wrapper.vm.handleDownload('png')
    expect(wrapper.emitted('action-triggered')).toBeTruthy()

    await wrapper.vm.handleDownload('pdf')
    expect(mockApiService.request).toHaveBeenCalled()
  })

  it('should support different paper sizes (A4, A5, Letter)', async () => {
    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1,
        paperSize: 'A4'
      }
    })

    // Test A4 dimensions
    expect(wrapper.vm.canvasWidth).toBe(794)
    expect(wrapper.vm.canvasHeight).toBe(1123)

    // Change to A5
    await wrapper.setProps({ paperSize: 'A5' })
    await wrapper.vm.setupCanvasDimensions()
    expect(wrapper.vm.canvasWidth).toBe(559)
    expect(wrapper.vm.canvasHeight).toBe(794)

    // Change to Letter
    await wrapper.setProps({ paperSize: 'Letter' })
    await wrapper.vm.setupCanvasDimensions()
    expect(wrapper.vm.canvasWidth).toBe(816)
    expect(wrapper.vm.canvasHeight).toBe(1056)
  })

  it('should handle API errors gracefully with notify service', async () => {
    const mockError = new Error('API Error')
    mockApiService.get.mockRejectedValue(mockError)

    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    await wrapper.vm.$nextTick()

    expect(mockNotifyService.error).toHaveBeenCalled()
    expect(wrapper.vm.hasError).toBe(true)
  })

  it('should provide accessible navigation and actions', async () => {
    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    // Check ARIA labels exist
    const toolbar = wrapper.find('[role="group"][aria-label="Document actions"]')
    expect(toolbar.exists()).toBe(true)

    // Check keyboard accessibility
    const buttons = wrapper.findAll('button')
    buttons.forEach(button => {
      expect(button.attributes('type')).toBeDefined()
    })
  })

  it('should support zoom and view mode controls', async () => {
    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    // Test zoom controls
    expect(wrapper.vm.zoomLevel).toBe(100)

    await wrapper.vm.adjustZoom(10)
    expect(wrapper.vm.zoomLevel).toBe(110)

    await wrapper.vm.adjustZoom(-20)
    expect(wrapper.vm.zoomLevel).toBe(90)

    await wrapper.vm.resetZoom()
    expect(wrapper.vm.zoomLevel).toBe(100)

    // Test view mode switching
    expect(wrapper.vm.viewMode).toBe('preview')

    await wrapper.vm.$nextTick()
    wrapper.vm.viewMode = 'code'
    await wrapper.vm.$nextTick()
    
    expect(wrapper.vm.viewMode).toBe('code')
  })

  it('should handle document actions correctly', async () => {
    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    // Test edit action
    await wrapper.vm.handleEditAction()
    expect(wrapper.emitted('action-triggered')).toBeTruthy()
    expect(wrapper.emitted('action-triggered')?.[0]).toEqual([{ 
      action: 'edit', 
      data: { documentId: 1 } 
    }])

    // Test copy action  
    await wrapper.vm.handleCopy()
    // Should emit action-triggered event
  })

  it('should handle loading states properly', async () => {
    const wrapper = mount(DocumentView, {
      props: {
        resource: 'estimates',
        documentId: 1
      }
    })

    // Should start in loading state
    expect(wrapper.vm.isLoading).toBe(true)

    // Check loading UI
    expect(wrapper.find('.loading-state').exists()).toBe(true)
    expect(wrapper.find('.spinner-border').exists()).toBe(true)
  })
})