<?php

namespace App\Console\Commands;

use App\Core\Models\Tenant;
use App\Core\Services\TenantContext;
use App\Modules\ERP\Services\ReorderService;
use Illuminate\Console\Command;

class CheckReorderRules extends Command
{
    protected $signature = 'erp:check-reorder-rules';

    protected $description = 'Check reorder rules and create purchase orders for products that need reordering';

    public function handle(TenantContext $tenantContext, ReorderService $reorderService): int
    {
        $this->info('Starting reorder rules check...');

        $tenants = Tenant::where('status', 'active')->get();
        $totalOrdersCreated = 0;

        foreach ($tenants as $tenant) {
            $tenantContext->setTenant($tenant);
            $this->info("Processing tenant: {$tenant->name}");

            try {
                $orders = $reorderService->checkAndReorder();
                $count = count($orders);
                $totalOrdersCreated += $count;
                $this->info("Created {$count} purchase orders for tenant: {$tenant->name}");
            } catch (\Exception $e) {
                $this->error("Error processing tenant {$tenant->name}: {$e->getMessage()}");
            }
        }

        $this->info("Total purchase orders created: {$totalOrdersCreated}");

        return Command::SUCCESS;
    }
}
