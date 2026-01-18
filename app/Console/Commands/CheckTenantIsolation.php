<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Command to verify tenant isolation across all models.
 */
class CheckTenantIsolation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:check-tenant-isolation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify tenant isolation across all ERP and CRM models';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking tenant isolation...');
        $this->newLine();

        $tables = [
            // ERP Tables
            'erp_reports',
            'erp_report_schedules',
            'erp_notifications',
            'erp_activity_feed',
            'erp_webhooks',
            'erp_webhook_deliveries',
            'erp_system_settings',
            'erp_system_health',
            'erp_sales_invoices',
            'erp_purchase_invoices',
            'erp_payments',
            'erp_journal_entries',
            'erp_fixed_assets',
            'erp_workflows',
            'erp_workflow_instances',
            // CRM Tables
            'leads',
            'contacts',
            'accounts',
            'deals',
            'activities',
            'notes',
        ];

        $issues = [];
        $passed = 0;

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("  ⚠ Table '{$table}' does not exist (may not be migrated yet)");
                continue;
            }

            if (!Schema::hasColumn($table, 'tenant_id')) {
                $issues[] = "Missing tenant_id column in '{$table}'";
                $this->error("  ✗ {$table}: Missing tenant_id column");
            } else {
                // Check for records without tenant_id
                $count = DB::table($table)->whereNull('tenant_id')->count();
                if ($count > 0) {
                    $issues[] = "Found {$count} record(s) without tenant_id in '{$table}'";
                    $this->warn("  ⚠ {$table}: {$count} record(s) without tenant_id");
                } else {
                    $this->info("  ✓ {$table}: Properly isolated");
                    $passed++;
                }
            }
        }

        $this->newLine();
        if (empty($issues)) {
            $this->info("✓ All tables are properly tenant-isolated!");
            return Command::SUCCESS;
        } else {
            $this->error("✗ Found " . count($issues) . " tenant isolation issue(s):");
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            return Command::FAILURE;
        }
    }
}

