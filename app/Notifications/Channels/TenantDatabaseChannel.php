<?php

namespace App\Notifications\Channels;

use App\Core\Models\Notification;
use App\Core\Services\TenantContext;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Str;

class TenantDatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \App\Core\Models\Notification|null
     */
    public function send($notifiable, BaseNotification $notification): ?Notification
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        if (! $tenantId) {
            return null;
        }

        // All our notification classes implement toArray
        /** @var array<string, mixed> $data */
        $data = method_exists($notification, 'toArray')
            ? call_user_func([$notification, 'toArray'], $notifiable)
            : [];

        return Notification::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'type' => get_class($notification),
            'data' => $data,
        ]);
    }
}

