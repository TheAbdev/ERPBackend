<?php

namespace App\Listeners;

use App\Events\ActivityDue;
use App\Jobs\SendActivityReminderJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendActivityReminderListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ActivityDue $event): void
    {
        SendActivityReminderJob::dispatch(
            $event->activity->id,
            $event->activity->tenant_id
        );
    }
}
