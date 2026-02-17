<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Core\Models\Tenant;
use App\Core\Services\TenantContext;
use App\Modules\HR\Services\ZkBioTimeAttendanceSyncService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');




Schedule::command('crm:check-activity-reminders')->everyFiveMinutes();


Schedule::command('crm:sync-email-accounts')
    ->name('crm-sync-email-accounts')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// ============================================
// ERP Scheduled Tasks
// ============================================

Schedule::command('erp:generate-scheduled-reports')
    ->name('erp-generate-scheduled-reports')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();


Schedule::command('erp:check-system-health')
    ->name('erp-check-system-health')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Retry failed webhook deliveries every 10 minutes
Schedule::call(function () {
    $tenants = \App\Core\Models\Tenant::where('status', 'active')->get();
    foreach ($tenants as $tenant) {
        app(\App\Core\Services\TenantContext::class)->setTenant($tenant);
        app(\App\Modules\ERP\Services\WebhookService::class)->retryFailedDeliveries();
    }
})->name('erp-retry-webhook-deliveries')->everyTenMinutes()->withoutOverlapping();

// Generate recurring invoices daily
Schedule::command('erp:generate-recurring-invoices')
    ->name('erp-generate-recurring-invoices')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

// Check and reorder products daily
Schedule::command('erp:check-reorder-rules')
    ->name('erp-check-reorder-rules')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('hr:sync-zkbiotime-attendance')
    ->name('hr-sync-zkbiotime-attendance')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Artisan::command('hr:sync-zkbiotime-attendance {--tenant= : Tenant ID} {--from= : Start datetime} {--to= : End datetime} {--page-size= : Page size}', function () {
    $tenantId = $this->option('tenant');
    $from = $this->option('from') ? Carbon::parse($this->option('from')) : null;
    $to = $this->option('to') ? Carbon::parse($this->option('to')) : null;
    $pageSize = (int) ($this->option('page-size') ?: config('zkbiotime.default_page_size', 200));

    $tenants = $tenantId
        ? Tenant::where('status', 'active')->where('id', $tenantId)->get()
        : Tenant::where('status', 'active')->get();

    if ($tenants->isEmpty()) {
        $this->error('No active tenants found for sync.');
        return 1;
    }

    $syncService = app(ZkBioTimeAttendanceSyncService::class);

    foreach ($tenants as $tenant) {
        app(TenantContext::class)->setTenant($tenant);

        try {
            $result = $syncService->syncTenant($tenant, $from, $to, $pageSize);
            $this->info(sprintf(
                'Tenant %d: processed=%d created=%d skipped=%d missing_employees=%d',
                $result['tenant_id'],
                $result['processed'],
                $result['created'],
                $result['skipped'],
                $result['missing_employees']
            ));
        } catch (\Throwable $e) {
            $this->error('Tenant ' . $tenant->id . ' sync failed: ' . $e->getMessage());
        }
    }

    return 0;
})->purpose('Sync attendance records from ZKBioTime into HR attendance records');
