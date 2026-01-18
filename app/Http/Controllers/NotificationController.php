<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Use User's notifications() relationship which uses morphMany
        // This queries the 'notifications' table where notifiable_type = 'App\Models\User' and notifiable_id = user_id
        $query = $user->notifications();

        // Filter unread only
        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $perPage = $request->input('per_page', 20);
        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Get unread count
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'data' => $notifications->map(function ($notification) {
                // Extract data from Laravel notification structure
                $data = $notification->data ?? [];
                
                // Handle different notification types
                $title = $data['title'] ?? $data['subject'] ?? $data['deal_title'] ?? 'Notification';
                $message = $data['message'] ?? $data['body'] ?? '';
                
                // For DealStatusNotification, extract deal information
                if (isset($data['deal_title'])) {
                    $title = "Deal: {$data['deal_title']}";
                    $message = $data['message'] ?? "Deal '{$data['deal_title']}' has been updated.";
                }
                
                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'info', // Extract type from data or use notification type
                    'notification_type' => $notification->type, // Full class name like App\Notifications\DealStatusNotification
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                    'updated_at' => $notification->updated_at?->toISOString(),
                ];
            }),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Use User's notifications() relationship
        $notification = $user->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->json([
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = ErpNotification::where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => "{$count} notification(s) marked as read.",
            'count' => $count,
        ]);
    }
}

