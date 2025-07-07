<template>
  <nav v-if="breadcrumbs.length > 1" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0" :class="breadcrumbClasses">
      <li 
        v-for="(item, index) in breadcrumbs" 
        :key="item.path || index"
        class="breadcrumb-item"
        :class="{ 'active': index === breadcrumbs.length - 1 }"
        :aria-current="index === breadcrumbs.length - 1 ? 'page' : undefined"
      >
        <!-- Link for non-active items -->
        <NuxtLink 
          v-if="index !== breadcrumbs.length - 1 && item.path"
          :to="item.path"
          class="text-decoration-none"
          @click="handleBreadcrumbClick(item)"
        >
          <i v-if="item.icon" :class="item.icon + ' me-1'"></i>
          {{ item.label }}
        </NuxtLink>
        
        <!-- Text for active item -->
        <span v-else>
          <i v-if="item.icon" :class="item.icon + ' me-1'"></i>
          {{ item.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from '#app';
import { useAuth } from '~/composables/useAuth';
import { useToast } from '~/utils/errorHandling';

// Define TypeScript interfaces
interface BreadcrumbItem {
  label: string;
  path?: string;
  icon?: string;
}

interface Props {
  customBreadcrumbs?: BreadcrumbItem[];
  showHome?: boolean;
  homeLabel?: string;
  homePath?: string;
  homeIcon?: string;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'light' | 'dark';
}

// Props with defaults
const props = withDefaults(defineProps<Props>(), {
  customBreadcrumbs: () => [],
  showHome: true,
  homeLabel: 'Dashboard',
  homePath: '/',
  homeIcon: 'bi bi-house-fill',
  size: 'md',
  variant: 'default'
});

// Composables
const route = useRoute();
const { isAuthenticated } = useAuth();
const { showSuccessToast } = useToast();

// Breadcrumb classes based on props
const breadcrumbClasses = computed(() => ({
  'breadcrumb-sm': props.size === 'sm',
  'breadcrumb-lg': props.size === 'lg',
  'text-light': props.variant === 'dark',
  'bg-light rounded px-3 py-2': props.variant === 'light'
}));

// Generate breadcrumbs based on current route or custom breadcrumbs
const breadcrumbs = computed((): BreadcrumbItem[] => {
  // If custom breadcrumbs are provided, use them
  if (props.customBreadcrumbs.length > 0) {
    const crumbs = [...props.customBreadcrumbs];
    
    // Add home breadcrumb if requested and authenticated
    if (props.showHome && isAuthenticated.value) {
      crumbs.unshift({
        label: props.homeLabel,
        path: props.homePath,
        icon: props.homeIcon
      });
    }
    
    return crumbs;
  }
  
  // Auto-generate breadcrumbs from route
  const segments = route.path.split('/').filter(Boolean);
  const crumbs: BreadcrumbItem[] = [];
  
  // Add home if authenticated
  if (props.showHome && isAuthenticated.value) {
    crumbs.push({
      label: props.homeLabel,
      path: props.homePath,
      icon: props.homeIcon
    });
  }
  
  // Build breadcrumbs from path segments
  let currentPath = '';
  segments.forEach((segment, index) => {
    currentPath += `/${segment}`;
    
    // Skip if this is the current page (last segment)
    const isLast = index === segments.length - 1;
    
    crumbs.push({
      label: formatSegmentLabel(segment),
      path: isLast ? undefined : currentPath,
      icon: getSegmentIcon(segment)
    });
  });
  
  return crumbs;
});

// Format segment label for display
const formatSegmentLabel = (segment: string): string => {
  // Handle special cases
  const labelMap: Record<string, string> = {
    'doc': 'Documents',
    'estimates': 'Estimates',
    'list': 'Lists',
    '404': '404 Error'
  };
  
  if (labelMap[segment]) {
    return labelMap[segment];
  }
  
  // Capitalize and format generic segments
  return segment
    .split('-')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

// Get icon for segment
const getSegmentIcon = (segment: string): string | undefined => {
  const iconMap: Record<string, string> = {
    'doc': 'bi bi-file-text',
    'estimates': 'bi bi-calculator',
    'list': 'bi bi-list-ul',
    '404': 'bi bi-exclamation-triangle'
  };
  
  return iconMap[segment];
};

// Handle breadcrumb click
const handleBreadcrumbClick = (item: BreadcrumbItem): void => {
  if (item.path) {
    showSuccessToast(`Navigating to ${item.label}`);
  }
};
</script>

<style scoped>
.breadcrumb-sm {
  font-size: 0.875rem;
}

.breadcrumb-lg {
  font-size: 1.125rem;
}

.breadcrumb-sm .breadcrumb-item + .breadcrumb-item::before {
  font-size: 0.75rem;
}

.breadcrumb-lg .breadcrumb-item + .breadcrumb-item::before {
  font-size: 1rem;
}
</style>
