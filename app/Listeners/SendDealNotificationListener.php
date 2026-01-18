<?php

namespace App\Listeners;

use App\Events\DealStatusChanged;
use App\Jobs\SendDealNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDealNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(DealStatusChanged $event): void
    {
        SendDealNotificationJob::dispatch(
            $event->deal->id,
            $event->deal->tenant_id,
            $event->action
        );
    }
}
