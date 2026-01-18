<?php

namespace App\Jobs;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendActivityReminderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $activityId,
        public int $tenantId
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

        // Load activity
        $activity = Activity::find($this->activityId);
        if (! $activity || $activity->status === 'completed' || $activity->status === 'canceled') {
            return;
        }

        // Check if already notified (idempotent check)
        $alreadyNotified = \App\Core\Models\Notification::where('tenant_id', $this->tenantId)
            ->where('notifiable_id', $activity->assigned_to ?? $activity->created_by)
            ->where('type', \App\Notifications\ActivityDueNotification::class)
            ->whereJsonContains('data->activity_id', $this->activityId)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        // Send notification to assigned user or creator
        $user = $activity->assigned_to
            ? \App\Models\User::find($activity->assigned_to)
            : $activity->creator;

        if ($user && $user->tenant_id === $this->tenantId) {
            $user->notify(new \App\Notifications\ActivityDueNotification($activity));
        }
    }
}
