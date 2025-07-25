import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import MasterDetail from '@/components/Resource/MasterDetail.vue'
import { useApiService } from '@/utils/api'
import { useNotifyService } from '@/utils/notify'

// Mock the services
vi.mock('@/utils/api')
vi.mock('@/utils/notify')

// Mock child components
vi.mock('@/components/Resource/List.vue', () => ({
  default: {
    name: 'List',
    template: '<div data-testid="list-component">Mock List Component</div>',
    props: ['resource'],
    emits: ['item-selected']
  }
}))

vi.mock('@/components/Resource/Form.vue', () => ({
  default: {
    name: 'Form', 
    template: '<div data-testid="form-component">Mock Form Component</div>',
    props: ['resource', 'resource-id'],
    emits: ['form-saved']
  }
}))

vi.mock('@/components/Resource/DocumentView.vue', () => ({
  default: {
    name: 'DocumentView',
    template: '<div data-testid="document-view-component">Mock DocumentView Component</div>',
    props: ['resource', 'document-id'],
    emits: ['action-triggered']
  }
}))

describe('MasterDetail Component', () => {
  let mockApiService: any
  let mockNotifyService: any

  beforeEach(() => {
    mockApiService = {
      get: vi.fn(),
      fetch: vi.fn(),
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

  it('should render responsive master-detail layout', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'products',
        mode: 'form'
      }
    })

    // Check that the main container exists
    expect(wrapper.find('.master-detail-container').exists()).toBe(true)
    
    // Check responsive grid layout
    expect(wrapper.find('.row.g-0.h-100').exists()).toBe(true)
    
    // Check master panel
    const masterPanel = wrapper.find('.master-panel')
    expect(masterPanel.exists()).toBe(true)
    
    // Check that List component is rendered in master panel
    expect(wrapper.findComponent({ name: 'List' }).exists()).toBe(true)
  })

  it('should coordinate List component in master panel', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'products',
        mode: 'form'
      }
    })

    const listComponent = wrapper.findComponent({ name: 'List' })
    expect(listComponent.exists()).toBe(true)
    expect(listComponent.props('resource')).toBe('products')
  })

  it('should show Form component in detail panel for form mode', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'products', 
        mode: 'form',
        initialSelection: 1
      }
    })

    // Simulate item selection
    await wrapper.vm.handleItemSelection({ id: 1, name: 'Test Product' })
    await wrapper.vm.$nextTick()

    const formComponent = wrapper.findComponent({ name: 'Form' })
    expect(formComponent.exists()).toBe(true)
    expect(formComponent.props('resource')).toBe('products')
  })

  it('should show DocumentView component in detail panel for document mode', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'estimates',
        mode: 'document',
        initialSelection: 1
      }
    })

    // Simulate item selection
    await wrapper.vm.handleItemSelection({ id: 1, name: 'Test Estimate' })
    await wrapper.vm.$nextTick()

    const documentComponent = wrapper.findComponent({ name: 'DocumentView' })
    expect(documentComponent.exists()).toBe(true)
    expect(documentComponent.props('resource')).toBe('estimates')
  })

  it('should handle item selection and detail panel updates', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'products',
        mode: 'form'
      }
    })

    const testItem = { id: 1, name: 'Test Product' }
    
    // Simulate item selection from List component
    const listComponent = wrapper.findComponent({ name: 'List' })
    await listComponent.vm.$emit('item-selected', testItem)

    // Check that selection-changed event is emitted
    expect(wrapper.emitted('selection-changed')).toBeTruthy()
    expect(wrapper.emitted('selection-changed')?.[0]).toEqual([{ selectedItem: testItem }])
  })

  it('should adapt layout for mobile and desktop screens', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'products',
        mode: 'form',
        initialSelection: 1
      }
    })

    // Set mobile viewport
    Object.defineProperty(window, 'innerWidth', { value: 576, writable: true })
    await wrapper.vm.updateViewport()

    expect(wrapper.vm.isMobile).toBe(true)

    // Set desktop viewport  
    Object.defineProperty(window, 'innerWidth', { value: 1200, writable: true })
    await wrapper.vm.updateViewport()

    expect(wrapper.vm.isMobile).toBe(false)
  })

  it('should manage loading states across child components', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'products',
        mode: 'form'
      }
    })

    // Component should handle loading states
    expect(wrapper.vm.isLoading).toBeDefined()
  })

  it('should handle component mode switching (form/document)', async () => {
    const wrapper = mount(MasterDetail, {
      props: {
        resource: 'products',
        mode: 'form'
      }
    })

    expect(wrapper.props('mode')).toBe('form')

    // Update mode prop
    await wrapper.setProps({ mode: 'document' })
    expect(wrapper.props('mode')).toBe('document')
  })
})