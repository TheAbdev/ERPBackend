# Quick Setup Commands - Production Deployment

## 1. Install Dependencies

```bash
# Install export libraries
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
# OR
composer require spatie/laravel-pdf

# Install cron expression parser (if needed)
composer require dragonmantank/cron-expression
```

## 2. Run Migrations

```bash
php artisan migrate --force
php artisan migrate:status
```

## 3. Verify Setup

```bash
# Comprehensive production check
php artisan erp:verify-production-setup

# Check tenant isolation
php artisan erp:check-tenant-isolation

# Check system health
php artisan erp:check-system-health
```

## 4. Configure Environment

Update `.env`:

```env
# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=erp_crm

# Queue
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# App
APP_DEBUG=false
APP_ENV=production
```

## 5. Clear and Rebuild Caches

```bash
# Clear all caches (including custom ERP/CRM caches)
php artisan erp:clear-all-caches

# Or manually
php artisan optimize:clear
php artisan optimize
```

## 6. Setup Queue Workers

### Development

```bash
php artisan queue:work --tries=3 --timeout=300
```

### Production (Supervisor)

```bash
# Copy supervisor config
sudo cp supervisor-queue-worker.conf.example /etc/supervisor/conf.d/erp-queue-worker.conf

# Edit config (update paths)
sudo nano /etc/supervisor/conf.d/erp-queue-worker.conf

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start erp-queue-worker:*
```

## 7. Setup Scheduled Tasks

```bash
# Add to crontab
crontab -e

# Add this line:
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1

# Verify scheduled tasks
php artisan schedule:list
```

## 8. Test API Endpoints

```bash
# Reports
curl -X GET http://your-domain/api/erp/reports \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-ID: TENANT_ID"

# Dashboard
curl -X GET http://your-domain/api/erp/dashboard/metrics \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-ID: TENANT_ID"

# System Health
curl -X GET http://your-domain/api/erp/system-health \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-ID: TENANT_ID"
```

## 9. Monitor Queue

```bash
# Check queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## 10. Maintenance Commands

```bash
# Generate scheduled reports manually
php artisan erp:generate-scheduled-reports

# Check system health
php artisan erp:check-system-health

# Clear all caches
php artisan erp:clear-all-caches

# Verify production setup
php artisan erp:verify-production-setup
```

## Troubleshooting

### Cache Issues

```bash
# Test cache
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
```

### Queue Issues

```bash
# Restart queue workers
sudo supervisorctl restart erp-queue-worker:*

# Check queue connection
php artisan tinker
>>> config('queue.default');
```

### Scheduled Tasks Not Running

```bash
# Test scheduler
php artisan schedule:run

# Check cron
crontab -l
```

