# Notification Service Design Specification

## Overview

The Notification Service provides a centralized and reusable mechanism for displaying notifications (toasts) across the application. It supports various notification types, including success, error, warning, and informational messages. The service is designed to be lightweight, modular, and easy to integrate.

## Features

- **Notification Types**: Supports `success`, `error`, `warning`, and `info`.
- **Global Accessibility**: Notifications can be triggered from anywhere in the application.
- **Customizable**: Allows customization of message, type, duration, and position.
- **Dismissal Options**:
  - Auto-dismiss after a specified duration.
  - Manual dismissal via a close button.
- **Smooth Transitions**: Includes smooth animations for appearance and dismissal.
- **Compact Design**: Ensures a non-intrusive user experience.
- **Queue Management**: Handles multiple notifications in a queue.

## Implementation

### Notification Service API

```typescript
import { reactive, computed } from 'vue';

interface Notification {
  id: string;
  message: string;
  type: 'success' | 'error' | 'warning' | 'info';
  duration: number;
}

const notifications = reactive<Notification[]>([]);

export const useNotificationService = () => {
  const addNotification = (message: string, type: 'success' | 'error' | 'warning' | 'info', duration = 5000) => {
    const id = Date.now().toString();
    notifications.push({ id, message, type, duration });

    if (duration > 0) {
      setTimeout(() => removeNotification(id), duration);
    }
  };

  const removeNotification = (id: string) => {
    const index = notifications.findIndex((n) => n.id === id);
    if (index !== -1) {
      notifications.splice(index, 1);
    }
  };

  const activeNotifications = computed(() => notifications);

  return {
    addNotification,
    removeNotification,
    activeNotifications,
  };
};
```

### Notification Component

```vue
<template>
  <div class="notification-container position-fixed top-0 end-0 p-3" style="z-index: 1055; max-width: 350px;">
    <div
      v-for="notification in activeNotifications"
      :key="notification.id"
      class="toast show"
      :class="getNotificationConfig(notification.type).border"
      role="alert"
      aria-live="assertive"
      aria-atomic="true"
      style="transition: all 0.3s ease; opacity: 1; border-left-width: 4px;"
    >
      <div class="toast-header" :class="getNotificationConfig(notification.type).header">
        <strong class="me-auto">{{ getNotificationConfig(notification.type).title }}</strong>
        <button
          type="button"
          class="btn-close"
          aria-label="Close"
          @click="removeNotification(notification.id)"
        ></button>
      </div>
      <div class="toast-body">
        {{ notification.message }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useNotificationService } from '~/services/notificationService';

const { activeNotifications, removeNotification } = useNotificationService();

const notificationConfig = {
  success: { border: 'border-success', header: 'text-bg-success', title: 'Success' },
  error: { border: 'border-danger', header: 'text-bg-danger', title: 'Error' },
  warning: { border: 'border-warning', header: 'text-bg-warning', title: 'Warning' },
  info: { border: 'border-info', header: 'text-bg-info', title: 'Information' },
} as const;

const getNotificationConfig = (type: string) =>
  notificationConfig[type as keyof typeof notificationConfig] || { border: '', header: '', title: 'Notification' };
</script>

<style scoped>
.notification-container {
  max-width: 350px;
}
</style>
```

## Usage

1. **Register the Service**: Import and use the `useNotificationService` in your components or composables.

```typescript
import { useNotificationService } from '~/services/notificationService';

const { addNotification } = useNotificationService();

addNotification('Operation completed successfully!', 'success');
addNotification('An error occurred.', 'error', 0); // Pinned notification
```

2. **Include the Component**: Add the `Notification` component to your app layout or main component.

```vue
<Notification />
```

## Best Practices

- Use meaningful messages to inform users about the status of their actions.
- Avoid overloading users with too many notifications.
- Use pinned notifications (`duration = 0`) for critical errors that require user attention.
- Test the service across different screen sizes to ensure responsiveness.
