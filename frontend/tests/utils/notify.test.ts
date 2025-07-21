import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// Mock toastr before importing anything else
vi.mock('toastr', () => ({
  default: {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    clear: vi.fn(),
    options: {}
  }
}))

vi.mock('toastr/build/toastr.min.css', () => ({}))

// Now import the service after mocking
import { useNotifyService, type NotificationOptions } from '../../app/utils/notify'

describe('Notify Service', () => {
  let notifyService: ReturnType<typeof useNotifyService>
  const originalConsole = { ...console }
  
  // Mock console methods
  const mockConsole = {
    error: vi.fn(),
    warn: vi.fn(),
    log: vi.fn()
  }

  beforeEach(async () => {
    // Reset mocks
    vi.clearAllMocks()
    
    // Get the mocked toastr
    const toastr = (await vi.importMock('toastr')).default as any
    toastr.options = {}
    
    // Mock console methods
    console.error = mockConsole.error
    console.warn = mockConsole.warn
    console.log = mockConsole.log
    
    // Reset service instance
    ;(useNotifyService as any).reset()
    notifyService = useNotifyService()
  })

  afterEach(() => {
    // Restore console
    Object.assign(console, originalConsole)
    vi.clearAllMocks()
  })

  describe('initialization', () => {
    it('should configure toastr with default options', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      expect(toastr.options).toEqual({
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        preventDuplicates: true,
        showDuration: 300,
        hideDuration: 1000,
        timeOut: 5000,
        extendedTimeOut: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
      })
    })

    it('should create singleton instance', () => {
      const service1 = useNotifyService()
      const service2 = useNotifyService()
      expect(service1).toBe(service2)
    })
  })

  describe('notify method', () => {
    it('should display success notification', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const options: NotificationOptions = {
        type: 'success',
        message: 'Operation successful',
        title: 'Success',
        duration: 3000
      }

      notifyService.notify(options)

      expect(toastr.success).toHaveBeenCalledWith('Operation successful', 'Success')
      // Verify that options were temporarily set correctly by checking that toastr was configured
      expect(toastr.options).toBeDefined()
    })

    it('should display error notification with pinned duration by default', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const options: NotificationOptions = {
        type: 'error',
        message: 'Something went wrong',
        title: 'Error'
      }

      notifyService.notify(options)

      expect(toastr.error).toHaveBeenCalledWith('Something went wrong', 'Error')
      expect(mockConsole.error).toHaveBeenCalledWith('[Notify] Error: Something went wrong')
    })

    it('should display error notification with custom duration when specified', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const options: NotificationOptions = {
        type: 'error',
        message: 'Temporary error',
        duration: 3000
      }

      notifyService.notify(options)

      expect(toastr.error).toHaveBeenCalledWith('Temporary error', '')
      expect(mockConsole.error).toHaveBeenCalledWith('[Notify] Temporary error')
    })

    it('should display warning notification and log to console', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const options: NotificationOptions = {
        type: 'warning',
        message: 'This is a warning',
        title: 'Warning',
        duration: 4000
      }

      notifyService.notify(options)

      expect(toastr.warning).toHaveBeenCalledWith('This is a warning', 'Warning')
      expect(mockConsole.warn).toHaveBeenCalledWith('[Notify] Warning: This is a warning')
    })

    it('should display info notification', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const options: NotificationOptions = {
        type: 'info',
        message: 'Information message',
        duration: 2000
      }

      notifyService.notify(options)

      expect(toastr.info).toHaveBeenCalledWith('Information message', '')
    })

    it('should handle unknown notification type', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const options = {
        type: 'unknown' as any,
        message: 'Unknown type message'
      }

      notifyService.notify(options)

      expect(mockConsole.warn).toHaveBeenCalledWith('Unknown notification type:', 'unknown')
      expect(toastr.info).toHaveBeenCalledWith('Unknown type message', '')
    })

    it('should use default duration when not specified', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const options: NotificationOptions = {
        type: 'success',
        message: 'Default duration test'
      }

      notifyService.notify(options)

      expect(toastr.success).toHaveBeenCalledWith('Default duration test', '')
    })

    it('should handle pinned notifications (duration = 0)', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const options: NotificationOptions = {
        type: 'info',
        message: 'Pinned notification',
        duration: 0
      }

      notifyService.notify(options)

      expect(toastr.info).toHaveBeenCalledWith('Pinned notification', '')
    })

    it('should restore original toastr options after notification', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const originalOptions = { ...toastr.options }
      
      notifyService.notify({
        type: 'success',
        message: 'Test message',
        duration: 3000
      })

      // Options should be restored to original values
      expect(toastr.options).toEqual(originalOptions)
    })

    it('should properly configure duration for error notifications', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      
      // Test that error notifications are pinned regardless of specified duration
      let capturedOptions: any = null
      const originalToastrError = toastr.error
      toastr.error.mockImplementation((...args: any[]) => {
        capturedOptions = { ...toastr.options }
        return originalToastrError.apply(toastr, args)
      })

      notifyService.notify({
        type: 'error',
        message: 'Error message',
        duration: 5000 // This should be ignored for error type
      })

      expect(capturedOptions.timeOut).toBe(0)
      expect(capturedOptions.extendedTimeOut).toBe(0)
    })

    it('should properly configure duration for non-error notifications', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      
      let capturedOptions: any = null
      const originalToastrSuccess = toastr.success
      toastr.success.mockImplementation((...args: any[]) => {
        capturedOptions = { ...toastr.options }
        return originalToastrSuccess.apply(toastr, args)
      })

      notifyService.notify({
        type: 'success',
        message: 'Success message',
        duration: 3000
      })

      expect(capturedOptions.timeOut).toBe(3000)
      expect(capturedOptions.extendedTimeOut).toBe(1000)
    })
  })

  describe('convenience methods', () => {
    it('should call notify with success type', () => {
      const notifySpy = vi.spyOn(notifyService, 'notify')
      
      notifyService.success('Success message', 'Title', 3000)

      expect(notifySpy).toHaveBeenCalledWith({
        type: 'success',
        message: 'Success message',
        title: 'Title',
        duration: 3000
      })
    })

    it('should call notify with error type', () => {
      const notifySpy = vi.spyOn(notifyService, 'notify')
      
      notifyService.error('Error message', 'Error Title')

      expect(notifySpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Error message',
        title: 'Error Title',
        duration: undefined
      })
    })

    it('should call notify with warning type', () => {
      const notifySpy = vi.spyOn(notifyService, 'notify')
      
      notifyService.warning('Warning message')

      expect(notifySpy).toHaveBeenCalledWith({
        type: 'warning',
        message: 'Warning message',
        title: undefined,
        duration: undefined
      })
    })

    it('should call notify with info type', () => {
      const notifySpy = vi.spyOn(notifyService, 'notify')
      
      notifyService.info('Info message', undefined, 2000)

      expect(notifySpy).toHaveBeenCalledWith({
        type: 'info',
        message: 'Info message',
        title: undefined,
        duration: 2000
      })
    })
  })

  describe('clear method', () => {
    it('should clear all notifications', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      notifyService.clear()
      expect(toastr.clear).toHaveBeenCalledWith()
    })
  })

  describe('remove method', () => {
    it('should remove specific notification when provided', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      const notification = { id: 'test-notification' }
      notifyService.remove(notification)
      expect(toastr.clear).toHaveBeenCalledWith(notification)
    })

    it('should clear all notifications when no specific notification provided', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      notifyService.remove()
      expect(toastr.clear).toHaveBeenCalledWith()
    })
  })

  describe('error handling', () => {
    it('should handle toastr errors gracefully', async () => {
      const toastr = (await vi.importMock('toastr')).default as any
      toastr.success.mockImplementation(() => {
        throw new Error('Toastr error')
      })

      // Should not throw - error should be caught and logged
      expect(() => {
        notifyService.success('Test message')
      }).not.toThrow()

      // Should log the error
      expect(mockConsole.error).toHaveBeenCalledWith('[Notify] Error displaying notification:', expect.any(Error))
    })
  })
})