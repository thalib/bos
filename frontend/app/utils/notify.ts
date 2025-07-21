import toastr from 'toastr'
import 'toastr/build/toastr.min.css'

export interface NotificationOptions {
  type: 'success' | 'error' | 'warning' | 'info'
  message: string
  title?: string
  duration?: number // Duration in milliseconds, 0 for pinned notifications
}

/**
 * Notify Service implementation for BOS project
 * Provides centralized notification management using toastr.js
 */
class NotifyService {
  constructor() {
    // Set default toastr options
    this.configureToastr()
  }

  /**
   * Configure toastr default settings
   */
  private configureToastr(): void {
    toastr.options = {
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
    }
  }

  /**
   * Display a notification
   */
  notify({ type, message, title = '', duration = 5000 }: NotificationOptions): void {
    // Apply specific options for this notification
    const originalOptions = { ...toastr.options }
    
    // Set duration - error notifications are pinned by default
    const finalDuration = type === 'error' ? 0 : duration
    
    toastr.options = {
      ...toastr.options,
      timeOut: finalDuration > 0 ? finalDuration : 0,
      extendedTimeOut: finalDuration > 0 ? 1000 : 0
    }

    try {
      switch (type) {
        case 'success':
          toastr.success(message, title)
          break
        case 'error':
          console.error(`[Notify] ${title ? `${title}: ` : ''}${message}`)
          toastr.error(message, title)
          break
        case 'warning':
          console.warn(`[Notify] ${title ? `${title}: ` : ''}${message}`)
          toastr.warning(message, title)
          break
        case 'info':
          toastr.info(message, title)
          break
        default:
          console.warn('Unknown notification type:', type)
          toastr.info(message, title)
      }
    } catch (error) {
      // Log error but don't re-throw to avoid breaking the application
      console.error('[Notify] Error displaying notification:', error)
    } finally {
      // Restore original options for next notification
      toastr.options = originalOptions
    }
  }

  /**
   * Clear all notifications
   */
  clear(): void {
    toastr.clear()
  }

  /**
   * Remove a specific notification
   */
  remove(notification?: any): void {
    if (notification) {
      toastr.clear(notification)
    } else {
      toastr.clear()
    }
  }

  /**
   * Convenience methods for each notification type
   */
  success(message: string, title?: string, duration?: number): void {
    this.notify({ type: 'success', message, title, duration })
  }

  error(message: string, title?: string, duration?: number): void {
    this.notify({ type: 'error', message, title, duration })
  }

  warning(message: string, title?: string, duration?: number): void {
    this.notify({ type: 'warning', message, title, duration })
  }

  info(message: string, title?: string, duration?: number): void {
    this.notify({ type: 'info', message, title, duration })
  }
}

// Create singleton instance
let notifyServiceInstance: NotifyService | null = null

/**
 * Composable to access the Notify service
 */
export function useNotifyService(): NotifyService {
  if (!notifyServiceInstance) {
    notifyServiceInstance = new NotifyService()
  }
  return notifyServiceInstance
}

// Reset function for testing
;(useNotifyService as any).reset = () => {
  notifyServiceInstance = null
}

// Export for direct use if needed
export { NotifyService }