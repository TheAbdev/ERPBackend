<?php

namespace App\Http\Controllers;

use App\Core\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    protected HealthCheckService $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * Get system health status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $checks = $this->healthCheckService->checkAll();

        $overallStatus = 'healthy';
        foreach ($checks as $check) {
            if (isset($check['status']) && $check['status'] === 'unhealthy') {
                $overallStatus = 'unhealthy';
                break;
            }
        }

        return response()->json([
            'status' => $overallStatus,
            'checks' => $checks,
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Check database.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function database(): JsonResponse
    {
        $check = $this->healthCheckService->checkDatabase();
        return response()->json($check, $check['status'] === 'healthy' ? 200 : 503);
    }

    /**
     * Check cache.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cache(): JsonResponse
    {
        $check = $this->healthCheckService->checkCache();
        return response()->json($check, $check['status'] === 'healthy' ? 200 : 503);
    }

    /**
     * Check queue.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function queue(): JsonResponse
    {
        $check = $this->healthCheckService->checkQueue();
        return response()->json($check, $check['status'] === 'healthy' ? 200 : 503);
    }

    /**
     * Check storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storage(): JsonResponse
    {
        $check = $this->healthCheckService->checkStorage();
        return response()->json($check, $check['status'] === 'healthy' ? 200 : 503);
    }
}

