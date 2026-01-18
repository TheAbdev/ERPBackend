# Production Setup Guide - ERP & CRM System

This guide provides step-by-step instructions for setting up the ERP & CRM system in production.

## Prerequisites

- PHP 8.2+
- Laravel 12
- MySQL/MariaDB
- Redis (recommended for caching and queues)
- Supervisor (for queue workers)

## Step 1: Install Dependencies

### Install Export Libraries

```bash
# Install Excel export library
composer require maatwebsite/excel

# Install PDF library (choose one)
composer require barryvdh/laravel-dompdf
# OR
composer require spatie/laravel-pdf
```

### Install Cron Expression Parser (if not already installed)

```bash
composer require dragonmantank/cron-expression
```

## Step 2: Run Migrations

```bash
# Run all migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

## Step 3: Verify Production Setup

```bash
# Run production verification command
php artisan erp:verify-production-setup
```

This command checks:
- Cache configuration
- Database indexes
- Tenant isolation
- Queue configuration
- Required tables

## Step 4: Configure Cache

Update `.env` file:

```env
CACHE_DRIVER=redis
CACHE_PREFIX=erp_crm

# Or for file-based caching (less optimal)
# CACHE_DRIVER=file
```

Clear and rebuild cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

## Step 5: Configure Queue

Update `.env` file:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Step 6: Start Queue Workers

### Development (Manual)

```bash
# Start queue worker
php artisan queue:work --tries=3 --timeout=300

# Or for specific queue
php artisan queue:work --queue=webhooks,reports,notifications --tries=3
```

### Production (Supervisor)

Create `/etc/supervisor/conf.d/erp-queue-worker.conf`:

```ini
[program:erp-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Then:

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start erp-queue-worker:*
```

## Step 7: Setup Scheduled Tasks

### Laravel Scheduler (routes/console.php)

The scheduler is already configured. Add to crontab:

```bash
# Edit crontab
crontab -e

# Add this line (runs every minute)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### Verify Scheduled Tasks

```bash
# List scheduled tasks
php artisan schedule:list

# Test scheduled tasks
php artisan schedule:test
```

## Step 8: Clear and Warm Cache

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 9: Verify Tenant Isolation

Run the verification command:

```bash
php artisan erp:verify-production-setup
```

Or manually check:

```php
// In tinker: php artisan tinker
$report = \App\Modules\ERP\Models\Report::first();
$report->tenant_id; // Should be set
```

## Step 10: Test API Endpoints

### Reports

```bash
# List reports
curl -X GET http://your-domain/api/erp/reports \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID"

# Get dashboard metrics
curl -X GET http://your-domain/api/erp/dashboard/metrics \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID"
```

### System Settings

```bash
# Get settings
curl -X GET http://your-domain/api/erp/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID"

# Set setting
curl -X POST http://your-domain/api/erp/settings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"key": "app.name", "value": "My ERP", "type": "string"}'
```

### System Health

```bash
# Check health
curl -X GET http://your-domain/api/erp/system-health \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID"
```

## Step 11: Monitor Queue

```bash
# Check queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Step 12: Setup Logging

Ensure logging is configured in `.env`:

```env
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_DAILY_DAYS=14
```

## Step 13: Performance Optimization

### Database Indexes

All required indexes should be created via migrations. Verify:

```bash
php artisan migrate:status
```

### Cache TTL Values

Default TTL values (can be adjusted in services):
- Reports: 3600 seconds (1 hour)
- Dashboard metrics: 300 seconds (5 minutes)
- Notifications unread count: 60 seconds (1 minute)
- Activity feed: 300 seconds (5 minutes)
- Settings: 3600 seconds (1 hour)

## Step 14: Security Checklist

- [ ] Ensure `.env` file is not in version control
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong `APP_KEY`
- [ ] Configure HTTPS
- [ ] Review and set proper permissions on storage and bootstrap/cache
- [ ] Enable rate limiting on API endpoints
- [ ] Review tenant isolation
- [ ] Enable audit logging

## Troubleshooting

### Cache Not Working

```bash
# Check cache driver
php artisan tinker
>>> Cache::get('test', 'not found'); // Should return 'not found'
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test'); // Should return 'value'
```

### Queue Not Processing

```bash
# Check queue connection
php artisan tinker
>>> config('queue.default'); // Should be 'redis' or 'database'

# Check if jobs table exists
php artisan migrate:status

# Restart queue worker
sudo supervisorctl restart erp-queue-worker:*
```

### Scheduled Tasks Not Running

```bash
# Verify cron is running
crontab -l

# Test scheduler manually
php artisan schedule:run

# Check logs
tail -f storage/logs/laravel.log
```

## Maintenance Commands

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Check system health
php artisan erp:check-system-health

# Generate scheduled reports manually
php artisan erp:generate-scheduled-reports

# Verify production setup
php artisan erp:verify-production-setup
```

## Next Steps

1. Configure email settings for report delivery
2. Set up monitoring and alerting
3. Configure backup strategy
4. Set up SSL certificates
5. Configure load balancing (if needed)
6. Set up CDN for static assets (if needed)




