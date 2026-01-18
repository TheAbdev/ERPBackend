<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DealStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public $deal,
        public $action
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
            ->where('event', 'deal_update')
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
        $message = match ($this->action) {
            'won' => "Deal '{$this->deal->title}' has been marked as won.",
            'lost' => "Deal '{$this->deal->title}' has been marked as lost.",
            'stage_changed' => "Deal '{$this->deal->title}' has moved to a new stage.",
            default => "Deal '{$this->deal->title}' has been updated.",
        };

        return [
            'type' => 'deal_update',
            'deal_id' => $this->deal->id,
            'deal_title' => $this->deal->title,
            'action' => $this->action,
            'message' => $message,
        ];
    }
}
