<template>
  <div class="container mt-5">
    <div class="row">
      <div class="col-md-8 offset-md-2">
        <div class="text-center">
          <h1 class="display-4 mb-4">BOS Project</h1>
          <p class="lead mb-4">
            API Service Layer and Authentication Service Implementation
          </p>
          
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Services Implemented</h5>
              <ul class="list-unstyled">
                <li class="mb-2">
                  <i class="bi bi-check-circle-fill text-success me-2"></i>
                  <strong>API Service Layer</strong> - Centralized HTTP request handling
                </li>
                <li class="mb-2">
                  <i class="bi bi-check-circle-fill text-success me-2"></i>
                  <strong>Authentication Service</strong> - User authentication and authorization
                </li>
                <li class="mb-2">
                  <i class="bi bi-check-circle-fill text-success me-2"></i>
                  <strong>Request/Response Interceptors</strong> - Automatic token management
                </li>
                <li class="mb-2">
                  <i class="bi bi-check-circle-fill text-success me-2"></i>
                  <strong>CRUD Operations</strong> - Standardized API methods
                </li>
                <li class="mb-2">
                  <i class="bi bi-check-circle-fill text-success me-2"></i>
                  <strong>Route Protection</strong> - Authentication middleware
                </li>
              </ul>
              
              <div class="mt-4">
                <NuxtLink to="/demo" class="btn btn-primary btn-lg">
                  View Demo
                </NuxtLink>
              </div>
            </div>
          </div>
          
          <div class="row mt-5">
            <div class="col-md-6">
              <div class="card h-100">
                <div class="card-body">
                  <h5 class="card-title">API Service Features</h5>
                  <ul class="list-unstyled text-start">
                    <li>• Generic request method</li>
                    <li>• CRUD operations (fetch, get, create, update, delete)</li>
                    <li>• Request/response interceptors</li>
                    <li>• Standardized error handling</li>
                    <li>• Automatic authentication headers</li>
                    <li>• Singleton pattern</li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card h-100">
                <div class="card-body">
                  <h5 class="card-title">Auth Service Features</h5>
                  <ul class="list-unstyled text-start">
                    <li>• Login/logout functionality</li>
                    <li>• Token refresh mechanism</li>
                    <li>• Reactive authentication state</li>
                    <li>• Secure token storage</li>
                    <li>• Route protection middleware</li>
                    <li>• Automatic initialization</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  title: 'BOS Project - Home',
  layout: 'default'
})
</script>