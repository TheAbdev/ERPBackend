<?php

namespace App\Console\Commands;

use App\Core\Models\Tenant;
use App\Core\Services\TenantContext;
use App\Modules\CRM\Services\EmailSyncService;
use Illuminate\Console\Command;

class SyncEmailAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:sync-email-accounts 
                            {--tenant= : Sync for specific tenant ID}
                            {--account= : Sync for specific email account ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync emails from IMAP accounts';

    /**
     * Execute the console command.
     */
    public function handle(TenantContext $tenantContext, EmailSyncService $syncService): int
    {
        $tenantId = $this->option('tenant');
        $accountId = $this->option('account');

        if ($tenantId) {
            // Sync for specific tenant
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return Command::FAILURE;
            }

            $tenantContext->setTenant($tenant);
            $synced = $syncService->syncAllAccounts();
            $this->info("Synced {$synced} emails for tenant {$tenant->name}.");
            return Command::SUCCESS;
        }

        if ($accountId) {
            // Sync for specific account (requires tenant context)
            $this->error("Account-specific sync requires tenant context. Use --tenant option.");
            return Command::FAILURE;
        }

        // Sync for all active tenants
        $tenants = Tenant::where('status', 'active')->get();
        $totalSynced = 0;

        foreach ($tenants as $tenant) {
            try {
                $tenantContext->setTenant($tenant);
                $synced = $syncService->syncAllAccounts();
                $totalSynced += $synced;
                $this->info("Synced {$synced} emails for tenant {$tenant->name}.");
            } catch (\Exception $e) {
                $this->error("Failed to sync emails for tenant {$tenant->name}: " . $e->getMessage());
            }
        }

        $this->info("Total synced: {$totalSynced} emails across all tenants.");
        return Command::SUCCESS;
    }
}



































