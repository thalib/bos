<!-- Example page demonstrating API and Auth service usage -->
<template>
  <div class="container mt-5">
    <div class="row">
      <div class="col-md-8 offset-md-2">
        <h1 class="mb-4">API & Auth Service Demo</h1>
        
        <!-- Authentication Status -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Authentication Status</h5>
          </div>
          <div class="card-body">
            <p><strong>Authenticated:</strong> {{ isAuthenticated }}</p>
            <p><strong>Current User:</strong> {{ currentUser?.name || 'None' }}</p>
            <p><strong>User Email:</strong> {{ currentUser?.email || 'None' }}</p>
          </div>
        </div>

        <!-- Login Form -->
        <div v-if="!isAuthenticated" class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Login</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="handleLogin">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input 
                  v-model="loginForm.email"
                  type="email" 
                  class="form-control" 
                  id="email" 
                  required
                >
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input 
                  v-model="loginForm.password"
                  type="password" 
                  class="form-control" 
                  id="password" 
                  required
                >
              </div>
              <button 
                type="submit" 
                class="btn btn-primary"
                :disabled="isLoading"
              >
                {{ isLoading ? 'Logging in...' : 'Login' }}
              </button>
            </form>
          </div>
        </div>

        <!-- User Actions -->
        <div v-if="isAuthenticated" class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Actions</h5>
          </div>
          <div class="card-body">
            <button 
              @click="handleLogout"
              class="btn btn-secondary me-2"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Logging out...' : 'Logout' }}
            </button>
            <button 
              @click="handleRefreshToken"
              class="btn btn-info me-2"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Refreshing...' : 'Refresh Token' }}
            </button>
            <button 
              @click="handleCheckStatus"
              class="btn btn-warning"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Checking...' : 'Check Auth Status' }}
            </button>
          </div>
        </div>

        <!-- API Demo -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">API Service Demo</h5>
          </div>
          <div class="card-body">
            <button 
              @click="handleApiCall"
              class="btn btn-success me-2"
              :disabled="isLoading"
            >
              {{ isLoading ? 'Loading...' : 'Test API Call' }}
            </button>
            <div v-if="apiResult" class="mt-3">
              <h6>API Response:</h6>
              <pre class="bg-light p-3 rounded"><code>{{ JSON.stringify(apiResult, null, 2) }}</code></pre>
            </div>
          </div>
        </div>

        <!-- Messages -->
        <div v-if="message" class="alert" :class="messageClass">
          {{ message }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useApiService } from '~/app/utils/api'
import { useAuthService } from '~/app/utils/auth'

// Services
const apiService = useApiService()
const authService = useAuthService()

// Reactive state
const loginForm = reactive({
  email: 'demo@example.com',
  password: 'password123'
})

const isLoading = ref(false)
const message = ref('')
const messageClass = ref('alert-info')
const apiResult = ref(null)

// Computed properties from auth service
const isAuthenticated = authService.isAuthenticated
const currentUser = authService.currentUser

// Methods
const showMessage = (text: string, type: 'success' | 'error' | 'info' = 'info') => {
  message.value = text
  messageClass.value = `alert-${type === 'error' ? 'danger' : type}`
  setTimeout(() => {
    message.value = ''
  }, 5000)
}

const handleLogin = async () => {
  isLoading.value = true
  try {
    const response = await authService.login(loginForm)
    if (response.success) {
      showMessage('Login successful!', 'success')
    } else {
      showMessage(response.message || 'Login failed', 'error')
    }
  } catch (error) {
    showMessage('An error occurred during login', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleLogout = async () => {
  isLoading.value = true
  try {
    const response = await authService.logout()
    showMessage('Logged out successfully', 'success')
  } catch (error) {
    showMessage('An error occurred during logout', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleRefreshToken = async () => {
  isLoading.value = true
  try {
    const response = await authService.refreshToken()
    if (response.success) {
      showMessage('Token refreshed successfully!', 'success')
    } else {
      showMessage(response.message || 'Token refresh failed', 'error')
    }
  } catch (error) {
    showMessage('An error occurred during token refresh', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleCheckStatus = async () => {
  isLoading.value = true
  try {
    const response = await authService.checkAuthStatus()
    if (response.success) {
      showMessage(`Auth status: ${response.data?.authenticated ? 'Authenticated' : 'Not authenticated'}`, 'info')
    } else {
      showMessage(response.message || 'Status check failed', 'error')
    }
  } catch (error) {
    showMessage('An error occurred during status check', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleApiCall = async () => {
  isLoading.value = true
  try {
    // Example API call - this would fail since there's no backend
    // but it demonstrates the API service usage
    const response = await apiService.fetch('users', { page: 1, per_page: 10 })
    apiResult.value = response
    
    if (response.success) {
      showMessage('API call successful!', 'success')
    } else {
      showMessage('API call failed: ' + response.message, 'error')
    }
  } catch (error) {
    showMessage('An error occurred during API call', 'error')
    apiResult.value = { error: 'Network error or backend not available' }
  } finally {
    isLoading.value = false
  }
}

// Page meta
definePageMeta({
  title: 'API & Auth Service Demo',
  layout: 'default'
})
</script>