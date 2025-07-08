/**
 * Toast Plugin
 * Automatically adds the Toast component to the app for error notifications
 * and provides global toast utility
 */
import { defineNuxtPlugin } from '#app'
import Toast from '~/components/Toast.vue'
import toastUtil from '~/utils/toast'

export default defineNuxtPlugin((nuxtApp) => {
  // Add Toast component to the app
  nuxtApp.vueApp.component('Toast', Toast)
  
  // Global error handler to catch and display unhandled errors
  nuxtApp.vueApp.config.errorHandler = (error, instance, info) => {
    // Use the new toast utility for errors
    const errorMessage = (error as Error)?.message || 'An unexpected error occurred'
    toastUtil.error(errorMessage)
    
    // Log the error to console
    console.error('Global error:', error)
    console.info('Error info:', info)
    
    // Forward to original error handler for debugging purposes
    if (typeof console !== 'undefined') {
      console.error(error)
    }
  }
  
  // Make toast utility globally available
  return {
    provide: {
      toast: toastUtil
    }
  }
})
