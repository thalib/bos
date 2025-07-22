<template>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card">
          <div class="card-header text-center">
            <h4><i class="bi bi-person-circle"></i> Login</h4>
          </div>
          <div class="card-body">
            <form @submit.prevent="handleLogin" novalidate>
              <!-- Username Field -->
              <div class="mb-3">
                <label for="username" class="form-label">Username, Email, or WhatsApp</label>
                <input
                  id="username"
                  v-model="form.username"
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.username }"
                  data-testid="username-field"
                  placeholder="Enter your username, email, or phone number"
                  required
                />
                <div v-if="errors.username" class="invalid-feedback">
                  {{ errors.username }}
                </div>
              </div>

              <!-- Password Field -->
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input
                  id="password"
                  v-model="form.password"
                  type="password"
                  class="form-control"
                  :class="{ 'is-invalid': errors.password }"
                  data-testid="password-field"
                  placeholder="Enter your password"
                  required
                />
                <div v-if="errors.password" class="invalid-feedback">
                  {{ errors.password }}
                </div>
              </div>

              <!-- Remember Me -->
              <div class="mb-3 form-check">
                <input
                  id="remember"
                  v-model="form.rememberMe"
                  type="checkbox"
                  class="form-check-input"
                  data-testid="remember-field"
                />
                <label for="remember" class="form-check-label">
                  Remember me
                </label>
              </div>

              <!-- Submit Button -->
              <div class="d-grid">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="isLoading"
                  data-testid="login-button"
                >
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  {{ isLoading ? 'Signing in...' : 'Sign In' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useAuthService } from '../utils/auth'
import { useNotifyService } from '../utils/notify'

// Services
const authService = useAuthService()
const notifyService = useNotifyService()

// Router
const route = useRoute()
const router = useRouter()

// Form state
const form = reactive({
  username: '',
  password: '',
  rememberMe: false
})

// Validation errors
const errors = reactive({
  username: '',
  password: ''
})

// Loading state
const isLoading = ref(false)

// Validation functions
const validateForm = (): boolean => {
  // Clear previous errors
  errors.username = ''
  errors.password = ''

  let isValid = true

  // Validate username field
  if (!form.username.trim()) {
    errors.username = 'Username, email, or phone number is required'
    isValid = false
  } else if (form.username.trim().length < 2) {
    errors.username = 'Username must be at least 2 characters long'
    isValid = false
  }

  // Validate password field
  if (!form.password) {
    errors.password = 'Password is required'
    isValid = false
  } else if (form.password.length < 6) {
    errors.password = 'Password must be at least 6 characters long'
    isValid = false
  }

  return isValid
}

// Check if username is an email
const isEmail = (username: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(username)
}

// Handle form submission
const handleLogin = async (): Promise<void> => {
  // Always validate form first
  if (!validateForm()) {
    return
  }

  isLoading.value = true

  try {
    // Prepare credentials - map username to email field for API
    const credentials = {
      email: form.username, // API expects email field, but we accept various formats
      password: form.password
    }

    const response = await authService.login(credentials)

    if (response.success) {
      // Determine redirect path
      const redirectPath = (route.query.redirect as string) || '/dashboard'
      
      // Navigate to the appropriate page
      await navigateTo(redirectPath)
    }
  } catch (error) {
    // Error handling is done by the auth service
    console.error('Login failed:', error)
  } finally {
    isLoading.value = false
  }
}

// Redirect if already authenticated
onMounted(() => {
  // Wait for auth service to be initialized before checking authentication status
  if (authService.isInitialized.value && authService.isAuthenticated.value) {
    const redirectPath = (route.query.redirect as string) || '/dashboard'
    navigateTo(redirectPath)
  }
})
</script>