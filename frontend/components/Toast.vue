<!-- 
  Toast Component for API Error Notifications
  Displays toast notifications for errors, warnings, and success messages
-->
<template>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055; max-width: 350px;">
    <div 
      v-for="toast in toasts" 
      :key="toast.id"
      class="toast show"
      :class="getToastConfig(toast.type).border"
      role="alert"
      aria-live="assertive"
      aria-atomic="true"
      style="transition: all 0.3s ease; opacity: 1; border-left-width: 4px;"
    >
      <div class="toast-header" :class="getToastConfig(toast.type).header">
        <strong class="me-auto">{{ getToastConfig(toast.type).title }}</strong>
        <button 
          type="button" 
          class="btn-close" 
          aria-label="Close"
          @click="dismissToast(toast.id)"
        ></button>
      </div>
      <div class="toast-body">
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useToast } from '~/utils/errorHandling'

// Get toast functionality from our utility
const { activeToasts, dismissToast } = useToast()

// Reactive list of active toasts
const toasts = activeToasts

// Toast type mappings - consolidated into objects for better performance
const toastConfig = {
  success: { border: 'border-success', header: 'text-bg-success', title: 'Success' },
  error: { border: 'border-danger', header: 'text-bg-danger', title: 'Error' },
  warning: { border: 'border-warning', header: 'text-bg-warning', title: 'Warning' },
  info: { border: 'border-info', header: 'text-bg-info', title: 'Information' }
} as const

// Get toast configuration for a given type
const getToastConfig = (type: string) => toastConfig[type as keyof typeof toastConfig] || { border: '', header: '', title: 'Notification' }
</script>


