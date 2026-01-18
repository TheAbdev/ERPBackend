#!/bin/bash

# Production Deployment Quick Start Script
# Run this script to perform initial production setup checks

set -e

echo "=========================================="
echo "ERP & CRM Production Deployment Check"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
    fi
}

# Check if we're in the project directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: Not in Laravel project directory${NC}"
    exit 1
fi

echo "Step 1: Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_VERSION;")
if php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
    print_status 0 "PHP version $PHP_VERSION (>= 8.2.0)"
else
    print_status 1 "PHP version $PHP_VERSION (requires >= 8.2.0)"
fi

echo ""
echo "Step 2: Checking Composer dependencies..."
if composer show maatwebsite/excel > /dev/null 2>&1; then
    print_status 0 "maatwebsite/excel installed"
else
    print_status 1 "maatwebsite/excel not installed (run: composer require maatwebsite/excel)"
fi

if composer show barryvdh/laravel-dompdf > /dev/null 2>&1 || composer show spatie/laravel-pdf > /dev/null 2>&1; then
    print_status 0 "PDF library installed"
else
    print_status 1 "PDF library not installed (run: composer require barryvdh/laravel-dompdf)"
fi

echo ""
echo "Step 3: Checking environment configuration..."
if grep -q "APP_ENV=production" .env 2>/dev/null; then
    print_status 0 "APP_ENV=production"
else
    print_status 1 "APP_ENV not set to production"
fi

if grep -q "APP_DEBUG=false" .env 2>/dev/null; then
    print_status 0 "APP_DEBUG=false"
else
    print_status 1 "APP_DEBUG not set to false"
fi

if grep -q "CACHE_DRIVER=redis" .env 2>/dev/null; then
    print_status 0 "CACHE_DRIVER=redis"
else
    print_status 1 "CACHE_DRIVER not set to redis"
fi

if grep -q "QUEUE_CONNECTION=redis" .env 2>/dev/null; then
    print_status 0 "QUEUE_CONNECTION=redis"
else
    print_status 1 "QUEUE_CONNECTION not set to redis"
fi

echo ""
echo "Step 4: Running Laravel verification commands..."
php artisan erp:verify-production-setup
VERIFY_EXIT=$?

echo ""
echo "Step 5: Checking tenant isolation..."
php artisan erp:check-tenant-isolation
ISOLATION_EXIT=$?

echo ""
echo "Step 6: Checking scheduled tasks..."
php artisan schedule:list > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_status 0 "Scheduled tasks configured"
    echo "  Scheduled tasks:"
    php artisan schedule:list | grep -E "erp:|crm:" | sed 's/^/    /'
else
    print_status 1 "Scheduled tasks check failed"
fi

echo ""
echo "Step 7: Checking queue configuration..."
if php artisan queue:monitor > /dev/null 2>&1; then
    print_status 0 "Queue system accessible"
else
    print_status 1 "Queue system not accessible"
fi

echo ""
echo "Step 8: Checking supervisor status..."
if command -v supervisorctl > /dev/null 2>&1; then
    if sudo supervisorctl status erp-queue-worker:* > /dev/null 2>&1; then
        print_status 0 "Supervisor queue workers configured"
        echo "  Queue workers:"
        sudo supervisorctl status erp-queue-worker:* 2>/dev/null | sed 's/^/    /' || echo "    (No workers found)"
    else
        print_status 1 "Supervisor queue workers not configured"
    fi
else
    print_status 1 "Supervisor not installed"
fi

echo ""
echo "Step 9: Checking cron job..."
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    print_status 0 "Laravel scheduler cron job configured"
    echo "  Cron entry:"
    crontab -l 2>/dev/null | grep "schedule:run" | sed 's/^/    /'
else
    print_status 1 "Laravel scheduler cron job not configured"
    echo "  Add to crontab: * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
fi

echo ""
echo "Step 10: Checking file permissions..."
if [ -w "storage" ] && [ -w "bootstrap/cache" ]; then
    print_status 0 "Storage and cache directories writable"
else
    print_status 1 "Storage or cache directories not writable"
    echo "  Run: sudo chown -R www-data:www-data storage bootstrap/cache"
    echo "  Run: sudo chmod -R 775 storage bootstrap/cache"
fi

echo ""
echo "=========================================="
if [ $VERIFY_EXIT -eq 0 ] && [ $ISOLATION_EXIT -eq 0 ]; then
    echo -e "${GREEN}Production setup verification completed!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Review any warnings above"
    echo "2. Configure supervisor if not done"
    echo "3. Add cron job if not done"
    echo "4. Test API endpoints"
    echo "5. Monitor logs and queue workers"
    exit 0
else
    echo -e "${YELLOW}Production setup verification completed with warnings${NC}"
    echo ""
    echo "Please review the issues above and fix them before deployment."
    exit 1
fi

