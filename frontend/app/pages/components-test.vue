<template>
  <div>
    <!-- Navbar Component -->
    <CommonNavbar :title="currentPageTitle" />
    
    <!-- Sidebar Component -->
    <CommonSidebar @logout="handleLogout" />
    
    <!-- Main Content -->
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="card mt-3">
            <div class="card-header">
              <h3>Component Test Page</h3>
            </div>
            <div class="card-body">
              <p>This page demonstrates the new Navbar and Sidebar components.</p>
              
              <div class="alert alert-info">
                <h5>Features Demonstrated:</h5>
                <ul class="mb-0">
                  <li><strong>Navbar:</strong> Authentication awareness, menu toggle, page title, user dropdown</li>
                  <li><strong>Sidebar:</strong> API-driven menu, responsive navigation, theme toggle, logout</li>
                </ul>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <h5>Navbar Features:</h5>
                  <ul>
                    <li>✅ Menu toggle button (bi-list icon)</li>
                    <li>✅ Dynamic page title: "{{ currentPageTitle }}"</li>
                    <li>✅ User dropdown (bi-person-circle icon)</li>
                    <li>✅ Bootstrap 5.3 styling</li>
                    <li>✅ Authentication awareness</li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <h5>Sidebar Features:</h5>
                  <ul>
                    <li>✅ User info display</li>
                    <li>✅ API menu loading</li>
                    <li>✅ Loading/error states</li>
                    <li>✅ Bootstrap offcanvas</li>
                    <li>✅ Theme toggle button</li>
                    <li>✅ Logout functionality</li>
                  </ul>
                </div>
              </div>
              
              <div class="mt-4">
                <button 
                  class="btn btn-primary" 
                  type="button" 
                  data-bs-toggle="offcanvas" 
                  data-bs-target="#sidebar"
                >
                  <i class="bi bi-list me-2"></i>
                  Toggle Sidebar
                </button>
                
                <button 
                  class="btn btn-outline-secondary ms-2"
                  @click="changePage"
                >
                  <i class="bi bi-arrow-repeat me-2"></i>
                  Change Page Title
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Set page title
useHead({
  title: 'Component Test - BOS'
})

// Define auth middleware
definePageMeta({
  middleware: 'auth'
})

// Reactive data
const currentPageTitle = ref('Component Test')

const pageOptions = [
  'Component Test',
  'Dashboard',
  'User Profile', 
  'Settings',
  'Reports'
]

// Methods
const changePage = () => {
  const currentIndex = pageOptions.indexOf(currentPageTitle.value)
  const nextIndex = (currentIndex + 1) % pageOptions.length
  currentPageTitle.value = pageOptions[nextIndex]
}

const handleLogout = () => {
  // Logout handled by sidebar component, this is just for demo
  console.log('Logout event received from sidebar')
}

// Services for components
const authService = useAuthService()

onMounted(() => {
  // Ensure user is authenticated for demo
  if (!authService.isAuthenticated.value) {
    console.warn('User not authenticated - components may not display properly')
  }
})
</script>