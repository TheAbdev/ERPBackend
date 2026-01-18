<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\FiscalYear;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use App\Modules\ERP\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling accounting operations.
 */
class AccountingService extends BaseService
{
    protected WorkflowService $workflowService;

    public function __construct(
        TenantContext $tenantContext,
        WorkflowService $workflowService
    ) {
        parent::__construct($tenantContext);
        $this->workflowService = $workflowService;
    }

    /**
     * Post a journal entry.
     *
     * @param  \App\Modules\ERP\Models\JournalEntry  $entry
     * @param  int  $userId
     * @return void
     *
     * @throws \Exception
     */
    public function postJournalEntry(JournalEntry $entry, int $userId): void
    {
        if ($entry->isPosted()) {
            throw new \Exception('Journal entry is already posted.');
        }

        // Check if workflow approval is required
        if ($this->workflowService->requiresApproval($entry)) {
            // Start workflow instead of posting directly
            $this->workflowService->startWorkflow($entry, $userId);

            throw new \Exception('Journal entry requires approval. Workflow has been initiated.');
        }

        // Validate entry is balanced
        if (! $this->validateBalancedEntry($entry)) {
            throw new \Exception('Journal entry is not balanced. Debits must equal credits.');
        }

        // Validate fiscal period is open
        $this->validateFiscalPeriod($entry->fiscal_period_id);

        DB::transaction(function () use ($entry, $userId) {
            $entry->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);
        });
    }

    /**
     * Validate that a journal entry is balanced.
     *
     * @param  \App\Modules\ERP\Models\JournalEntry  $entry
     * @return bool
     */
    public function validateBalancedEntry(JournalEntry $entry): bool
    {
        return $entry->isBalanced();
    }

    /**
     * Validate fiscal period is open and active.
     *
     * @param  int  $fiscalPeriodId
     * @return void
     *
     * @throws \Exception
     */
    public function validateFiscalPeriod(int $fiscalPeriodId): void
    {
        $period = FiscalPeriod::where('tenant_id', $this->getTenantId())
            ->findOrFail($fiscalPeriodId);

        if ($period->is_closed) {
            throw new \Exception('Cannot post to a closed fiscal period.');
        }

        if (! $period->is_active) {
            throw new \Exception('Cannot post to an inactive fiscal period.');
        }

        // Check fiscal year is also active
        $fiscalYear = FiscalYear::where('tenant_id', $this->getTenantId())
            ->findOrFail($period->fiscal_year_id);

        if ($fiscalYear->is_closed) {
            throw new \Exception('Cannot post to a closed fiscal year.');
        }

        if (! $fiscalYear->is_active) {
            throw new \Exception('Cannot post to an inactive fiscal year.');
        }
    }

    /**
     * Get active fiscal period for a date.
     *
     * @param  \Carbon\Carbon|string  $date
     * @return \App\Modules\ERP\Models\FiscalPeriod
     *
     * @throws \Exception
     */
    public function getActiveFiscalPeriod($date): FiscalPeriod
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;

        $period = FiscalPeriod::where('tenant_id', $this->getTenantId())
            ->where('is_active', true)
            ->where('is_closed', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (! $period) {
            throw new \Exception('No active fiscal period found for the given date.');
        }

        return $period;
    }

    /**
     * Get active fiscal year for a date.
     *
     * @param  \Carbon\Carbon|string  $date
     * @return \App\Modules\ERP\Models\FiscalYear
     *
     * @throws \Exception
     */
    public function getActiveFiscalYear($date): FiscalYear
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;

        $year = FiscalYear::where('tenant_id', $this->getTenantId())
            ->where('is_active', true)
            ->where('is_closed', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (! $year) {
            throw new \Exception('No active fiscal year found for the given date.');
        }

        return $year;
    }
}

