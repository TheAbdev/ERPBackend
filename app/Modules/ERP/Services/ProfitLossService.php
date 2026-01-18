<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

/**
 * Service for generating Profit & Loss (Income Statement) reports.
 */
class ProfitLossService extends BaseService
{
    /**
     * Generate Profit & Loss statement.
     *
     * @param  int  $fiscalPeriodId
     * @param  bool  $includePreviousPeriods
     * @return array
     */
    public function generateProfitLoss(int $fiscalPeriodId, bool $includePreviousPeriods = false): array
    {
        $fiscalPeriod = FiscalPeriod::where('tenant_id', $this->getTenantId())
            ->findOrFail($fiscalPeriodId);

        $fiscalYear = $fiscalPeriod->fiscalYear;

        // Determine date range
        $dateFrom = $includePreviousPeriods
            ? \Carbon\Carbon::parse($fiscalYear->start_date)
            : \Carbon\Carbon::parse($fiscalPeriod->start_date);
        $dateTo = \Carbon\Carbon::parse($fiscalPeriod->end_date);

        // Get revenue accounts
        $revenues = $this->getAccountsByType('revenue', $dateFrom, $dateTo, $fiscalYear->id);

        // Get COGS accounts
        $cogs = $this->getAccountsByType('expense', $dateFrom, $dateTo, $fiscalYear->id, ['COGS']);

        // Get other expense accounts (excluding COGS)
        $expenses = $this->getAccountsByType('expense', $dateFrom, $dateTo, $fiscalYear->id, ['COGS'], true);

        // Calculate totals
        $totalRevenue = array_sum(array_column($revenues, 'balance'));
        $totalCOGS = array_sum(array_column($cogs, 'balance'));
        $totalExpenses = array_sum(array_column($expenses, 'balance'));
        $grossProfit = $totalRevenue - $totalCOGS;
        $netProfit = $grossProfit - $totalExpenses;

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
            'period_range' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d'),
                'includes_previous_periods' => $includePreviousPeriods,
            ],
            'revenues' => $revenues,
            'revenue_total' => (float) $totalRevenue,
            'cost_of_goods_sold' => $cogs,
            'cogs_total' => (float) $totalCOGS,
            'gross_profit' => (float) $grossProfit,
            'expenses' => $expenses,
            'expenses_total' => (float) $totalExpenses,
            'net_profit' => (float) $netProfit,
        ];
    }

    /**
     * Get accounts by type with balances.
     *
     * @param  string  $type
     * @param  \Carbon\Carbon|string  $dateFrom
     * @param  \Carbon\Carbon|string  $dateTo
     * @param  int  $fiscalYearId
     * @param  array  $excludeCodes
     * @param  bool  $excludeIncluded
     * @return array
     */
    protected function getAccountsByType(
        string $type,
        $dateFrom,
        $dateTo,
        int $fiscalYearId,
        array $excludeCodes = [],
        bool $excludeIncluded = false
    ): array {
        $query = Account::where('tenant_id', $this->getTenantId())
            ->where('type', $type)
            ->where('is_active', true);

        if ($excludeIncluded && !empty($excludeCodes)) {
            $query->whereNotIn('code', $excludeCodes);
        } elseif (!empty($excludeCodes)) {
            $query->whereIn('code', $excludeCodes);
        }

        $accounts = $query->orderBy('display_order')
            ->orderBy('code')
            ->get();

        $result = [];

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalance($account->id, $dateFrom, $dateTo, $fiscalYearId);

            // Only include accounts with activity or balance
            if (abs($balance) > 0.01) {
                $result[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'balance' => (float) $balance,
                ];
            }
        }

        return $result;
    }

    /**
     * Calculate account balance for a date range.
     *
     * @param  int  $accountId
     * @param  \Carbon\Carbon|string  $dateFrom
     * @param  \Carbon\Carbon|string  $dateTo
     * @param  int  $fiscalYearId
     * @return float
     */
    protected function calculateAccountBalance(int $accountId, $dateFrom, $dateTo, int $fiscalYearId): float
    {
        $account = Account::findOrFail($accountId);

        $lines = JournalEntryLine::where('tenant_id', $this->getTenantId())
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($dateFrom, $dateTo, $fiscalYearId) {
                $query->where('status', 'posted')
                    ->where('fiscal_year_id', $fiscalYearId)
                    ->whereDate('entry_date', '>=', $dateFrom)
                    ->whereDate('entry_date', '<=', $dateTo);
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit = (float) ($lines->total_debit ?? 0);
        $totalCredit = (float) ($lines->total_credit ?? 0);

        // Calculate balance based on account type
        if ($account->isDebitType()) {
            return $totalDebit - $totalCredit;
        } else {
            return $totalCredit - $totalDebit;
        }
    }
}

