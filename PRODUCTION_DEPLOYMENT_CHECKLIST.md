# Production Deployment Checklist - ERP & CRM System

## Pre-Deployment Verification

### Step 1: Verify and Install Export Libraries

#### 1.1 Install Required Packages

```bash
# Navigate to project directory
cd /path/to/your/project

# Install Excel export library
composer require maatwebsite/excel

# Install PDF export library (choose one)
composer require barryvdh/laravel-dompdf
# OR
composer require spatie/laravel-pdf

# Verify installation
composer show maatwebsite/excel
composer show barryvdh/laravel-dompdf
```

#### 1.2 Verify CSV Export (Built-in)

```bash
# Test CSV export via tinker
php artisan tinker
```

```php
// In tinker
$reportService = app(\App\Modules\ERP\Services\ReportService::class);
$data = ['test' => 'data', 'value' => 123];
$csv = $reportService->exportReport(1, 'csv'); // Use actual report ID
echo $csv;
```

#### 1.3 Verify Excel Export

```bash
# Test Excel export
php artisan tinker
```

```php
// In tinker
$reportService = app(\App\Modules\ERP\Services\ReportService::class);
try {
    $excel = $reportService->exportReport(1, 'excel'); // Use actual report ID
    echo "Excel export working";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

#### 1.4 Verify PDF Export

```bash
# Test PDF export
php artisan tinker
```

```php
// In tinker
$reportService = app(\App\Modules\ERP\Services\ReportService::class);
try {
    $pdf = $reportService->exportReport(1, 'pdf'); // Use actual report ID
    echo "PDF export working";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

#### 1.5 Verify ExportReportJob

```bash
# Test job dispatch
php artisan tinker
```

```php
// In tinker
$schedule = \App\Modules\ERP\Models\ReportSchedule::first();
if ($schedule) {
    \App\Jobs\ExportReportJob::dispatch(
        $schedule->id,
        $schedule->report_id,
        'csv',
        ['test@example.com']
    );
    echo "Job dispatched successfully";
}
```

**Verification Checklist:**
- [ ] maatwebsite/excel installed
- [ ] PDF library installed
- [ ] CSV export working
- [ ] Excel export working
- [ ] PDF export working
- [ ] ExportReportJob can be dispatched

---

### Step 2: Configure and Link Cron Jobs

#### 2.1 Verify Laravel Scheduler Configuration

```bash
# Check scheduled tasks
php artisan schedule:list
```

Expected output should show:
- `erp:generate-scheduled-reports` (every minute)
- `erp:check-system-health` (every 5 minutes)
- Webhook retry (every 10 minutes)
- `crm:check-activity-reminders` (every 5 minutes)

#### 2.2 Test Scheduled Tasks Manually

```bash
# Test report generation
php artisan erp:generate-scheduled-reports

# Test system health check
php artisan erp:check-system-health

# Test scheduler run
php artisan schedule:run
```

#### 2.3 Setup System Cron Job

```bash
# Edit crontab
crontab -e

# Add this line (replace /path/to/your/project with actual path)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1

# Verify crontab
crontab -l

# Check cron service is running (Linux)
sudo systemctl status cron
# OR
sudo service cron status
```

#### 2.4 Verify Cron Job Execution

```bash
# Check Laravel logs for scheduler execution
tail -f storage/logs/laravel.log | grep "Running scheduled command"

# Or check system logs
grep CRON /var/log/syslog | tail -20
```

**Verification Checklist:**
- [ ] `schedule:list` shows all tasks
- [ ] Manual task execution works
- [ ] Cron job added to crontab
- [ ] Cron service is running
- [ ] Scheduler runs every minute (check logs)

---

### Step 3: Setup and Verify Queue Workers

#### 3.1 Configure Queue Connection

```bash
# Verify .env configuration
cat .env | grep QUEUE_CONNECTION
# Should show: QUEUE_CONNECTION=redis (or database)

# Verify Redis connection (if using Redis)
php artisan tinker
```

```php
// In tinker
\Illuminate\Support\Facades\Redis::connection()->ping();
// Should return: "PONG"
```

#### 3.2 Test Queue Workers (Development)

```bash
# Start queue worker manually (for testing)
php artisan queue:work --tries=3 --timeout=300 --verbose

# In another terminal, dispatch a test job
php artisan tinker
```

```php
// In tinker
\App\Jobs\ExportReportJob::dispatch(1, 1, 'csv', []);
// Check the queue worker terminal for job processing
```

#### 3.3 Setup Supervisor for Production

```bash
# Install supervisor (if not installed)
sudo apt-get install supervisor  # Debian/Ubuntu
# OR
sudo yum install supervisor      # CentOS/RHEL

# Copy supervisor configuration
sudo cp supervisor-queue-worker.conf.example /etc/supervisor/conf.d/erp-queue-worker.conf

# Edit configuration with actual paths
sudo nano /etc/supervisor/conf.d/erp-queue-worker.conf
```

Update these paths in the config:
- `/path/to/your/project` → actual project path
- `www-data` → your web server user (nginx/apache user)

#### 3.4 Start Supervisor Workers

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start all queue workers
sudo supervisorctl start erp-queue-worker:*

# Check status
sudo supervisorctl status

# View logs
tail -f /path/to/your/project/storage/logs/queue-worker.log
```

#### 3.5 Verify Queue Processing

```bash
# Check queue statistics
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed

# Test job dispatch and processing
php artisan tinker
```

```php
// In tinker - dispatch a test job
\App\Jobs\ExportReportJob::dispatch(1, 1, 'csv', ['test@example.com']);

// Check if job is processed (check supervisor logs or queue:monitor)
```

**Verification Checklist:**
- [ ] Queue connection configured (Redis/Database)
- [ ] Queue workers can process jobs manually
- [ ] Supervisor configuration created
- [ ] Supervisor workers running
- [ ] Jobs are being processed
- [ ] Failed jobs can be retried

---

### Step 4: Verify Caching, Indexing, and Tenant Isolation

#### 4.1 Clear All Caches

```bash
# Clear all caches (including custom ERP/CRM caches)
php artisan erp:clear-all-caches

# Verify caches are cleared
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 4.2 Configure Redis Cache

```bash
# Verify .env configuration
cat .env | grep CACHE_DRIVER
# Should show: CACHE_DRIVER=redis

# Test cache connection
php artisan tinker
```

```php
// In tinker
\Illuminate\Support\Facades\Cache::put('test', 'value', 60);
\Illuminate\Support\Facades\Cache::get('test');
// Should return: "value"
```

#### 4.3 Verify Database Indexes

```bash
# Run migration to add performance indexes (if not already run)
php artisan migrate

# Check indexes via MySQL
mysql -u your_user -p your_database
```

```sql
-- In MySQL
SHOW INDEX FROM erp_notifications;
SHOW INDEX FROM erp_activity_feed;
SHOW INDEX FROM erp_webhook_deliveries;
SHOW INDEX FROM erp_sales_invoices;
SHOW INDEX FROM erp_purchase_invoices;
SHOW INDEX FROM erp_payments;

-- Or use Laravel command
php artisan tinker
```

```php
// In tinker
$connection = \Illuminate\Support\Facades\DB::connection();
$indexes = $connection->select("SHOW INDEX FROM erp_notifications");
print_r($indexes);
```

#### 4.4 Verify Tenant Isolation

```bash
# Run tenant isolation check
php artisan erp:check-tenant-isolation

# Expected output: All tables properly isolated
```

**Verification Checklist:**
- [ ] All caches cleared
- [ ] Redis cache working
- [ ] Database indexes created
- [ ] Tenant isolation verified
- [ ] No records without tenant_id

---

### Step 5: Verify API Routes and Integrations

#### 5.1 Test Reports API

```bash
# Get authentication token first (replace with actual credentials)
TOKEN="your_auth_token"
TENANT_ID="your_tenant_id"

# Test reports list
curl -X GET "http://your-domain/api/erp/reports" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Test report generation
curl -X GET "http://your-domain/api/erp/reports/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Test report export
curl -X GET "http://your-domain/api/erp/reports/1/export?format=csv" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"
```

#### 5.2 Test Dashboard API

```bash
# Test dashboard metrics
curl -X GET "http://your-domain/api/erp/dashboard/metrics" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Test recent activities
curl -X GET "http://your-domain/api/erp/dashboard/recent-activities" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Test module summary
curl -X GET "http://your-domain/api/erp/dashboard/module-summary?module=ERP" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"
```

#### 5.3 Test System Settings API

```bash
# List settings
curl -X GET "http://your-domain/api/erp/settings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Get specific setting
curl -X GET "http://your-domain/api/erp/settings/app.name" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Create/Update setting
curl -X POST "http://your-domain/api/erp/settings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"key": "app.name", "value": "My ERP System", "type": "string"}'
```

#### 5.4 Test System Health API

```bash
# Check system health
curl -X GET "http://your-domain/api/erp/system-health" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Trigger health check
curl -X POST "http://your-domain/api/erp/system-health/check" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"
```

#### 5.5 Test Notifications API

```bash
# List notifications
curl -X GET "http://your-domain/api/erp/notifications" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Mark notification as read
curl -X POST "http://your-domain/api/erp/notifications/mark-read" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"notification_id": 1}'

# Get unread count
curl -X GET "http://your-domain/api/erp/notifications/unread-count" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"
```

#### 5.6 Test Webhooks API

```bash
# List webhooks
curl -X GET "http://your-domain/api/erp/webhooks" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Create webhook
curl -X POST "http://your-domain/api/erp/webhooks" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com/webhook",
    "module": "ERP",
    "event_types": ["invoice.created", "payment.received"],
    "is_active": true
  }'
```

#### 5.7 Test Activity Feed API

```bash
# List activity feed
curl -X GET "http://your-domain/api/erp/activity-feed" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"

# Get entity activities
curl -X GET "http://your-domain/api/erp/activity-feed/entity/App\\Modules\\ERP\\Models\\SalesInvoice/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json"
```

#### 5.8 Test Workflow Integration

```bash
# Test workflow approval (create invoice and trigger workflow)
php artisan tinker
```

```php
// In tinker - test workflow integration
$tenant = \App\Core\Models\Tenant::first();
app(\App\Core\Services\TenantContext::class)->setTenant($tenant);

// Create a sales invoice (this should trigger workflow if configured)
$invoiceService = app(\App\Modules\ERP\Services\InvoiceService::class);
// ... create invoice and verify workflow instance is created

// Check workflow instance
$workflowInstance = \App\Modules\ERP\Models\WorkflowInstance::latest()->first();
echo "Workflow Status: " . $workflowInstance->status;
```

#### 5.9 Test Notifications and Activity Feed Integration

```bash
php artisan tinker
```

```php
// In tinker - test notification and activity feed
$tenant = \App\Core\Models\Tenant::first();
app(\App\Core\Services\TenantContext::class)->setTenant($tenant);

// Create an entity (e.g., invoice) and verify notifications/activity feed
$invoice = \App\Modules\ERP\Models\SalesInvoice::first();
if ($invoice) {
    // Check notifications
    $notifications = \App\Modules\ERP\Models\Notification::where('entity_type', get_class($invoice))
        ->where('entity_id', $invoice->id)
        ->get();
    echo "Notifications: " . $notifications->count();
    
    // Check activity feed
    $activities = \App\Modules\ERP\Models\ActivityFeed::where('entity_type', get_class($invoice))
        ->where('entity_id', $invoice->id)
        ->get();
    echo "Activities: " . $activities->count();
}
```

**Verification Checklist:**
- [ ] Reports API working
- [ ] Dashboard API working
- [ ] System Settings API working
- [ ] System Health API working
- [ ] Notifications API working
- [ ] Webhooks API working
- [ ] Activity Feed API working
- [ ] Workflow integration triggering
- [ ] Notifications being created
- [ ] Activity feed logging events

---

### Step 6: Final System Verification

#### 6.1 Run Production Setup Verification

```bash
# Run comprehensive verification
php artisan erp:verify-production-setup

# Expected output: All checks passed
```

#### 6.2 Test Scheduled Report Execution

```bash
# Create a test report schedule
php artisan tinker
```

```php
// In tinker
$tenant = \App\Core\Models\Tenant::first();
app(\App\Core\Services\TenantContext::class)->setTenant($tenant);

// Create a report
$report = \App\Modules\ERP\Models\Report::create([
    'tenant_id' => $tenant->id,
    'name' => 'Test Report',
    'type' => 'trial_balance',
    'module' => 'ERP',
    'is_active' => true,
    'created_by' => 1,
]);

// Create a schedule (runs every minute for testing)
$schedule = \App\Modules\ERP\Models\ReportSchedule::create([
    'tenant_id' => $tenant->id,
    'report_id' => $report->id,
    'cron_expression' => '* * * * *', // Every minute
    'next_run_at' => now(),
    'is_active' => true,
    'format' => 'csv',
    'recipients' => ['test@example.com'],
]);

// Run report generation manually
\Artisan::call('erp:generate-scheduled-reports');

// Check if report was generated
$schedule->refresh();
echo "Last run: " . $schedule->last_run_at;
```

#### 6.3 Test Webhook Delivery and Retry

```bash
# Create a test webhook
php artisan tinker
```

```php
// In tinker
$tenant = \App\Core\Models\Tenant::first();
app(\App\Core\Services\TenantContext::class)->setTenant($tenant);

// Create webhook
$webhook = \App\Modules\ERP\Models\Webhook::create([
    'tenant_id' => $tenant->id,
    'url' => 'https://webhook.site/your-unique-url', // Use webhook.site for testing
    'module' => 'ERP',
    'event_types' => ['invoice.created'],
    'is_active' => true,
    'secret' => 'test-secret',
]);

// Trigger an event (create invoice)
$invoiceService = app(\App\Modules\ERP\Services\InvoiceService::class);
// ... create invoice to trigger webhook

// Check webhook delivery
$delivery = \App\Modules\ERP\Models\WebhookDelivery::latest()->first();
echo "Status: " . $delivery->status;
echo "Response: " . $delivery->response_code;

// Test retry mechanism
$webhookService = app(\App\Modules\ERP\Services\WebhookService::class);
$retried = $webhookService->retryFailedDeliveries();
echo "Retried: " . $retried . " deliveries";
```

#### 6.4 Test Multi-Tenant Isolation

```bash
# Test tenant isolation
php artisan tinker
```

```php
// In tinker
$tenant1 = \App\Core\Models\Tenant::first();
$tenant2 = \App\Core\Models\Tenant::skip(1)->first();

if ($tenant1 && $tenant2) {
    // Set tenant 1
    app(\App\Core\Services\TenantContext::class)->setTenant($tenant1);
    $reports1 = \App\Modules\ERP\Models\Report::count();
    
    // Set tenant 2
    app(\App\Core\Services\TenantContext::class)->setTenant($tenant2);
    $reports2 = \App\Modules\ERP\Models\Report::count();
    
    echo "Tenant 1 reports: " . $reports1;
    echo "Tenant 2 reports: " . $reports2;
    
    // Verify isolation (should be different)
    if ($reports1 !== $reports2) {
        echo "✓ Tenant isolation working";
    }
}
```

#### 6.5 Test User Permissions

```bash
# Test permissions
php artisan tinker
```

```php
// In tinker
$user = \App\Models\User::first();
if ($user) {
    // Test report permissions
    $canView = $user->can('viewAny', \App\Modules\ERP\Models\Report::class);
    echo "Can view reports: " . ($canView ? 'Yes' : 'No');
    
    // Test settings permissions
    $canManageSettings = $user->can('viewAny', \App\Modules\ERP\Models\SystemSetting::class);
    echo "Can manage settings: " . ($canManageSettings ? 'Yes' : 'No');
}
```

#### 6.6 Run Tests (Optional)

```bash
# Run unit tests
php artisan test --testsuite=Unit

# Run integration tests
php artisan test --testsuite=Integration

# Run all tests
php artisan test
```

**Verification Checklist:**
- [ ] Production setup verification passed
- [ ] Scheduled reports generating
- [ ] Report exports working
- [ ] Webhook delivery working
- [ ] Webhook retry mechanism working
- [ ] Multi-tenant isolation verified
- [ ] User permissions working
- [ ] All tests passing (if applicable)

---

### Step 7: Environment and Deployment Checks

#### 7.1 Verify .env Configuration

```bash
# Check critical .env settings
cat .env | grep -E "APP_ENV|APP_DEBUG|CACHE_DRIVER|QUEUE_CONNECTION|DB_|MAIL_|FILESYSTEM"

# Expected values:
# APP_ENV=production
# APP_DEBUG=false
# CACHE_DRIVER=redis
# QUEUE_CONNECTION=redis
# DB_* (database credentials)
# MAIL_* (mail configuration)
# FILESYSTEM_DISK=s3 (or local)
```

#### 7.2 Verify Database Connection

```bash
# Test database connection
php artisan tinker
```

```php
// In tinker
\Illuminate\Support\Facades\DB::connection()->getPdo();
echo "Database connected successfully";
```

#### 7.3 Verify Mail Configuration

```bash
# Test mail configuration
php artisan tinker
```

```php
// In tinker
try {
    \Illuminate\Support\Facades\Mail::raw('Test email', function ($message) {
        $message->to('test@example.com')
                ->subject('Test Email');
    });
    echo "Mail configuration working";
} catch (\Exception $e) {
    echo "Mail error: " . $e->getMessage();
}
```

#### 7.4 Verify Storage Configuration

```bash
# Check storage disk
php artisan tinker
```

```php
// In tinker
$disk = \Illuminate\Support\Facades\Storage::disk('local');
$disk->put('test.txt', 'test content');
$content = $disk->get('test.txt');
$disk->delete('test.txt');
echo "Storage working: " . ($content === 'test content' ? 'Yes' : 'No');
```

#### 7.5 Verify Supervisor Status

```bash
# Check supervisor status
sudo supervisorctl status

# Check supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Check queue worker logs
tail -f /path/to/your/project/storage/logs/queue-worker.log
```

#### 7.6 Verify Cron Jobs

```bash
# Check crontab
crontab -l

# Check cron service
sudo systemctl status cron
# OR
sudo service cron status

# Check cron execution logs
grep CRON /var/log/syslog | tail -20
```

#### 7.7 Verify File Permissions

```bash
# Set proper permissions
sudo chown -R www-data:www-data /path/to/your/project/storage
sudo chown -R www-data:www-data /path/to/your/project/bootstrap/cache
sudo chmod -R 775 /path/to/your/project/storage
sudo chmod -R 775 /path/to/your/project/bootstrap/cache
```

#### 7.8 Final Pre-Launch Checklist

```bash
# Run all verification commands
php artisan erp:verify-production-setup
php artisan erp:check-tenant-isolation
php artisan erp:check-system-health
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Final Verification Checklist:**
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] CACHE_DRIVER=redis
- [ ] QUEUE_CONNECTION=redis
- [ ] Database credentials correct
- [ ] Mail configuration working
- [ ] Storage accessible and writable
- [ ] Supervisor running
- [ ] Cron jobs active
- [ ] File permissions correct
- [ ] All caches optimized

---

## Post-Deployment Monitoring

### Monitor Queue Workers

```bash
# Watch queue statistics
watch -n 5 'php artisan queue:monitor'

# Check failed jobs regularly
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Monitor System Health

```bash
# Check system health regularly
php artisan erp:check-system-health

# Monitor logs
tail -f storage/logs/laravel.log
```

### Monitor Scheduled Tasks

```bash
# Check scheduler execution
grep "Running scheduled command" storage/logs/laravel.log | tail -20

# Verify scheduled reports are generating
php artisan tinker
```

```php
// In tinker
$schedules = \App\Modules\ERP\Models\ReportSchedule::where('is_active', true)->get();
foreach ($schedules as $schedule) {
    echo "Schedule {$schedule->id}: Last run: {$schedule->last_run_at}, Next run: {$schedule->next_run_at}\n";
}
```

---

## Troubleshooting

### Queue Workers Not Processing

```bash
# Restart supervisor workers
sudo supervisorctl restart erp-queue-worker:*

# Check queue connection
php artisan tinker
>>> config('queue.default');
```

### Scheduled Tasks Not Running

```bash
# Test scheduler manually
php artisan schedule:run

# Check cron service
sudo systemctl status cron

# Verify crontab entry
crontab -l
```

### Cache Not Working

```bash
# Test cache
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');

# Clear and rebuild cache
php artisan erp:clear-all-caches
php artisan config:cache
```

### Reports Not Generating

```bash
# Check report schedules
php artisan tinker
>>> \App\Modules\ERP\Models\ReportSchedule::where('is_active', true)->get();

# Run manually
php artisan erp:generate-scheduled-reports
```

---

## Support and Documentation

- **Production Setup Guide**: `PRODUCTION_SETUP.md`
- **Quick Commands**: `QUICK_SETUP_COMMANDS.md`
- **Setup Summary**: `SETUP_SUMMARY.md`

For issues or questions, check the logs:
- Application logs: `storage/logs/laravel.log`
- Queue logs: `storage/logs/queue-worker.log`
- Supervisor logs: `/var/log/supervisor/supervisord.log`

