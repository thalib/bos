# Notify Service Design Specification

## Overview

The Notify Service `frontend\app\utils\notify.ts`  for Nuxt4 application provides a centralized and reusable mechanism for displaying notifications (toasts) across the application. It supports various notification types, including success, error, warning, and informational messages. The service is designed to be lightweight, modular, and easy to integrate. It uses **toastr.js** for showing notifications, ensuring flexibility to replace toastr.js with another library if needed.

## Features

- **Notification Types**: Supports `success`, `error`, `warning`, and `info`.
- **Global Accessibility**: Notifications can be triggered from anywhere in the application.
- **Customizable**: Allows customization of message, type, duration, and position.
- **Dismissal Options**:
  - Auto-dismiss (if duration is set).
  - Manual close (pinned notifications).
- **Smooth Transitions**: Includes animations for appearance and dismissal.
- **Compact Design**: Ensures a non-intrusive user experience.
- **Queue Management**: Handles multiple notifications efficiently.
- **Flexible Design**: Built with toastr.js but designed to allow easy replacement with other libraries.

## Implementation

### Notify Service API

```typescript
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

interface NotificationOptions {
  type: 'success' | 'error' | 'warning' | 'info';
  message: string;
  title?: string;
  duration?: number; // Duration in milliseconds, 0 for pinned notifications
}

export const useNotifiyService = () => {
  const notify = ({ type, message, title = '', duration = 5000 }: NotificationOptions) => {
    toastr.options = {
      closeButton: true,
      progressBar: true,
      timeOut: duration > 0 ? duration : 0,
      extendedTimeOut: duration > 0 ? 1000 : 0,
      positionClass: 'toast-top-right',
    };

    switch (type) {
      case 'success':
        toastr.success(message, title);
        break;
      case 'error':
        console.error(message);
        toastr.error(message, title);
        break;
      case 'warning':
        console.warn(message);
        toastr.warning(message, title);
        break;
      case 'info':
        toastr.info(message, title);
        break;
      default:
        console.warn('Unknown notification type:', type);
    }
  };

  return {
    notify,
  };
};
```

### Usage

1. **Register the Service**: Import and use the `useNotifiyService` in your components or composables.

```typescript
import { useNotifiyService } from '~/services/notifiyService';

const { notify } = useNotifiyService();

notify({ message: 'Operation completed successfully!', type: 'success' });
notify({ message: 'An error occurred.', type: 'error', duration: 0 }); // Pinned notification
```

2. **Include the Toastr CSS**: Ensure the toastr CSS is included in your project. Add it to your Nuxt configuration or global styles.

```javascript
// nuxt.config.ts
export default defineNuxtConfig({
  css: ['toastr/build/toastr.min.css'],
});
```



## Best Practices

- Use meaningful messages to inform users about the status of their actions.
- Avoid overloading users with too many notifications.
- Use pinned notifications (`duration = 0`) for critical errors that require user attention.
- Test the service across different screen sizes to ensure responsiveness.
- Ensure the service adheres to the structured design principles outlined in `design\rules-app.md`

## Design Considerations

- **Flexibility**: The service is built with toastr.js but can be easily adapted to use other notification libraries.
- **Error Handling**: Ensure consistent error handling and user feedback across the application.
- **Performance**: Optimize the service to handle multiple notifications efficiently without impacting the user experience.
- **Error and Warning Logging**: For `error` and `warning` types, in addition to displaying a toast notification, log the message in the browser's console for debugging purposes.
- **Optional Duration**: The `duration` property should be optional. If not provided, it will default to a predefined value.
- **Pinned Errors**: Notifications of type `error` should be pinned by default (i.e., `duration = 0`) so that users can see and manually clear them.
- The Notify Service `frontend\app\utils\notify.ts` is core part of the notification mechanism in the frontend.
- All notifications must go through the Notify Service to ensure consistency and maintainability.


