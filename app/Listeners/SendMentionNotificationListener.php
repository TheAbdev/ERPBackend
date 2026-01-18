<?php

namespace App\Listeners;

use App\Events\NoteMentioned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMentionNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(NoteMentioned $event): void
    {
        $event->mentionedUser->notify(
            new \App\Notifications\MentionNotification($event->note, $event->mentionedBy)
        );
    }
}
