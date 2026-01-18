# Production Deployment Execution Order

## Quick Reference: Execute These Commands in Order

### Phase 1: Dependencies and Configuration (15 minutes)

```bash
# 1. Install export libraries
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf

# 2. Verify installation
composer show maatwebsite/excel
composer show barryvdh/laravel-dompdf

# 3. Update .env file
nano .env
# Set: APP_ENV=production, APP_DEBUG=false, CACHE_DRIVER=redis, QUEUE_CONNECTION=redis

# 4. Run migrations
php artisan migrate --force

# 5. Clear all caches
php artisan erp:clear-all-caches
```

### Phase 2: Verification (10 minutes)

```bash
# 1. Run production verification
php artisan erp:verify-production-setup

# 2. Check tenant isolation
php artisan erp:check-tenant-isolation

# 3. Check system health
php artisan erp:check-system-health

# 4. Verify scheduled tasks
php artisan schedule:list
```

### Phase 3: Queue Workers Setup (20 minutes)

```bash
# 1. Test queue manually
php artisan queue:work --tries=3 --timeout=300

# 2. Setup supervisor (Linux)
sudo cp supervisor-queue-worker.conf.example /etc/supervisor/conf.d/erp-queue-worker.conf
sudo nano /etc/supervisor/conf.d/erp-queue-worker.conf  # Update paths
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start erp-queue-worker:*

# 3. Verify queue workers
sudo supervisorctl status
```

### Phase 4: Cron Jobs Setup (5 minutes)

```bash
# 1. Add cron job
crontab -e
# Add: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# 2. Verify cron
crontab -l

# 3. Test scheduler
php artisan schedule:run
```

### Phase 5: API Testing (15 minutes)

```bash
# Test each endpoint (replace TOKEN and TENANT_ID)
TOKEN="your_token"
TENANT_ID="your_tenant_id"

# Reports
curl -X GET "http://your-domain/api/erp/reports" \
  -H "Authorization: Bearer $TOKEN" -H "X-Tenant-ID: $TENANT_ID"

# Dashboard
curl -X GET "http://your-domain/api/erp/dashboard/metrics" \
  -H "Authorization: Bearer $TOKEN" -H "X-Tenant-ID: $TENANT_ID"

# System Settings
curl -X GET "http://your-domain/api/erp/settings" \
  -H "Authorization: Bearer $TOKEN" -H "X-Tenant-ID: $TENANT_ID"

# System Health
curl -X GET "http://your-domain/api/erp/system-health" \
  -H "Authorization: Bearer $TOKEN" -H "X-Tenant-ID: $TENANT_ID"
```

### Phase 6: Final Optimization (5 minutes)

```bash
# 1. Optimize application
php artisan optimize

# 2. Cache configuration
php artisan config:cache

# 3. Cache routes
php artisan route:cache

# 4. Cache views
php artisan view:cache
```

### Phase 7: Monitoring Setup (10 minutes)

```bash
# 1. Setup log rotation (if needed)
# Configure logrotate or similar

# 2. Monitor queue
php artisan queue:monitor

# 3. Check failed jobs
php artisan queue:failed

# 4. Monitor system health
php artisan erp:check-system-health
```

## Total Estimated Time: ~80 minutes

## Post-Deployment Monitoring Commands

```bash
# Daily checks
php artisan erp:check-system-health
php artisan queue:failed
php artisan erp:verify-production-setup

# Weekly checks
php artisan erp:check-tenant-isolation
php artisan optimize
```

## Emergency Commands

```bash
# Restart queue workers
sudo supervisorctl restart erp-queue-worker:*

# Clear all caches
php artisan erp:clear-all-caches

# Retry failed jobs
php artisan queue:retry all

# Check system status
php artisan erp:check-system-health
```

