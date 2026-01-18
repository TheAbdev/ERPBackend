<?php

namespace App\Console\Commands;

use App\Core\Services\TenantContext;
use App\Jobs\SendActivityReminderJob;
use App\Modules\CRM\Models\Activity;
use Illuminate\Console\Command;

class CheckActivityReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:check-activity-reminders {--minutes=15 : Minutes before due date to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for activities due soon and send reminders';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $now = now();
        $reminderTime = $now->copy()->addMinutes($minutes);

        // Get all active tenants
        $tenants = \App\Core\Models\Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            app(TenantContext::class)->setTenant($tenant);

            // Find activities due within the reminder window
            $activities = Activity::where('status', 'pending')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$now, $reminderTime])
                ->get();

            foreach ($activities as $activity) {
                SendActivityReminderJob::dispatch($activity->id, $tenant->id);
            }

            if ($activities->count() > 0) {
                $this->info("Dispatched {$activities->count()} reminder(s) for tenant: {$tenant->name}");
            }
        }

        return Command::SUCCESS;
    }
}
