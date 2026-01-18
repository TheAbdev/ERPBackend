<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ActivityDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public $activity
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Check user notification settings
        $setting = \App\Core\Models\NotificationSetting::where('tenant_id', $notifiable->tenant_id)
            ->where('user_id', $notifiable->id)
            ->where('channel', 'in_app')
            ->where('event', 'activity_due')
            ->first();

        if ($setting && ! $setting->enabled) {
            return [];
        }

        return [\App\Notifications\Channels\TenantDatabaseChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'activity_due',
            'activity_id' => $this->activity->id,
            'activity_subject' => $this->activity->subject,
            'activity_due_date' => $this->activity->due_date?->toIso8601String(),
            'message' => "Activity '{$this->activity->subject}' is due soon.",
        ];
    }
}
