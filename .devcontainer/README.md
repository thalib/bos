# BOS DevContainer Configuration

This directory contains the development container configuration for the BOS project.

## Files

- `devcontainer.json` - Main devcontainer configuration with PHP 8.3
- `setup.sh` - Post-creation setup script for dependencies
- `php.ini` - Custom PHP configuration for development

## Features

- **PHP 8.3** with all required extensions for Laravel
- **Node.js 20** for Nuxt frontend development
- **VS Code extensions** for PHP, Vue, and Laravel development
- **Port forwarding** for Laravel (8000) and Nuxt (3000)
- **Automatic setup** of dependencies and environment

## Usage

1. Open the project in GitHub Codespaces or VS Code with Dev Containers extension
2. The container will automatically build and run the setup script
3. Laravel API will be available at http://localhost:8000
4. Nuxt frontend will be available at http://localhost:3000

## Manual Setup (if needed)

If you need to run setup manually:

```bash
cd /workspaces/bos
bash .devcontainer/setup.sh
```
