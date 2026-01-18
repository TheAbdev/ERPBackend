<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Command to verify production setup including caching, indexing, and tenant isolation.
 */
class VerifyProductionSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:verify-production-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify production setup: caching, indexing, tenant isolation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Verifying production setup...');
        $this->newLine();

        $checks = [
            'Cache Configuration' => $this->checkCache(),
            'Database Indexes' => $this->checkIndexes(),
            'Tenant Isolation' => $this->checkTenantIsolation(),
            'Queue Configuration' => $this->checkQueue(),
            'Required Tables' => $this->checkTables(),
        ];

        $allPassed = true;
        foreach ($checks as $check => $passed) {
            $status = $passed ? '✓' : '✗';
            $color = $passed ? 'green' : 'red';
            $this->line("<fg={$color}>{$status} {$check}</>");
            if (!$passed) {
                $allPassed = false;
            }
        }

        $this->newLine();
        if ($allPassed) {
            $this->info('All production checks passed!');
            return Command::SUCCESS;
        } else {
            $this->error('Some production checks failed. Please review the issues above.');
            return Command::FAILURE;
        }
    }

    /**
     * Check cache configuration.
     *
     * @return bool
     */
    protected function checkCache(): bool
    {
        try {
            $driver = config('cache.default');
            if ($driver === 'array') {
                $this->warn('  Warning: Using array cache driver (not suitable for production)');
                return false;
            }

            // Test cache
            $key = 'production_setup_test_' . time();
            Cache::put($key, 'test', 60);
            $value = Cache::get($key);
            Cache::forget($key);

            return $value === 'test';
        } catch (\Exception $e) {
            $this->error("  Error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Check database indexes.
     *
     * @return bool
     */
    protected function checkIndexes(): bool
    {
        $requiredIndexes = [
            'erp_notifications' => ['idx_notif_tenant_user_read', 'idx_notif_entity'],
            'erp_activity_feed' => ['idx_activity_tenant_created', 'idx_activity_entity'],
            'erp_webhook_deliveries' => ['idx_delivery_status_attempts', 'idx_delivery_tenant_webhook'],
            'erp_sales_invoices' => ['idx_sales_inv_tenant_status_date'],
            'erp_purchase_invoices' => ['idx_purch_inv_tenant_status_date'],
            'erp_payments' => ['idx_payment_tenant_date', 'idx_payment_reference'],
        ];

        $allPresent = true;
        foreach ($requiredIndexes as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue; // Table doesn't exist yet, skip
            }

            foreach ($indexes as $index) {
                if (!$this->hasIndex($table, $index)) {
                    $this->warn("  Missing index: {$table}.{$index}");
                    $allPresent = false;
                }
            }
        }

        return $allPresent;
    }

    /**
     * Check tenant isolation.
     *
     * @return bool
     */
    protected function checkTenantIsolation(): bool
    {
        // Check if models have tenant_id column
        $models = [
            'erp_reports',
            'erp_notifications',
            'erp_activity_feed',
            'erp_webhooks',
            'erp_system_settings',
        ];

        $allIsolated = true;
        foreach ($models as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'tenant_id')) {
                $this->warn("  Missing tenant_id in: {$table}");
                $allIsolated = false;
            }
        }

        return $allIsolated;
    }

    /**
     * Check queue configuration.
     *
     * @return bool
     */
    protected function checkQueue(): bool
    {
        $driver = config('queue.default');
        if ($driver === 'sync') {
            $this->warn('  Warning: Using sync queue driver (not suitable for production)');
            return false;
        }

        // Check if jobs table exists
        if (!Schema::hasTable('jobs')) {
            $this->warn('  Warning: jobs table does not exist. Run migrations.');
            return false;
        }

        return true;
    }

    /**
     * Check required tables exist.
     *
     * @return bool
     */
    protected function checkTables(): bool
    {
        $requiredTables = [
            'tenants',
            'users',
            'erp_reports',
            'erp_report_schedules',
            'erp_notifications',
            'erp_activity_feed',
            'erp_webhooks',
            'erp_system_settings',
            'erp_system_health',
        ];

        $allPresent = true;
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("  Missing table: {$table}");
                $allPresent = false;
            }
        }

        return $allPresent;
    }

    /**
     * Check if index exists.
     *
     * @param  string  $table
     * @param  string  $index
     * @return bool
     */
    protected function hasIndex(string $table, string $index): bool
    {
        try {
            $connection = DB::connection();
            $database = $connection->getDatabaseName();
            $indexes = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}

