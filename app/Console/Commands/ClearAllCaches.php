<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Command to clear all caches including custom ERP/CRM caches.
 */
class ClearAllCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:clear-all-caches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all caches including ERP/CRM specific caches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Clearing all caches...');

        // Clear Laravel caches
        Artisan::call('cache:clear');
        $this->info('✓ Application cache cleared');

        Artisan::call('config:clear');
        $this->info('✓ Configuration cache cleared');

        Artisan::call('route:clear');
        $this->info('✓ Route cache cleared');

        Artisan::call('view:clear');
        $this->info('✓ View cache cleared');

        // Clear custom ERP/CRM caches
        $this->clearCustomCaches();

        $this->newLine();
        $this->info('All caches cleared successfully!');

        return Command::SUCCESS;
    }

    /**
     * Clear custom ERP/CRM caches.
     *
     * @return void
     */
    protected function clearCustomCaches(): void
    {
        // Get all tenants
        $tenants = \App\Core\Models\Tenant::all();

        foreach ($tenants as $tenant) {
            // Clear dashboard metrics cache
            Cache::forget("dashboard_metrics_{$tenant->id}");

            // Clear notification unread counts
            $users = \App\Models\User::where('tenant_id', $tenant->id)->pluck('id');
            foreach ($users as $userId) {
                Cache::forget("notifications_unread_count_{$tenant->id}_{$userId}");
            }

            // Clear activity feed cache
            for ($limit = 10; $limit <= 100; $limit += 10) {
                Cache::forget("activity_feed_recent_{$tenant->id}_{$limit}");
            }

            // Clear settings cache
            $settings = \App\Modules\ERP\Models\SystemSetting::where('tenant_id', $tenant->id)->pluck('key');
            foreach ($settings as $key) {
                Cache::forget("setting_{$tenant->id}_{$key}");
            }

            // Clear report caches (would need to iterate through reports)
            // This is handled by cache tags if using Redis with tags
        }

        $this->info('✓ Custom ERP/CRM caches cleared');
    }
}




