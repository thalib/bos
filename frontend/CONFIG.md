# Configuration Guide

This document explains how to configure the Thanzil frontend application using the centralized configuration system.

## Overview

The frontend uses a centralized configuration system following Nuxt 3 conventions. All configuration is managed through:

1. **Environment Variables** (`.env` file)
2. **Runtime Configuration** (`nuxt.config.ts`)
3. **App Configuration Composable** (`composables/useThanzilConfig.ts` - accessed via `useThanzilConfig()`)

## Quick Setup

### 1. Environment Configuration

Copy the example environment file and customize it:

```bash
cp .env.example .env
```

### 2. Update Domain Configuration

When changing domains, update these key variables in `.env`:

```env
# Frontend URL (where your Nuxt app runs)
NUXT_FRONTEND_URL="https://yourdomain.com"

# Backend URL (where your Laravel API runs)  
NUXT_BACKEND_URL="https://api.yourdomain.com"

# API Base URL (usually backend URL + /api)
NUXT_PUBLIC_API_BASE="https://api.yourdomain.com/api"
```

## Configuration Structure

### App Configuration
- `NUXT_APP_NAME` - Application name
- `NUXT_APP_DESCRIPTION` - Application description
- `NUXT_APP_VERSION` - Application version

### Domain Configuration
- `NUXT_FRONTEND_URL` - Frontend application URL
- `NUXT_BACKEND_URL` - Backend API server URL

### API Configuration
- `NUXT_PUBLIC_API_BASE` - Full API base URL
- `NUXT_PUBLIC_API_VERSION` - API version (default: v1)
- `NUXT_PUBLIC_API_TIMEOUT` - Request timeout in milliseconds

### Authentication
- `NUXT_PUBLIC_ENABLE_AUTH` - Enable/disable authentication
- `NUXT_PUBLIC_SESSION_TIMEOUT` - Session timeout in seconds
- `NUXT_PUBLIC_TOKEN_REFRESH_INTERVAL` - Token refresh interval in milliseconds

### UI Configuration
- `NUXT_PUBLIC_THEME` - Default theme
- `NUXT_PUBLIC_ITEMS_PER_PAGE` - Default pagination size
- `NUXT_PUBLIC_MAX_UPLOAD_SIZE` - Maximum file upload size in bytes

### Feature Flags
- `NUXT_PUBLIC_ENABLE_DEVTOOLS` - Enable Nuxt devtools
- `NUXT_PUBLIC_ENABLE_DEBUG` - Enable debug mode

## Usage in Code

### Using the App Configuration Composable

```typescript
// Get all configuration
const config = useThanzilConfig()

// Access specific configuration sections
console.log(config.app.name)           // App name
console.log(config.domains.frontend)   // Frontend URL
console.log(config.api.baseUrl)       // API base URL
console.log(config.auth.enabled)      // Auth enabled status
```

### Using API Endpoints

```typescript
// Generate API endpoint URLs
const endpoint = useApiEndpoint('users')           // /api/v1/users
const endpoint = useApiEndpoint('products', 'v2')  // /api/v2/products
```

### Using Feature Flags

```typescript
// Check if a feature is enabled
const debugEnabled = useFeatureFlag('debug')
const devtoolsEnabled = useFeatureFlag('devtools')
```

### Using Environment-Specific Configuration

```typescript
const envConfig = useEnvironmentConfig()

if (envConfig.isDevelopment) {
  console.log('Running in development mode')
}

if (envConfig.showDebugInfo) {
  console.log('Debug info enabled')
}
```

## Environment-Specific Configuration

### Development
```env
NODE_ENV=development
NUXT_PUBLIC_ENABLE_DEBUG=true
NUXT_PUBLIC_ENABLE_DEVTOOLS=true
NUXT_FRONTEND_URL="http://localhost:3000"
NUXT_BACKEND_URL="http://127.0.0.1:8000"
```

### Production
```env
NODE_ENV=production
NUXT_PUBLIC_ENABLE_DEBUG=false
NUXT_PUBLIC_ENABLE_DEVTOOLS=false
NUXT_FRONTEND_URL="https://yourdomain.com"
NUXT_BACKEND_URL="https://api.yourdomain.com"
```

## Best Practices

1. **Never commit `.env` files** - Use `.env.example` as a template
2. **Use environment variables** for all configurable values
3. **Prefix public variables** with `NUXT_PUBLIC_` for client-side access
4. **Group related configuration** logically
5. **Document all configuration options** in `.env.example`
6. **Use feature flags** for optional features
7. **Validate configuration** in development mode

## Troubleshooting

### Configuration Not Loading
- Ensure `.env` file exists in the frontend root directory
- Check that variable names have correct prefixes (`NUXT_PUBLIC_` for client-side)
- Restart the development server after changing `.env`

### API Endpoints Not Working
- Verify `NUXT_PUBLIC_API_BASE` matches your Laravel API URL
- Check that the backend is running and accessible
- Ensure CORS is properly configured on the backend

### Build Issues
- Make sure all required environment variables are set
- Check for typos in variable names
- Verify that sensitive data is not in public variables

## Migration from Hardcoded Values

If you have existing hardcoded URLs or configuration:

1. Move the values to `.env` file
2. Update `nuxt.config.ts` to use environment variables
3. Replace hardcoded values in components with `useThanzilConfig()`
4. Test in both development and production environments

## Security Considerations

- **Never expose sensitive data** in public variables
- **Use server-only variables** for secrets (without `NUXT_PUBLIC_` prefix)
- **Validate and sanitize** configuration values
- **Use HTTPS** in production environments
- **Implement proper CORS** policies
