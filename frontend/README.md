# BOS Frontend

A modern business management application built with Nuxt 3, Vue 3, and Bootstrap 5.

## Quick Start

### 1. Install Dependencies

```bash
npm install
```

### 2. Configure Environment

Copy the example environment file and customize it:

```bash
cp .env.example .env
```

Update the domain configuration in `.env`:

```env
# Update these URLs to match your environment
NUXT_FRONTEND_URL="http://localhost:3000"
NUXT_BACKEND_URL="http://127.0.0.1:8000"
NUXT_PUBLIC_API_BASE="http://127.0.0.1:8000/api"
```

### 3. Start Development Server

```bash
npm run dev
```

The application will be available at `http://localhost:3000`

## Configuration

This application uses a centralized configuration system. For detailed configuration options, see [CONFIG.md](./CONFIG.md).

### Quick Domain Change

When changing domains, update these variables in `.env`:

- `NUXT_FRONTEND_URL` - Your frontend domain
- `NUXT_BACKEND_URL` - Your backend domain  
- `NUXT_PUBLIC_API_BASE` - Your API base URL

## Development

Start the development server on `http://localhost:3000`:

```bash
npm run dev
```

## Production

Build the application for production:

```bash
npm run build
```

Locally preview production build:

```bash
npm run preview
```

Check out the [deployment documentation](https://nuxt.com/docs/getting-started/deployment) for more information.
