<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// CRM Scheduled Tasks
Schedule::command('crm:check-activity-reminders')->everyFiveMinutes();

// Sync email accounts every 5 minutes
Schedule::command('crm:sync-email-accounts')
    ->name('crm-sync-email-accounts')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// ERP Scheduled Tasks
// Check for scheduled reports every minute
Schedule::command('erp:generate-scheduled-reports')
    ->name('erp-generate-scheduled-reports')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Check system health every 5 minutes
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
