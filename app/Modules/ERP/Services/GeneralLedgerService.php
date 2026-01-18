<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use Illuminate\Support\Collection;

/**
 * Service for generating General Ledger reports.
 */
class GeneralLedgerService extends BaseService
{
    /**
     * Generate general ledger for an account.
     *
     * @param  int  $accountId
     * @param  int|null  $fiscalPeriodId
     * @param  string|null  $dateFrom
     * @param  string|null  $dateTo
     * @return array
     */
    public function generateGeneralLedger(
        int $accountId,
        ?int $fiscalPeriodId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $account = Account::where('tenant_id', $this->getTenantId())
            ->findOrFail($accountId);

        // Build query for journal entry lines
        $query = JournalEntryLine::where('tenant_id', $this->getTenantId())
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) {
                $q->where('status', 'posted');
            })
            ->with(['journalEntry.fiscalPeriod', 'journalEntry.fiscalYear', 'currency'])
            ->orderBy('created_at');

        if ($fiscalPeriodId) {
            $query->whereHas('journalEntry', function ($q) use ($fiscalPeriodId) {
                $q->where('fiscal_period_id', $fiscalPeriodId);
            });
        }

        if ($dateFrom) {
            $query->whereHas('journalEntry', function ($q) use ($dateFrom) {
                $q->whereDate('entry_date', '>=', $dateFrom);
            });
        }

        if ($dateTo) {
            $query->whereHas('journalEntry', function ($q) use ($dateTo) {
                $q->whereDate('entry_date', '<=', $dateTo);
            });
        }

        $lines = $query->get();

        // Calculate running balance
        $runningBalance = 0;
        $entries = [];

        foreach ($lines as $line) {
            $journalEntry = $line->journalEntry;

            // Calculate balance change
            if ($account->isDebitType()) {
                $balanceChange = $line->debit - $line->credit;
            } else {
                $balanceChange = $line->credit - $line->debit;
            }

            $runningBalance += $balanceChange;

            $entries[] = [
                'id' => $line->id,
                'entry_date' => $journalEntry->entry_date->format('Y-m-d'),
                'entry_number' => $journalEntry->entry_number,
                'fiscal_period' => [
                    'id' => $journalEntry->fiscalPeriod->id,
                    'name' => $journalEntry->fiscalPeriod->name,
                    'code' => $journalEntry->fiscalPeriod->code,
                ],
                'description' => $line->description ?? $journalEntry->description,
                'reference_type' => $journalEntry->reference_type,
                'reference_id' => $journalEntry->reference_id,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance_change' => (float) $balanceChange,
                'running_balance' => (float) $runningBalance,
                'currency' => [
                    'id' => $line->currency->id,
                    'code' => $line->currency->code,
                    'name' => $line->currency->name,
                ],
            ];
        }

        // Calculate totals
        $totalDebits = $lines->sum('debit');
        $totalCredits = $lines->sum('credit');
        $netChange = $account->isDebitType()
            ? ($totalDebits - $totalCredits)
            : ($totalCredits - $totalDebits);

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
            ],
            'entries' => $entries,
            'totals' => [
                'total_debits' => (float) $totalDebits,
                'total_credits' => (float) $totalCredits,
                'net_change' => (float) $netChange,
                'ending_balance' => (float) $runningBalance,
            ],
        ];
    }
}

