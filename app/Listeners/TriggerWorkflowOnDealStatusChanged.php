<?php

namespace App\Listeners;

use App\Events\DealStatusChanged;
use App\Modules\CRM\Services\Workflows\WorkflowEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TriggerWorkflowOnDealStatusChanged implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(DealStatusChanged $event): void
    {
        app(WorkflowEngineService::class)->trigger(
            'deal.status_changed',
            $event->deal,
            [
                'old_status' => $event->oldStatus,
                'new_status' => $event->deal->status,
                'action' => $event->action,
            ]
        );
    }
}

