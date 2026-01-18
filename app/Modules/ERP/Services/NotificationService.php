<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing notifications.
 */
class NotificationService extends BaseService
{
    /**
     * Send notification to a user.
     *
     * @param  \App\Models\User|int  $user
     * @param  string  $title
     * @param  string  $message
     * @param  string  $type
     * @param  string|null  $entityType
     * @param  int|null  $entityId
     * @param  array|null  $metadata
     * @return \App\Modules\ERP\Models\Notification
     */
    public function sendToUser(
        $user,
        string $title,
        string $message,
        string $type = 'info',
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'tenant_id' => $this->getTenantId(),
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Send notification to multiple users.
     *
     * @param  \Illuminate\Database\Eloquent\Collection|array  $users
     * @param  string  $title
     * @param  string  $message
     * @param  string  $type
     * @param  string|null  $entityType
     * @param  int|null  $entityId
     * @param  array|null  $metadata
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sendToUsers(
        $users,
        string $title,
        string $message,
        string $type = 'info',
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): Collection {
        $notifications = collect();

        foreach ($users as $user) {
            $userId = $user instanceof User ? $user->id : $user;
            $notifications->push(
                Notification::create([
                    'tenant_id' => $this->getTenantId(),
                    'user_id' => $userId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'metadata' => $metadata,
                ])
            );
        }

        return $notifications;
    }

    /**
     * Mark notification as read.
     *
     * @param  int  $notificationId
     * @return bool
     */
    public function markRead(int $notificationId): bool
    {
        $notification = Notification::where('tenant_id', $this->getTenantId())
            ->findOrFail($notificationId);

        $result = $notification->markAsRead();

        // Clear cache
        if ($notification->user_id) {
            $this->clearCache($notification->user_id);
        }

        return $result;
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param  int  $userId
     * @return int
     */
    public function markAllRead(int $userId): int
    {
        $result = Notification::where('tenant_id', $this->getTenantId())
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Clear cache
        $this->clearCache($userId);

        return $result;
    }

    /**
     * Get unread notifications count for a user.
     *
     * @param  int  $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        $cacheKey = "notifications_unread_count_{$this->getTenantId()}_{$userId}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($userId) {
            return Notification::where('tenant_id', $this->getTenantId())
                ->where('user_id', $userId)
                ->whereNull('read_at')
                ->count();
        });
    }

    /**
     * Clear notification cache for user.
     *
     * @param  int  $userId
     * @return void
     */
    protected function clearCache(int $userId): void
    {
        \Illuminate\Support\Facades\Cache::forget("notifications_unread_count_{$this->getTenantId()}_{$userId}");
    }
}

