#!/bin/bash

# Development setup script for ForgeLite

set -e

echo "Setting up ForgeLite development environment..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
fi

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install

# Install Node dependencies
echo "Installing Node dependencies..."
npm install

# Generate application key
echo "Generating application key..."
php artisan key:generate

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database (if seeder exists)
if [ -f database/seeders/DatabaseSeeder.php ]; then
    echo "Seeding database..."
    php artisan db:seed --force
fi

# Build frontend assets
echo "Building frontend assets..."
npm run build

echo "Development setup complete!"
echo "You can now run: docker-compose up"

