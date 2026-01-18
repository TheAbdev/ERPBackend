<?php

namespace App\Console\Commands;

use App\Core\Models\Tenant;
use App\Core\Services\TenantContext;
use App\Modules\ERP\Services\RecurringInvoiceService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'erp:generate-recurring-invoices';

    protected $description = 'Generate invoices for recurring invoices that are due';

    public function handle(TenantContext $tenantContext, RecurringInvoiceService $recurringInvoiceService): int
    {
        $this->info('Starting recurring invoice generation...');

        $tenants = Tenant::where('status', 'active')->get();
        $totalGenerated = 0;

        foreach ($tenants as $tenant) {
            $tenantContext->setTenant($tenant);
            $this->info("Processing tenant: {$tenant->name}");

            try {
                $count = $recurringInvoiceService->generateDueInvoices();
                $totalGenerated += $count;
                $this->info("Generated {$count} invoices for tenant: {$tenant->name}");
            } catch (\Exception $e) {
                $this->error("Error processing tenant {$tenant->name}: {$e->getMessage()}");
            }
        }

        $this->info("Total invoices generated: {$totalGenerated}");

        return Command::SUCCESS;
    }
}
