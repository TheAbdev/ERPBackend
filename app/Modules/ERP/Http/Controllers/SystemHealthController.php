<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\SystemHealthResource;
use App\Modules\ERP\Services\SystemMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    protected SystemMonitoringService $monitoringService;

    public function __construct(SystemMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Get system health status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\SystemHealth::class);

        $health = $this->monitoringService->checkHealth();

        return response()->json([
            'data' => new SystemHealthResource($health),
        ]);
    }

    /**
     * Check health and return status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\SystemHealth::class);

        $health = $this->monitoringService->checkHealth();

        return response()->json([
            'data' => new SystemHealthResource($health),
        ]);
    }
}

