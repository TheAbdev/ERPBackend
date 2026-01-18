<?php

namespace App\Jobs;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Report;
use App\Modules\ERP\Models\ReportSchedule;
use App\Modules\ERP\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job to export and deliver scheduled reports.
 */
class ExportReportJob extends BaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $scheduleId,
        public int $reportId,
        public string $format,
        public ?array $recipients = null
    ) {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle(
        TenantContext $tenantContext,
        ReportService $reportService
    ): void {
        $schedule = ReportSchedule::with(['report', 'tenant'])->findOrFail($this->scheduleId);

        // Set tenant context
        $tenantContext->setTenant($schedule->tenant);

        try {
            // Generate report data
            $reportData = $reportService->generateReport($this->reportId);

            // Export report
            $exportedData = $reportService->exportReport($this->reportId, $this->format);

            // Send to recipients
            if ($this->recipients && count($this->recipients) > 0) {
                $this->sendToRecipients($schedule, $exportedData);
            }

            Log::info('Report exported and delivered', [
                'schedule_id' => $this->scheduleId,
                'report_id' => $this->reportId,
                'format' => $this->format,
                'recipients_count' => count($this->recipients ?? []),
            ]);

        } catch (\Exception $e) {
            Log::error('Report export failed', [
                'schedule_id' => $this->scheduleId,
                'report_id' => $this->reportId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send exported report to recipients.
     *
     * @param  \App\Modules\ERP\Models\ReportSchedule  $schedule
     * @param  string  $exportedData
     * @return void
     */
    protected function sendToRecipients(ReportSchedule $schedule, string $exportedData): void
    {
        foreach ($this->recipients as $recipient) {
            try {
                // If recipient is an email address
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    // TODO: Implement email sending with attachment
                    // Mail::to($recipient)->send(new ReportMail($schedule->report, $exportedData, $this->format));
                }
                // If recipient is a user ID
                elseif (is_numeric($recipient)) {
                    $user = \App\Models\User::find($recipient);
                    if ($user) {
                        // TODO: Send notification to user
                        // $user->notify(new ReportReadyNotification($schedule->report, $exportedData));
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to send report to recipient', [
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

