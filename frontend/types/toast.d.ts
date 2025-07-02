import type { ToastType } from '~/utils/toast'

declare module '#app' {
  interface NuxtApp {
    $toast: {
      toast: (type: ToastType, message: string) => void
      success: (message: string) => void
      error: (message: string) => void
      info: (message: string) => void
      warning: (message: string) => void
      ToastType: typeof ToastType
    }
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $toast: {
      toast: (type: ToastType, message: string) => void
      success: (message: string) => void
      error: (message: string) => void
      info: (message: string) => void
      warning: (message: string) => void
      ToastType: typeof ToastType
    }
  }
}

export {}
