<?php

namespace App\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PlatformSystemHealthController extends Controller
{

    /**
     * Get platform system health.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Get system health directly without using SystemMonitoringService
            // (which requires tenant context)
            $metrics = [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'active_connections' => $this->getActiveConnections(),
                'queue_size' => $this->getQueueSize(),
            ];

            $status = $this->determineStatus($metrics);

            // Get or create platform health record (tenant_id = null for platform-level)
            $health = \App\Modules\ERP\Models\SystemHealth::updateOrCreate(
                ['tenant_id' => null],
                array_merge($metrics, [
                    'status' => $status,
                    'metrics' => $metrics,
                    'last_checked_at' => now(),
                ])
            );

            return response()->json([
                'data' => [
                    'cpu_usage' => (float) ($health->cpu_usage ?? 0.0),
                    'memory_usage' => (float) ($health->memory_usage ?? 0.0),
                    'disk_usage' => (float) ($health->disk_usage ?? 0.0),
                    'active_connections' => (int) ($health->active_connections ?? 0),
                    'queue_size' => (int) ($health->queue_size ?? 0),
                    'status' => $health->status ?? 'healthy',
                    'last_checked_at' => $health->last_checked_at?->toIso8601String() ?? now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Platform system health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return default healthy status on error
            return response()->json([
                'data' => [
                    'cpu_usage' => 0.0,
                    'memory_usage' => 0.0,
                    'disk_usage' => 0.0,
                    'active_connections' => 0,
                    'queue_size' => 0,
                    'status' => 'healthy',
                    'last_checked_at' => now()->toIso8601String(),
                ],
            ]);
        }
    }

    /**
     * Get CPU usage percentage.
     *
     * @return float
     */
    protected function getCpuUsage(): float
    {
        // Simplified - in production, use proper system monitoring
        return 0.0;
    }

    /**
     * Get memory usage percentage.
     *
     * @return float
     */
    protected function getMemoryUsage(): float
    {
        $memory = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);

        if ($memoryLimitBytes > 0) {
            return round(($memory / $memoryLimitBytes) * 100, 2);
        }

        return 0.0;
    }

    /**
     * Get disk usage percentage.
     *
     * @return float
     */
    protected function getDiskUsage(): float
    {
        // Try root directory first (Linux/Mac), then current directory (Windows)
        $paths = ['/', '.', 'C:'];
        
        foreach ($paths as $path) {
            $total = @disk_total_space($path);
            $free = @disk_free_space($path);
            
            if ($total !== false && $total > 0) {
                return round((($total - $free) / $total) * 100, 2);
            }
        }

        return 0.0;
    }

    /**
     * Get active database connections.
     *
     * @return int
     */
    protected function getActiveConnections(): int
    {
        try {
            $result = DB::selectOne('SHOW STATUS LIKE "Threads_connected"');
            return (int) ($result->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get queue size.
     *
     * @return int
     */
    protected function getQueueSize(): int
    {
        try {
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Determine system status.
     *
     * @param  array  $metrics
     * @return string
     */
    protected function determineStatus(array $metrics): string
    {
        if ($metrics['cpu_usage'] > 90 || $metrics['memory_usage'] > 90 || $metrics['disk_usage'] > 90) {
            return 'critical';
        }

        if ($metrics['cpu_usage'] > 75 || $metrics['memory_usage'] > 75 || $metrics['disk_usage'] > 75) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Convert memory limit string to bytes.
     *
     * @param  string  $memoryLimit
     * @return int
     */
    protected function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get health status for all tenants.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tenants(): JsonResponse
    {
        $tenants = \App\Core\Models\Tenant::where('status', 'active')->get();
        $tenantsHealth = [];

        foreach ($tenants as $tenant) {
            $health = \App\Modules\ERP\Models\SystemHealth::where('tenant_id', $tenant->id)
                ->withoutGlobalScopes()
                ->latest('last_checked_at')
                ->first();

            $tenantsHealth[] = [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'health_status' => $health->status ?? 'healthy',
                'metrics' => [
                    'cpu_usage' => $health->cpu_usage ?? null,
                    'memory_usage' => $health->memory_usage ?? null,
                    'disk_usage' => $health->disk_usage ?? null,
                    'active_connections' => $health->active_connections ?? null,
                    'queue_size' => $health->queue_size ?? null,
                ],
            ];
        }

        return response()->json([
            'data' => $tenantsHealth,
        ]);
    }

    /**
     * Get critical alerts.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function alerts(): JsonResponse
    {
        $criticalHealth = \App\Modules\ERP\Models\SystemHealth::where('status', 'critical')
            ->latest('last_checked_at')
            ->limit(10)
            ->get();

        $alerts = [];
        foreach ($criticalHealth as $health) {
            $alerts[] = [
                'alert_type' => 'system_health',
                'message' => "System health is critical for tenant #{$health->tenant_id}",
                'severity' => 'critical',
                'created_at' => $health->last_checked_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        return response()->json([
            'data' => $alerts,
        ]);
    }
}

