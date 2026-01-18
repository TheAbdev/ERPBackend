<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\NotificationResource;
use App\Modules\ERP\Models\Notification;
use App\Modules\ERP\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Notification::class);

        $query = Notification::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->with('entity');

        if ($request->has('unread_only')) {
            $query->whereNull('read_at');
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return NotificationResource::collection($notifications);
    }

    /**
     * Mark notification as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Notification  $notification
     * @return \Illuminate\Http\JsonResponse
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('markRead', $notification);

        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $this->notificationService->markRead($notification->id);

        return response()->json([
            'message' => 'Notification marked as read.',
            'data' => new NotificationResource($notification->fresh()),
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
        $this->authorize('viewAny', Notification::class);

        $count = $this->notificationService->markAllRead($request->user()->id);

        return response()->json([
            'message' => "{$count} notification(s) marked as read.",
            'count' => $count,
        ]);
    }

    /**
     * Get unread notifications count.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return response()->json([
            'count' => $count,
        ]);
    }
}

