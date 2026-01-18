<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public $note,
        public $mentionedBy
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
            ->where('event', 'mention')
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
            'type' => 'mention',
            'note_id' => $this->note->id,
            'noteable_type' => $this->note->noteable_type,
            'noteable_id' => $this->note->noteable_id,
            'mentioned_by' => $this->mentionedBy->name,
            'message' => "{$this->mentionedBy->name} mentioned you in a note.",
        ];
    }
}
