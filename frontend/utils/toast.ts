import Toastify from 'toastify-js'
import 'toastify-js/src/toastify.css'

// Toast types
export enum ToastType {
  SUCCESS = 'success',
  ERROR = 'error',
  INFO = 'info',
  WARNING = 'warning'
}

// Toast configuration with different colors for each type
const getToastConfig = (type: ToastType, message: string) => {
  const baseConfig = {
    text: message,
    duration: 4000,
    close: true,
    gravity: 'top' as const,
    position: 'right' as const,
    stopOnFocus: true,
    style: {
      borderRadius: '8px',
      fontFamily: 'inherit',
      fontSize: '14px',
      fontWeight: '500',
      padding: '12px 16px',
      minWidth: '300px',
      boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)'
    }
  }
  // Different colors for each toast type
  const typeConfigs = {
    [ToastType.SUCCESS]: {
      ...baseConfig,
      style: {
        ...baseConfig.style,
        background: '#28a745',
        color: '#ffffff'
      }
    },
    [ToastType.ERROR]: {
      ...baseConfig,
      style: {
        ...baseConfig.style,
        background: '#dc3545',
        color: '#ffffff'
      },
      duration: 6000 // Errors stay longer
    },
    [ToastType.WARNING]: {
      ...baseConfig,
      style: {
        ...baseConfig.style,
        background: '#ffc107',
        color: '#212529'
      }
    },
    [ToastType.INFO]: {
      ...baseConfig,
      style: {
        ...baseConfig.style,
        background: '#17a2b8',
        color: '#ffffff'
      }
    }
  }

  return typeConfigs[type]
}

// Main toast function - simple and easy to use
export const toast = (type: ToastType, message: string) => {
  const config = getToastConfig(type, message)
  Toastify(config).showToast()
}

// Convenience methods for each type
export const toastSuccess = (message: string) => toast(ToastType.SUCCESS, message)
export const toastError = (message: string) => toast(ToastType.ERROR, message)
export const toastInfo = (message: string) => toast(ToastType.INFO, message)
export const toastWarning = (message: string) => toast(ToastType.WARNING, message)

// Default export for easy importing
export default {
  toast,
  success: toastSuccess,
  error: toastError,
  info: toastInfo,
  warning: toastWarning,
  ToastType
}
