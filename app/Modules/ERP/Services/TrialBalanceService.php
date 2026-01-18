<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

/**
 * Service for generating Trial Balance reports.
 */
class TrialBalanceService extends BaseService
{
    /**
     * Generate trial balance for a fiscal period.
     *
     * @param  int  $fiscalPeriodId
     * @param  bool  $includeOpeningBalance
     * @return array
     */
    public function generateTrialBalance(int $fiscalPeriodId, bool $includeOpeningBalance = true): array
    {
        $fiscalPeriod = FiscalPeriod::where('tenant_id', $this->getTenantId())
            ->findOrFail($fiscalPeriodId);

        $fiscalYear = $fiscalPeriod->fiscalYear;

        // Get all active accounts
        $accounts = Account::where('tenant_id', $this->getTenantId())
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $trialBalance = [];
        $totalDebits = 0;
        $totalCredits = 0;
        $totalOpeningDebits = 0;
        $totalOpeningCredits = 0;
        $totalEndingDebits = 0;
        $totalEndingCredits = 0;

        foreach ($accounts as $account) {
            // Calculate opening balance (from previous periods)
            $openingBalance = $includeOpeningBalance
                ? $this->calculateOpeningBalance($account->id, $fiscalPeriod)
                : 0;

            // Calculate period activity (only posted entries)
            $periodActivity = $this->calculatePeriodActivity($account->id, $fiscalPeriod);

            // Calculate ending balance
            $endingBalance = $openingBalance + $periodActivity['net'];

            // Determine debit/credit based on account type
            if ($account->isDebitType()) {
                $openingDebit = max(0, $openingBalance);
                $openingCredit = 0;
                $periodDebit = $periodActivity['debit'];
                $periodCredit = $periodActivity['credit'];
                $endingDebit = max(0, $endingBalance);
                $endingCredit = 0;
            } else {
                $openingDebit = 0;
                $openingCredit = max(0, abs($openingBalance));
                $periodDebit = $periodActivity['debit'];
                $periodCredit = $periodActivity['credit'];
                $endingDebit = 0;
                $endingCredit = max(0, abs($endingBalance));
            }

            // Only include accounts with activity or balance
            if ($openingBalance != 0 || $periodActivity['debit'] != 0 || $periodActivity['credit'] != 0) {
                $trialBalance[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->type,
                    'opening_debit' => (float) $openingDebit,
                    'opening_credit' => (float) $openingCredit,
                    'period_debit' => (float) $periodDebit,
                    'period_credit' => (float) $periodCredit,
                    'ending_debit' => (float) $endingDebit,
                    'ending_credit' => (float) $endingCredit,
                ];

                $totalOpeningDebits += $openingDebit;
                $totalOpeningCredits += $openingCredit;
                $totalDebits += $periodDebit;
                $totalCredits += $periodCredit;
                $totalEndingDebits += $endingDebit;
                $totalEndingCredits += $endingCredit;
            }
        }

        return [
            'fiscal_period' => [
                'id' => $fiscalPeriod->id,
                'name' => $fiscalPeriod->name,
                'code' => $fiscalPeriod->code,
                'start_date' => $fiscalPeriod->start_date->format('Y-m-d'),
                'end_date' => $fiscalPeriod->end_date->format('Y-m-d'),
            ],
            'fiscal_year' => [
                'id' => $fiscalYear->id,
                'name' => $fiscalYear->name,
            ],
            'accounts' => $trialBalance,
            'totals' => [
                'opening_debits' => (float) $totalOpeningDebits,
                'opening_credits' => (float) $totalOpeningCredits,
                'period_debits' => (float) $totalDebits,
                'period_credits' => (float) $totalCredits,
                'ending_debits' => (float) $totalEndingDebits,
                'ending_credits' => (float) $totalEndingCredits,
                'is_balanced' => abs($totalEndingDebits - $totalEndingCredits) < 0.01,
            ],
        ];
    }

    /**
     * Calculate opening balance for an account up to (but not including) the given period.
     *
     * @param  int  $accountId
     * @param  \App\Modules\ERP\Models\FiscalPeriod  $fiscalPeriod
     * @return float
     */
    protected function calculateOpeningBalance(int $accountId, FiscalPeriod $fiscalPeriod): float
    {
        $account = Account::findOrFail($accountId);

        // Get all posted journal entry lines for this account before the period start date
        $lines = JournalEntryLine::where('tenant_id', $this->getTenantId())
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($fiscalPeriod) {
                $query->where('status', 'posted')
                    ->where('fiscal_year_id', $fiscalPeriod->fiscal_year_id)
                    ->where('entry_date', '<', $fiscalPeriod->start_date);
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit = (float) ($lines->total_debit ?? 0);
        $totalCredit = (float) ($lines->total_credit ?? 0);

        // Calculate net balance based on account type
        if ($account->isDebitType()) {
            return $totalDebit - $totalCredit;
        } else {
            return $totalCredit - $totalDebit;
        }
    }

    /**
     * Calculate period activity for an account.
     *
     * @param  int  $accountId
     * @param  \App\Modules\ERP\Models\FiscalPeriod  $fiscalPeriod
     * @return array
     */
    protected function calculatePeriodActivity(int $accountId, FiscalPeriod $fiscalPeriod): array
    {
        $lines = JournalEntryLine::where('tenant_id', $this->getTenantId())
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($fiscalPeriod) {
                $query->where('status', 'posted')
                    ->where('fiscal_period_id', $fiscalPeriod->id);
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $debit = (float) ($lines->total_debit ?? 0);
        $credit = (float) ($lines->total_credit ?? 0);
        $net = $debit - $credit;

        return [
            'debit' => $debit,
            'credit' => $credit,
            'net' => $net,
        ];
    }
}

