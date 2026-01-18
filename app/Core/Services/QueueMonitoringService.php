<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Service for monitoring queue status.
 */
class QueueMonitoringService
{
    /**
     * Get queue statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $connection = config('queue.default');

        return [
            'connection' => $connection,
            'pending' => Queue::size(),
            'failed' => $this->getFailedJobCount(),
            'retries' => $this->getRetryCount(),
        ];
    }

    /**
     * Get failed jobs.
     *
     * @param  int  $limit
     * @return array
     */
    public function getFailedJobs(int $limit = 50): array
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'id' => $job->id,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'exception' => $job->exception,
                    'failed_at' => $job->failed_at,
                    'job_class' => $payload['displayName'] ?? 'Unknown',
                ];
            });

        return $failedJobs->toArray();
    }

    /**
     * Get failed job count.
     *
     * @return int
     */
    protected function getFailedJobCount(): int
    {
        return DB::table('failed_jobs')->count();
    }

    /**
     * Get retry count (jobs that have been retried).
     *
     * @return int
     */
    protected function getRetryCount(): int
    {
        // This would require custom tracking in your jobs table
        // For now, return 0 as a placeholder
        return 0;
    }

    /**
     * Get queue metrics for monitoring.
     *
     * @return array
     */
    public function getMetrics(): array
    {
        return [
            'pending_jobs' => Queue::size(),
            'failed_jobs' => $this->getFailedJobCount(),
            'queue_connection' => config('queue.default'),
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}

