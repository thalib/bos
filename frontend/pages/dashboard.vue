<template>
  <div class="container py-4">
    <!-- Welcome Header -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="mb-2">Welcome back, {{ user?.name || 'User' }}!</h1>
            <p class="text-muted mb-0">Here's what's happening with your business today.</p>
          </div>
          <div class="text-end d-none d-md-block">
            <small class="text-muted">{{ formatDate(new Date()) }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <NuxtLink to="/estimates" class="card h-100 text-decoration-none quick-action-card">
          <div class="card-body text-center">
            <i class="bi bi-calculator fs-1 text-primary mb-2 d-block"></i>
            <h6 class="card-title mb-1">Estimates</h6>
            <small class="text-muted">Create & manage</small>
          </div>
        </NuxtLink>
      </div>
      
      <div class="col-6 col-md-3">
        <NuxtLink to="/doc" class="card h-100 text-decoration-none quick-action-card">
          <div class="card-body text-center">
            <i class="bi bi-file-text fs-1 text-success mb-2 d-block"></i>
            <h6 class="card-title mb-1">Documents</h6>
            <small class="text-muted">View & export</small>
          </div>
        </NuxtLink>
      </div>
      
      <div class="col-6 col-md-3">
        <NuxtLink to="/list/users" class="card h-100 text-decoration-none quick-action-card">
          <div class="card-body text-center">
            <i class="bi bi-people fs-1 text-info mb-2 d-block"></i>
            <h6 class="card-title mb-1">Users</h6>
            <small class="text-muted">Manage users</small>
          </div>
        </NuxtLink>
      </div>
      
      <div class="col-6 col-md-3">
        <NuxtLink to="/list" class="card h-100 text-decoration-none quick-action-card">
          <div class="card-body text-center">
            <i class="bi bi-list-ul fs-1 text-warning mb-2 d-block"></i>
            <h6 class="card-title mb-1">All Lists</h6>
            <small class="text-muted">Browse resources</small>
          </div>
        </NuxtLink>
      </div>
    </div>

    <!-- Recent Activity Section (placeholder) -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Activity</h5>
            <button class="btn btn-sm btn-outline-primary">
              <i class="bi bi-arrow-clockwise me-1"></i>
              Refresh
            </button>
          </div>
          <div class="card-body">
            <div class="text-center py-4">
              <i class="bi bi-clock-history fs-1 text-muted mb-3 d-block"></i>
              <p class="text-muted mb-3">No recent activity to display</p>
              <p class="small text-muted">
                Start by creating an estimate or managing your documents to see activity here.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useAuth } from '~/composables/useAuth';

// Get user information
const { user } = useAuth();

// Set page metadata
useHead({
  title: 'Dashboard | Thanzil',
  meta: [
    {
      name: 'description',
      content: 'Thanzil dashboard - Access your business management tools and view recent activity.'
    }
  ]
});

// Utility function to format date
const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(date);
};
</script>

<style scoped>
.quick-action-card {
  transition: all 0.2s ease;
  border: 1px solid var(--bs-border-color);
}

.quick-action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: var(--bs-primary);
}

.quick-action-card .card-body {
  padding: 1.5rem 1rem;
}

.quick-action-card:hover .card-title {
  color: var(--bs-primary) !important;
}

@media (max-width: 768px) {
  .quick-action-card .card-body {
    padding: 1rem 0.5rem;
  }
  
  .quick-action-card i {
    font-size: 2rem !important;
  }
}
</style>
