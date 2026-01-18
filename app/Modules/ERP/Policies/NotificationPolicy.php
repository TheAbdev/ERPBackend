<?php

namespace App\Modules\ERP\Policies;

class NotificationPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'notifications';
    }

    /**
     * Determine if user can mark notification as read.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Modules\ERP\Models\Notification  $notification
     * @return bool
     */
    public function markRead(\App\Models\User $user, \App\Modules\ERP\Models\Notification $notification): bool
    {
        return $user->can('erp.notifications.markRead') && $notification->user_id === $user->id;
    }
}

