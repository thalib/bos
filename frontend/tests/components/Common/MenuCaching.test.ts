import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Sidebar from '../../../app/components/Common/Sidebar.vue'

// Mock dependencies
vi.mock('../../../app/utils/auth', () => ({
  useAuthService: () => ({
    getCurrentUser: () => ({ name: 'Test User' })
  })
}))

vi.mock('../../../app/utils/notify', () => ({
  useNotifyService: () => ({
    error: vi.fn(),
    info: vi.fn()
  })
}))

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
}
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

describe('Menu Caching', () => {
  const mockApiService = {
    request: vi.fn()
  }

  beforeEach(() => {
    vi.clearAllMocks()
    localStorageMock.getItem.mockReturnValue(null)
  })

  it('should cache menu items with 1-day expiry', async () => {
    // Mock API response in the correct format
    const mockMenuData = [
      { type: 'item', id: 1, name: 'Home', path: '/', icon: 'bi-house', order: 1 }
    ]
    const mockResponse = {
      data: mockMenuData,
      message: 'Menu items retrieved successfully'
    }

    mockApiService.request.mockResolvedValue(mockResponse)

    // Mock useApiService
    vi.doMock('../../../app/utils/api', () => ({
      useApiService: () => mockApiService
    }))

    const wrapper = mount(Sidebar)
    await wrapper.vm.$nextTick()

    // Wait for component to mount and fetch data
    await new Promise(resolve => setTimeout(resolve, 100))

    // Check that localStorage was called to store the cached data
    expect(localStorageMock.setItem).toHaveBeenCalledWith(
      'menu_cache',
      expect.stringContaining('menuItems')
    )
  })

  it('should use cached menu items if not expired', async () => {
    // Mock cached data that is not expired (less than 1 day old)
    const cachedData = {
      menuItems: [
        { type: 'item', id: 1, name: 'Cached Home', path: '/', icon: 'bi-house', order: 1 }
      ],
      timestamp: Date.now() - (1000 * 60 * 60 * 12) // 12 hours ago
    }
    localStorageMock.getItem.mockReturnValue(JSON.stringify(cachedData))

    const wrapper = mount(Sidebar)
    await wrapper.vm.$nextTick()

    // Wait for component to process cached data
    await new Promise(resolve => setTimeout(resolve, 100))

    // API should not be called since we have valid cached data
    expect(mockApiService.request).not.toHaveBeenCalled()
  })

  it('should fetch new data if cache is expired', async () => {
    // Mock expired cached data (more than 1 day old)
    const expiredData = {
      menuItems: [
        { type: 'item', id: 1, name: 'Expired Home', path: '/', icon: 'bi-house', order: 1 }
      ],
      timestamp: Date.now() - (1000 * 60 * 60 * 25) // 25 hours ago
    }
    localStorageMock.getItem.mockReturnValue(JSON.stringify(expiredData))

    const mockMenuData = [
      { type: 'item', id: 1, name: 'Fresh Home', path: '/', icon: 'bi-house', order: 1 }
    ]
    const mockResponse = {
      data: mockMenuData,
      message: 'Menu items retrieved successfully'
    }
    mockApiService.request.mockResolvedValue(mockResponse)

    const wrapper = mount(Sidebar)
    await wrapper.vm.$nextTick()

    // Wait for component to fetch fresh data
    await new Promise(resolve => setTimeout(resolve, 100))

    // API should be called since cache is expired
    expect(mockApiService.request).toHaveBeenCalledWith('/api/v1/app/menu', {
      method: 'GET'
    })
  })
})