<?php

namespace App\Console\Commands;

use App\Core\Services\PermissionCacheService;
use App\Core\Services\TenantContext;
use App\Core\Models\Tenant;
use Illuminate\Console\Command;

class WarmUpPermissionCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm-permissions
                            {--tenant= : Specific tenant ID to warm up}
                            {--all : Warm up cache for all tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up permission and role cache for improved performance';

    /**
     * Execute the console command.
     */
    public function handle(PermissionCacheService $permissionCacheService, TenantContext $tenantContext): int
    {
        $tenantId = $this->option('tenant');
        $allTenants = $this->option('all');

        if ($tenantId) {
            // Warm up specific tenant
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return Command::FAILURE;
            }

            $tenantContext->setTenant($tenant);
            $count = $permissionCacheService->warmUpTenantCache($tenantId);
            $this->info("Warmed up permission cache for tenant '{$tenant->name}' ({$count} users).");
        } elseif ($allTenants) {
            // Warm up all tenants
            $tenants = Tenant::where('status', 'active')->get();
            $totalUsers = 0;

            foreach ($tenants as $tenant) {
                $tenantContext->setTenant($tenant);
                $count = $permissionCacheService->warmUpTenantCache($tenant->id);
                $totalUsers += $count;
                $this->info("Warmed up cache for tenant '{$tenant->name}' ({$count} users).");
            }

            $this->info("Total: {$totalUsers} users across {$tenants->count()} tenants.");
        } else {
            // Warm up current tenant (if in tenant context)
            $currentTenantId = $tenantContext->getTenantId();
            if (! $currentTenantId) {
                $this->error('No tenant context available. Use --tenant or --all option.');
                return Command::FAILURE;
            }

            $count = $permissionCacheService->warmUpTenantCache($currentTenantId);
            $this->info("Warmed up permission cache for current tenant ({$count} users).");
        }

        return Command::SUCCESS;
    }
}
