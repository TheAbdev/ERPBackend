<?php

namespace App\Listeners;

use App\Events\ActivityDue;
use App\Modules\CRM\Services\Workflows\WorkflowEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TriggerWorkflowOnActivityOverdue implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ActivityDue $event): void
    {
        app(WorkflowEngineService::class)->trigger(
            'activity.overdue',
            $event->activity,
            []
        );
    }
}

