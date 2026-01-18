<?php

namespace App\Jobs;

use App\Core\Services\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Base job class with tenant awareness and optimized retry handling.
 */
abstract class BaseJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public int $timeout;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public int $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->tries = config('performance.queue.max_retries', 3);
        $this->timeout = config('performance.queue.job_timeout', 300);
        $this->backoff = config('performance.queue.retry_delay', 60);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        // Log failure
        \Illuminate\Support\Facades\Log::error('Job failed', [
            'job' => static::class,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optionally notify administrators
        // Notification::route('mail', config('mail.admin'))->notify(new JobFailedNotification($this, $exception));
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        // Exponential backoff: 60s, 120s, 240s
        return [
            $this->backoff,
            $this->backoff * 2,
            $this->backoff * 4,
        ];
    }
}

