{# #!/bin/bash

# Rollback script - reverts to previous Git commit
# Usage: ./rollback.sh [number_of_commits_to_go_back]

set -e

COMMITS_BACK=${1:-1}

# Colors
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${RED}⚠️  ROLLBACK INITIATED${NC}"
echo -e "${YELLOW}This will revert to $COMMITS_BACK commit(s) back${NC}"
echo ""

# Show current and target commits
echo "Current commit:"
git log -1 --oneline
echo ""
echo "Will rollback to:"
git log --oneline -1 HEAD~$COMMITS_BACK
echo ""

read -p "Are you sure you want to continue? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Rollback cancelled."
    exit 1
fi

# Enable maintenance mode
echo -e "${YELLOW}🔧 Enabling maintenance mode...${NC}"
php artisan down --retry=60 || true

# Reset to previous commit
echo -e "${YELLOW}⏪ Rolling back to previous commit...${NC}"
git reset --hard HEAD~$COMMITS_BACK

# Install dependencies
echo -e "${YELLOW}📦 Installing dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# Clear caches
echo -e "${YELLOW}🧹 Clearing caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations (rollback if needed)
echo -e "${YELLOW}🗄️  Checking database migrations...${NC}"
read -p "Do you need to rollback migrations? (yes/no): " -r
if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    read -p "How many migration steps to rollback?: " STEPS
    php artisan migrate:rollback --step=$STEPS
fi

# Rebuild caches
echo -e "${YELLOW}⚡ Rebuilding caches...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
echo -e "${YELLOW}♻️  Restarting services...${NC}"
php artisan queue:restart || true
sudo systemctl reload php8.2-fpm || sudo systemctl reload php-fpm
sudo systemctl reload nginx

# Disable maintenance mode
echo -e "${YELLOW}✅ Disabling maintenance mode...${NC}"
php artisan up

echo -e "${GREEN}✅ Rollback completed!${NC}"
echo ""
echo "Current commit:"
git log -1 --oneline
echo ""
echo -e "${YELLOW}⚠️  Don't forget to:${NC}"
echo "  1. Test the application"
echo "  2. Check logs for errors"
echo "  3. Consider force-pushing to update remote: git push -f origin main" #}
