<?php

namespace App\Console\Commands;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Services\SystemMonitoringService;
use Illuminate\Console\Command;

/**
 * Command to check system health for all tenants.
 */
class CheckSystemHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:check-system-health {--tenant= : Specific tenant ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check system health metrics for tenants';

    /**
     * Execute the console command.
     */
    public function handle(
        TenantContext $tenantContext,
        SystemMonitoringService $monitoringService
    ): int {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            // Check specific tenant
            $tenant = \App\Core\Models\Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return Command::FAILURE;
            }

            $tenantContext->setTenant($tenant);
            $health = $monitoringService->checkHealth($tenant->id);

            $this->displayHealthStatus($health, $tenant->name);
        } else {
            // Check all active tenants
            $tenants = \App\Core\Models\Tenant::where('status', 'active')->get();

            if ($tenants->isEmpty()) {
                $this->info('No active tenants found.');
                return Command::SUCCESS;
            }

            $this->info("Checking health for {$tenants->count()} tenant(s)...\n");

            foreach ($tenants as $tenant) {
                $tenantContext->setTenant($tenant);
                $health = $monitoringService->checkHealth($tenant->id);
                $this->displayHealthStatus($health, $tenant->name);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Display health status.
     *
     * @param  \App\Modules\ERP\Models\SystemHealth  $health
     * @param  string  $tenantName
     * @return void
     */
    protected function displayHealthStatus($health, string $tenantName): void
    {
        $status = match ($health->status) {
            'healthy' => '✓',
            'warning' => '⚠',
            'critical' => '✗',
            default => '?',
        };

        $this->line("{$status} {$tenantName} - Status: {$health->status}");
        $this->line("  CPU: {$health->cpu_usage}% | Memory: {$health->memory_usage}% | Disk: {$health->disk_usage}%");
        $this->line("  Connections: {$health->active_connections} | Queue: {$health->queue_size}");
        $this->newLine();
    }
}

