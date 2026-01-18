<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\SystemHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for system health monitoring.
 */
class SystemMonitoringService extends BaseService
{
    /**
     * Check system health.
     *
     * @param  int|null  $tenantId
     * @return \App\Modules\ERP\Models\SystemHealth
     */
    public function checkHealth(?int $tenantId = null): SystemHealth
    {
        $tenantId = $tenantId ?? $this->getTenantId();

        $metrics = [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'active_connections' => $this->getActiveConnections(),
            'queue_size' => $this->getQueueSize(),
        ];

        $status = $this->determineStatus($metrics);

        $health = SystemHealth::updateOrCreate(
            ['tenant_id' => $tenantId],
            array_merge($metrics, [
                'status' => $status,
                'metrics' => $metrics,
                'last_checked_at' => now(),
            ])
        );

        // Notify if critical
        if ($status === 'critical') {
            $this->notifyAlerts($health);
        }

        return $health;
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
        $total = disk_total_space('/');
        $free = disk_free_space('/');

        if ($total > 0) {
            return round((($total - $free) / $total) * 100, 2);
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
            return (int) DB::selectOne('SHOW STATUS LIKE "Threads_connected"')->Value ?? 0;
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
     * Notify alerts for critical status.
     *
     * @param  \App\Modules\ERP\Models\SystemHealth  $health
     * @return void
     */
    protected function notifyAlerts(SystemHealth $health): void
    {
        Log::critical('System health critical', [
            'tenant_id' => $health->tenant_id,
            'status' => $health->status,
            'metrics' => $health->metrics,
        ]);

        // In production, send notifications to admins
    }

    /**
     * Convert memory limit string to bytes.
     *
     * @param  string  $value
     * @return int
     */
    protected function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}

