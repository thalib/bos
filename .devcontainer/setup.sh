#!/bin/bash

echo "🚀 Setting up BOS development environment..."

# Update package lists
sudo apt-get update

# Install additional PHP extensions needed for Laravel
sudo apt-get install -y \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-zip \
    php8.3-gd \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-xml \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-sqlite3 \
    php8.3-redis \
    unzip \
    git

# Install Composer
if ! command -v composer &> /dev/null; then
    echo "📦 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi

# Verify PHP version
echo "✅ PHP Version:"
php --version

# Install backend dependencies
echo "📚 Installing Laravel dependencies..."
cd /workspaces/bos/backend
composer install --no-interaction --optimize-autoloader

# Install frontend dependencies
echo "🎨 Installing Nuxt dependencies..."
cd /workspaces/bos/frontend
npm install

# Set up Laravel environment
cd /workspaces/bos/backend
if [ ! -f .env ]; then
    echo "🔧 Setting up Laravel environment..."
    cp .env.example .env
    php artisan key:generate
fi

# Set proper permissions
sudo chown -R vscode:vscode /workspaces/bos
sudo chmod -R 755 /workspaces/bos/backend/storage
sudo chmod -R 755 /workspaces/bos/backend/bootstrap/cache

echo "🎉 Development environment setup complete!"
echo "🔗 Available ports:"
echo "   - Laravel API: http://localhost:8000"
echo "   - Nuxt Frontend: http://localhost:3000"
