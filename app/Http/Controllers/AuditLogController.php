<?php

namespace App\Http\Controllers;

use App\Core\Models\AuditLog;
use App\Core\Services\ActivityTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    protected ActivityTimelineService $timelineService;

    public function __construct(ActivityTimelineService $timelineService)
    {
        $this->timelineService = $timelineService;
    }

    /**
     * List audit logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $user = $request->user();
        $query = AuditLog::with(['user:id,name,email']);

        // Check if user is Site Owner (site_owner role) - check without tenant_id restriction
        $isSiteOwner = $user->roles()
            ->where('slug', 'site_owner')
            ->exists();

        // Site Owner (site_owner role): can see all audit logs (no filtering)
        // Super Admin (super_admin role): can only see their tenant's audit logs
        if (!$isSiteOwner) {
            // If not Site Owner, filter by tenant_id
            if ($user->tenant_id) {
                $query->where('tenant_id', $user->tenant_id);
            } else {
                // If user has no tenant_id, return empty (should not happen)
                $query->whereRaw('1 = 0');
            }
        }

        // Filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->has('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->has('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'model_name' => $log->model_name,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'metadata' => $log->metadata,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            }),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Get model timeline.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function modelTimeline(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        $timeline = $this->timelineService->getModelTimeline(
            $request->input('model_type'),
            $request->input('model_id'),
            $request->input('limit', 50)
        );

        return response()->json(['data' => $timeline]);
    }

    /**
     * Get user timeline.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userTimeline(int $userId): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $timeline = $this->timelineService->getUserTimeline($userId);

        return response()->json(['data' => $timeline]);
    }

    /**
     * Get recent activity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $activity = $this->timelineService->getRecentActivity(
            $request->input('limit', 100)
        );

        return response()->json(['data' => $activity]);
    }

    /**
     * Delete audit logs for the user's tenant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check authorization - for bulk delete, create a temporary instance
        // The policy delete method accepts null for bulk operations
        $tempAuditLog = new AuditLog();
        $this->authorize('delete', $tempAuditLog);
        $query = AuditLog::query();

        // Check if user is Site Owner (site_owner role)
        $isSiteOwner = $user->roles()
            ->where('slug', 'site_owner')
            ->exists();

        // Site Owner can delete all audit logs
        // Super Admin can only delete their tenant's audit logs
        if (!$isSiteOwner) {
            // If not Site Owner, only delete logs for user's tenant
            if ($user->tenant_id) {
                $query->where('tenant_id', $user->tenant_id);
            } else {
                // If user has no tenant_id, return error
                return response()->json([
                    'message' => 'Cannot delete audit logs. User has no tenant.',
                ], 422);
            }
        }

        // Apply optional filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->has('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        $deletedCount = $query->delete();

        return response()->json([
            'message' => "Successfully deleted {$deletedCount} audit log(s).",
            'deleted_count' => $deletedCount,
        ]);
    }
}

