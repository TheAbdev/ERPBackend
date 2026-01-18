<?php

namespace App\Console\Commands;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\ReportSchedule;
use App\Modules\ERP\Services\ReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command to generate scheduled reports.
 * This command checks for active report schedules and generates reports accordingly.
 */
class GenerateScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:generate-scheduled-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate scheduled reports based on cron expressions';

    /**
     * Execute the console command.
     */
    public function handle(TenantContext $tenantContext, ReportService $reportService): int
    {
        $this->info('Checking for scheduled reports...');

        $now = now();
        $processed = 0;
        $errors = 0;

        // Get all active schedules that are due
        $schedules = ReportSchedule::where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->with(['report', 'tenant'])
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No scheduled reports due at this time.');
            return Command::SUCCESS;
        }

        foreach ($schedules as $schedule) {
            try {
                // Set tenant context
                $tenantContext->setTenant($schedule->tenant);

                $this->info("Processing report schedule ID {$schedule->id} for tenant: {$schedule->tenant->name}");

                // Generate the report
                $reportData = $reportService->generateReport($schedule->report_id);

                // Export the report (queue job for async processing)
                \App\Jobs\ExportReportJob::dispatch(
                    $schedule->id,
                    $schedule->report_id,
                    $schedule->format,
                    $schedule->recipients
                );

                // Update schedule
                $schedule->update([
                    'last_run_at' => $now,
                    'next_run_at' => $schedule->calculateNextRun(),
                ]);

                $processed++;
                $this->info("✓ Report schedule {$schedule->id} processed successfully.");

            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Failed to process report schedule {$schedule->id}: {$e->getMessage()}");

                Log::error('Scheduled report generation failed', [
                    'schedule_id' => $schedule->id,
                    'tenant_id' => $schedule->tenant_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("\nCompleted: {$processed} processed, {$errors} errors");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

