<template>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div v-if="showLoginForm" class="card">
          <div class="card-header text-center">
            <h4><i class="bi bi-person-circle"></i> Login</h4>
          </div>
          <div class="card-body">
            <form @submit.prevent="handleLogin" novalidate>
              <!-- Username Field -->
              <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input id="username" v-model="form.username" type="text" class="form-control"
                  :class="{ 'is-invalid': errors.username }" data-testid="username-field" required />
                <div v-if="errors.username" class="invalid-feedback">
                  {{ errors.username }}
                </div>
              </div>

              <!-- Password Field -->
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input id="password" v-model="form.password" type="password" class="form-control"
                  :class="{ 'is-invalid': errors.password }" data-testid="password-field" required />
                <div v-if="errors.password" class="invalid-feedback">
                  {{ errors.password }}
                </div>
              </div>

              <!-- Remember Me -->
              <div class="mb-3 form-check">
                <input id="remember" v-model="form.rememberMe" type="checkbox" class="form-check-input"
                  data-testid="remember-field" />
                <label for="remember" class="form-check-label">
                  Remember me
                </label>
              </div>

              <!-- Submit Button -->
              <div class="d-grid">
                <button type="submit" class="btn btn-primary" :disabled="isLoading" data-testid="login-button">
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status"
                    aria-hidden="true"></span>
                  {{ isLoading ? 'Signing in...' : 'Sign In' }}
                </button>
              </div>
            </form>
          </div>
        </div>
        <div v-else>          
          <CommonLoading />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">


interface LoginForm {
  username: string
  password: string
  rememberMe: boolean
}

interface LoginErrors {
  username: string
  password: string
}

const authService = useAuthService()
const notifyService = useNotifyService()
const route = useRoute()

const form = reactive<LoginForm>({
  username: '',
  password: '',
  rememberMe: false
})

const errors = reactive<LoginErrors>({
  username: '',
  password: ''
})

const showLoginForm = ref(false)
const isLoading = ref(false)

const validateForm = (): boolean => {
  errors.username = ''
  errors.password = ''
  let isValid = true
  if (!form.username.trim()) {
    errors.username = 'Username is required'
    isValid = false
  } else if (form.username.trim().length < 2) {
    errors.username = 'Username must be at least 2 characters long'
    isValid = false
  }
  if (!form.password) {
    errors.password = 'Password is required'
    isValid = false
  } else if (form.password.length < 6) {
    errors.password = 'Password must be at least 6 characters long'
    isValid = false
  }
  return isValid
}



const handleLogin = async (): Promise<void> => {
  if (!validateForm()) return
  isLoading.value = true
  try {
    const credentials = {
      email: form.username,
      password: form.password
    }
    const response = await authService.login(credentials)
    if (response.success) {
      const redirectPath = (route.query.redirect as string) || '/dashboard'
      await navigateTo(redirectPath)
    }
  } catch (error) {
    notifyService.error('Login failed. Please check your credentials and try again.')
    // Optionally log error
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  if (authService.isInitialized.value && authService.isAuthenticated.value) {
    const redirectPath = (route.query.redirect as string) || '/dashboard'
    navigateTo(redirectPath)
  } else {
    showLoginForm.value = true
  }
})
</script>
