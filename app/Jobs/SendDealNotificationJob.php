<?php

namespace App\Jobs;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Deal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDealNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $dealId,
        public int $tenantId,
        public string $action
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set tenant context
        $tenant = \App\Core\Models\Tenant::find($this->tenantId);
        if (! $tenant || ! $tenant->isActive()) {
            return;
        }

        app(TenantContext::class)->setTenant($tenant);

        // Load deal
        $deal = Deal::find($this->dealId);
        if (! $deal) {
            return;
        }

        // Notify assigned user and creator
        $usersToNotify = collect();

        if ($deal->assigned_to) {
            $usersToNotify->push(\App\Models\User::find($deal->assigned_to));
        }

        if ($deal->created_by && $deal->assigned_to !== $deal->created_by) {
            $usersToNotify->push($deal->creator);
        }

        foreach ($usersToNotify as $user) {
            if ($user && $user->tenant_id === $this->tenantId) {
                $user->notify(new \App\Notifications\DealStatusNotification($deal, $this->action));
            }
        }
    }
}
