#!/bin/bash

# Manual deployment script (fallback if GitHub Actions fails)
# Usage: ./deploy.sh

set -e

echo "🚀 Starting manual deployment..."

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Enable maintenance mode
echo -e "${YELLOW}🔧 Enabling maintenance mode...${NC}"
php artisan down --retry=60 || true

# Pull latest code
echo -e "${YELLOW}📥 Pulling latest code from Git...${NC}"
git pull origin main

# Install/update composer dependencies
echo -e "${YELLOW}📦 Installing Composer dependencies...${NC}"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Clear old caches
echo -e "${YELLOW}🧹 Clearing old caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
echo -e "${YELLOW}🗄️  Running database migrations...${NC}"
php artisan migrate --force

# Rebuild caches
echo -e "${YELLOW}⚡ Rebuilding caches...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
echo -e "${YELLOW}🔧 Optimizing autoloader...${NC}"
composer dump-autoload --optimize

# Fix permissions
echo -e "${YELLOW}🔐 Setting permissions...${NC}"
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# Restart queue workers
echo -e "${YELLOW}🔄 Restarting queue workers...${NC}"
php artisan queue:restart || true

# Restart PHP-FPM
echo -e "${YELLOW}♻️  Restarting PHP-FPM...${NC}"
sudo systemctl reload php8.2-fpm || sudo systemctl reload php-fpm

# Restart Nginx
echo -e "${YELLOW}🌐 Restarting Nginx...${NC}"
sudo systemctl reload nginx

# Disable maintenance mode
echo -e "${YELLOW}✅ Disabling maintenance mode...${NC}"
php artisan up

echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo -e "${GREEN}📊 Deployment Summary:${NC}"
echo "   - Branch: $(git rev-parse --abbrev-ref HEAD)"
echo "   - Commit: $(git rev-parse --short HEAD)"
echo "   - Time: $(date)"
