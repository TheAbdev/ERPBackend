<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Service for system health checks.
 */
class HealthCheckService
{
    /**
     * Check database connection.
     *
     * @return array
     */
    public function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'duration_ms' => $duration,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache (Redis) connection.
     *
     * @return array
     */
    public function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_' . uniqid();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($value === 'test') {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache connection successful',
                    'duration_ms' => $duration,
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Cache read/write test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connection.
     *
     * @return array
     */
    public function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $size = Queue::size();

            return [
                'status' => 'healthy',
                'message' => 'Queue connection successful',
                'connection' => $connection,
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage.
     *
     * @return array
     */
    public function checkStorage(): array
    {
        try {
            $start = microtime(true);
            $testFile = 'health_check_' . uniqid() . '.txt';
            Storage::put($testFile, 'test');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);
            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($exists) {
                return [
                    'status' => 'healthy',
                    'message' => 'Storage read/write successful',
                    'duration_ms' => $duration,
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Storage read/write test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Run all health checks.
     *
     * @return array
     */
    public function checkAll(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}

