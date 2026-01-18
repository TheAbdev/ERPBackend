<?php

namespace App\Http\Controllers;

use App\Core\Services\QueueMonitoringService;
use Illuminate\Http\JsonResponse;

class QueueMonitoringController extends Controller
{
    protected QueueMonitoringService $queueMonitoringService;

    public function __construct(QueueMonitoringService $queueMonitoringService)
    {
        $this->queueMonitoringService = $queueMonitoringService;
    }

    /**
     * Get queue statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', \App\Core\Models\AuditLog::class); // Use audit log permission for now

        $stats = $this->queueMonitoringService->getStatistics();

        return response()->json(['data' => $stats]);
    }

    /**
     * Get failed jobs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function failedJobs(): JsonResponse
    {
        $this->authorize('viewAny', \App\Core\Models\AuditLog::class);

        $failedJobs = $this->queueMonitoringService->getFailedJobs(
            request()->input('limit', 50)
        );

        return response()->json(['data' => $failedJobs]);
    }

    /**
     * Get queue metrics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function metrics(): JsonResponse
    {
        $this->authorize('viewAny', \App\Core\Models\AuditLog::class);

        $metrics = $this->queueMonitoringService->getMetrics();

        return response()->json(['data' => $metrics]);
    }
}

