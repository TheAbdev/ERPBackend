<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\DashboardResource;
use App\Modules\ERP\Services\ActivityFeedService;
use App\Modules\ERP\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected ReportService $reportService;
    protected ActivityFeedService $activityFeedService;

    public function __construct(
        ReportService $reportService,
        ActivityFeedService $activityFeedService
    ) {
        $this->reportService = $reportService;
        $this->activityFeedService = $activityFeedService;
    }

    /**
     * Get dashboard metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function metrics(Request $request): JsonResponse
    {
        try {
            // Allow Tenant Owner (super_admin) to access dashboard without strict permission check
            $user = $request->user();
            if (!$user->hasRole('super_admin')) {
                $this->authorize('viewAny', \App\Modules\ERP\Models\Report::class);
            }

            $period = $request->input('period', 'month');
            $tenantId = $request->user()->tenant_id;
            $userId = $request->user()->isTenantOwner() ? null : $request->user()->id;
            
            Log::info('Dashboard metrics request', [
                'user_id' => $request->user()->id,
                'tenant_id' => $tenantId,
                'period' => $period,
                'is_personal' => $userId !== null,
            ]);

            $metrics = $this->reportService->generateDashboardMetrics($tenantId, $userId);

            Log::info('Dashboard metrics generated', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'metrics' => $metrics,
            ]);

            return response()->json([
                'data' => new DashboardResource($metrics),
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard metrics error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to load dashboard metrics: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get personal metrics for regular users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function personalMetrics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Only allow regular users (not tenant owners) to access personal metrics
            if ($user->isTenantOwner()) {
                return response()->json([
                    'message' => 'Tenant owners should use the regular metrics endpoint.',
                ], 403);
            }

            $period = $request->input('period', 'month');
            $tenantId = $user->tenant_id;
            $userId = $user->id;
            
            Log::info('Personal dashboard metrics request', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'period' => $period,
            ]);

            $metrics = $this->reportService->generateDashboardMetrics($tenantId, $userId);

            Log::info('Personal dashboard metrics generated', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'metrics' => $metrics,
            ]);

            return response()->json([
                'data' => new DashboardResource($metrics),
            ]);
        } catch (\Exception $e) {
            Log::error('Personal dashboard metrics error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to load personal dashboard metrics: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get recent activities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentActivities(Request $request): JsonResponse
    {
        try {
            // Allow Tenant Owner (super_admin) to access dashboard without strict permission check
            $user = $request->user();
            if (!$user->hasRole('super_admin')) {
                $this->authorize('viewAny', \App\Modules\ERP\Models\ActivityFeed::class);
            }

            $limit = $request->input('limit', 20);
            $tenantId = $request->user()->tenant_id;
            Log::info('Dashboard recent activities request', [
                'user_id' => $request->user()->id,
                'tenant_id' => $tenantId,
                'limit' => $limit,
            ]);

            $activities = $this->activityFeedService->fetchRecent($limit);

            Log::info('Dashboard recent activities fetched', [
                'tenant_id' => $tenantId,
                'count' => $activities->count(),
            ]);

            return response()->json([
                'data' => \App\Modules\ERP\Http\Resources\ActivityFeedResource::collection($activities),
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard recent activities error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'data' => [],
                'message' => 'Failed to load recent activities: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get module summary.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function moduleSummary(Request $request): JsonResponse
    {
        try {
            // Allow Tenant Owner (super_admin) to access dashboard without strict permission check
            $user = $request->user();
            if (!$user->hasRole('super_admin')) {
                $this->authorize('viewAny', \App\Modules\ERP\Models\Report::class);
            }

            $module = $request->input('module', 'ERP');
            $metrics = $this->reportService->generateDashboardMetrics();

            $summary = match ($module) {
                'ERP' => $metrics['erp'] ?? [],
                'CRM' => $metrics['crm'] ?? [],
                'Financial' => $metrics['financial'] ?? [],
                default => [],
            };

            return response()->json([
                'module' => $module,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard module summary error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'module' => $request->input('module', 'ERP'),
                'data' => [],
                'message' => 'Failed to load module summary: ' . $e->getMessage(),
            ], 500);
        }
    }
}

