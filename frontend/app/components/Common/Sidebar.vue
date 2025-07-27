<template>
  <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <!-- Loading State -->
    <CommonLoading v-if="isLoading" />

    <!-- Sidebar content -->
    <div v-else>
      <!-- Offcanvas Header -->
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="sidebarLabel" data-testid="user-name">
          <ClientOnly fallback="Guest">
            {{ currentUser?.name || 'Guest' }}
          </ClientOnly>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-testid="sidebar-close"
          aria-label="Close"></button>
      </div>

      <!-- Offcanvas Body -->
      <div class="offcanvas-body">
        <!-- Error State -->
        <div v-if="hasError" class="alert alert-warning" data-testid="error-alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Failed to load menu. Please try again or contact support if the issue persists.
        </div>

        <!-- Empty State -->
        <div v-else-if="menuItems.length === 0" class="text-center text-muted" data-testid="empty-state">
          <p>No menu items available. Please refresh the page or try again later.</p>
        </div>

        <!-- Menu Items -->
        <nav v-else class="nav flex-column">
          <template v-for="item in menuItems" :key="`${item.type}-${item.id}`">
            <!-- Regular Menu Item -->
            <template v-if="item.type === 'item'">
              <NuxtLink :to="item.path" class="nav-link" :data-testid="`menu-item-${item.id}`">
                <i v-if="item.icon" :class="[item.icon, 'me-2']"></i>
                {{ item.name }}
              </NuxtLink>
            </template>

            <!-- Menu Section (Collapsible) -->
            <template v-else-if="item.type === 'section'">
              <button class="nav-link btn btn-link text-start" type="button" data-bs-toggle="collapse"
                :data-bs-target="`#section-${item.title}`" :aria-expanded="false"
                :data-testid="`menu-section-${item.title}`">
                <i class="bi bi-chevron-right me-2"></i>
                {{ item.title }}
              </button>
              <div class="collapse" :id="`section-${item.title}`">
                <div class="ms-3">
                  <NuxtLink v-for="subItem in item.items" :key="subItem.id" :to="subItem.path" class="nav-link"
                    :data-testid="`menu-item-${subItem.id}`">
                    <i v-if="subItem.icon" :class="[subItem.icon, 'me-2']"></i>
                    {{ subItem.name }}
                  </NuxtLink>
                </div>
              </div>
            </template>

            <!-- Menu Divider -->
            <template v-else-if="item.type === 'divider'">
              <hr class="dropdown-divider" data-testid="menu-divider" />
            </template>
          </template>
        </nav>

        <!-- Bottom Section -->
        <div class="mt-auto">
          <hr />
          <p><a href="https://github.com/thalib/bos" target="_blank">BOS - Business OS</a></p>
        </div>
      </div>
    </div>
  </div>

</template>

<script setup lang="ts">
import { onMounted } from 'vue';

// Types for menu items
interface MenuItem {
  type: 'item' | 'section' | 'divider'
  id?: number
  name?: string
  path?: string
  icon?: string
  title?: string // for sections
  items?: MenuItem[] // for sections
}

// Services
const authService = useAuthService()
const menuService = useMenuService();
const notifyService = useNotifyService();


// Reactive state
const menuItems = ref<MenuItem[]>([])
const isLoading = ref(true)
const hasError = ref(false)

// Computed properties
const currentUser = computed(() => authService.getCurrentUser())

// Methods
const fetchMenuItems = async () => {
  try {
    isLoading.value = true;
    hasError.value = false;

    menuItems.value = await menuService.get();
  } catch (error) {
    hasError.value = true;
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => {
  const sidebarElement = document.getElementById('sidebar');
  if (sidebarElement) {
    sidebarElement.addEventListener('shown.bs.offcanvas', () => {
      fetchMenuItems();
    });
  }
});

</script>