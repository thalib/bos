<template>
  <div class="container mt-5">
    <div class="row">
      <div class="col-md-8 mx-auto">
        <div class="card">
          <div class="card-header">
            <h4 class="mb-0">Notification Service Demo</h4>
          </div>
          <div class="card-body">
            <p class="text-muted">
              Test the notification service integration with different notification types.
            </p>
            
            <div class="row g-3">
              <div class="col-md-6">
                <h5>Basic Notifications</h5>
                <div class="d-grid gap-2">
                  <button class="btn btn-success" @click="showSuccess">
                    Success Notification
                  </button>
                  <button class="btn btn-danger" @click="showError">
                    Error Notification
                  </button>
                  <button class="btn btn-warning" @click="showWarning">
                    Warning Notification
                  </button>
                  <button class="btn btn-info" @click="showInfo">
                    Info Notification
                  </button>
                </div>
              </div>
              
              <div class="col-md-6">
                <h5>Service Integration</h5>
                <div class="d-grid gap-2">
                  <button class="btn btn-primary" @click="testApiSuccess">
                    Test API Success
                  </button>
                  <button class="btn btn-danger" @click="testApiError">
                    Test API Error
                  </button>
                  <button class="btn btn-secondary" @click="testValidation">
                    Test Validation Warnings
                  </button>
                  <button class="btn btn-outline-secondary" @click="clearNotifications">
                    Clear All Notifications
                  </button>
                </div>
              </div>
            </div>
            
            <div class="mt-4">
              <h5>Notification Options</h5>
              <div class="row">
                <div class="col-md-6">
                  <label class="form-label">Duration (ms)</label>
                  <input 
                    v-model.number="duration" 
                    type="number" 
                    class="form-control" 
                    placeholder="5000"
                  >
                  <small class="text-muted">Set to 0 for pinned notifications</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Custom Title</label>
                  <input 
                    v-model="customTitle" 
                    type="text" 
                    class="form-control" 
                    placeholder="Optional title"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useNotifyService } from '~/utils/notify'
import { useApiService } from '~/utils/api'

// Services
const notify = useNotifyService()
const api = useApiService()

// Reactive data
const duration = ref(5000)
const customTitle = ref('')

// Basic notification methods
const showSuccess = () => {
  notify.success(
    'Operation completed successfully!',
    customTitle.value || 'Success',
    duration.value
  )
}

const showError = () => {
  notify.error(
    'Something went wrong. Please try again.',
    customTitle.value || 'Error',
    duration.value
  )
}

const showWarning = () => {
  notify.warning(
    'This action may have unexpected consequences.',
    customTitle.value || 'Warning',
    duration.value
  )
}

const showInfo = () => {
  notify.info(
    'Here is some useful information for you.',
    customTitle.value || 'Info',
    duration.value
  )
}

// Service integration tests
const testApiSuccess = async () => {
  try {
    // Mock a successful API response with notifications
    const mockResponse = {
      success: true,
      message: 'Data retrieved successfully',
      data: { items: [] },
      notifications: [
        { type: 'info' as const, message: 'Data loaded successfully' },
        { type: 'warning' as const, message: 'Some items were filtered' }
      ]
    }
    
    // Simulate the notification display
    if (mockResponse.notifications) {
      mockResponse.notifications.forEach(notification => {
        notify.notify({
          type: notification.type,
          message: notification.message
        })
      })
    }
    
    // Also show a success notification
    notify.success('API request completed successfully', 'API Success')
  } catch (error) {
    notify.error('API request failed', 'API Error')
  }
}

const testApiError = () => {
  // Simulate an API error
  notify.error(
    'The server returned an error: 404 Not Found',
    'API Error'
  )
}

const testValidation = () => {
  // Simulate validation warnings
  notify.warning('Invalid page number \'-1\', using page 1')
  notify.warning('Page size \'150\' exceeds maximum of 100, using maximum 100')
}

const clearNotifications = () => {
  notify.clear()
}

// Set page title
useHead({
  title: 'Notification Demo - BOS'
})
</script>

<style scoped>
.card {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>