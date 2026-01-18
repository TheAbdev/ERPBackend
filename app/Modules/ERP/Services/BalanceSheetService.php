<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\JournalEntryLine;
use App\Modules\ERP\Services\ProfitLossService;

/**
 * Service for generating Balance Sheet reports.
 */
class BalanceSheetService extends BaseService
{
    protected ProfitLossService $profitLossService;

    public function __construct(
        TenantContext $tenantContext,
        ProfitLossService $profitLossService
    ) {
        parent::__construct($tenantContext);
        $this->profitLossService = $profitLossService;
    }

    /**
     * Generate Balance Sheet.
     *
     * @param  int  $fiscalPeriodId
     * @return array
     */
    public function generateBalanceSheet(int $fiscalPeriodId): array
    {
        $fiscalPeriod = FiscalPeriod::where('tenant_id', $this->getTenantId())
            ->findOrFail($fiscalPeriodId);

        $fiscalYear = $fiscalPeriod->fiscalYear;

        // Get assets
        $assets = $this->getAccountsByType('asset', $fiscalPeriod, $fiscalYear->id);

        // Get liabilities
        $liabilities = $this->getAccountsByType('liability', $fiscalPeriod, $fiscalYear->id);

        // Get equity accounts
        $equity = $this->getAccountsByType('equity', $fiscalPeriod, $fiscalYear->id);

        // Calculate retained earnings (net profit from P&L)
        $profitLoss = $this->profitLossService->generateProfitLoss($fiscalPeriodId, true);
        $retainedEarnings = $profitLoss['net_profit'];

        // Add retained earnings to equity
        $equity[] = [
            'account_id' => null,
            'account_code' => 'RETAINED_EARNINGS',
            'account_name' => 'Retained Earnings',
            'balance' => $retainedEarnings,
        ];

        // Calculate totals
        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity = array_sum(array_column($equity, 'balance'));
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        // Check if balanced
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

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
            'as_of_date' => $fiscalPeriod->end_date->format('Y-m-d'),
            'assets' => $assets,
            'assets_total' => (float) $totalAssets,
            'liabilities' => $liabilities,
            'liabilities_total' => (float) $totalLiabilities,
            'equity' => $equity,
            'equity_total' => (float) $totalEquity,
            'liabilities_and_equity_total' => (float) $totalLiabilitiesAndEquity,
            'is_balanced' => $isBalanced,
            'balance_difference' => (float) abs($totalAssets - $totalLiabilitiesAndEquity),
        ];
    }

    /**
     * Get accounts by type with balances up to the period end date.
     *
     * @param  string  $type
     * @param  \App\Modules\ERP\Models\FiscalPeriod  $fiscalPeriod
     * @param  int  $fiscalYearId
     * @return array
     */
    protected function getAccountsByType(string $type, FiscalPeriod $fiscalPeriod, int $fiscalYearId): array
    {
        $accounts = Account::where('tenant_id', $this->getTenantId())
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('code')
            ->get();

        $result = [];

        foreach ($accounts as $account) {
            $balance = $this->calculateAccountBalanceToDate($account->id, $fiscalPeriod->end_date, $fiscalYearId);

            // Include all accounts, even with zero balance
            $result[] = [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => (float) $balance,
            ];
        }

        return $result;
    }

    /**
     * Calculate account balance up to a specific date.
     *
     * @param  int  $accountId
     * @param  \Carbon\Carbon|string  $dateTo
     * @param  int  $fiscalYearId
     * @return float
     */
    protected function calculateAccountBalanceToDate(int $accountId, $dateTo, int $fiscalYearId): float
    {
        $account = Account::findOrFail($accountId);

        $lines = JournalEntryLine::where('tenant_id', $this->getTenantId())
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($dateTo, $fiscalYearId) {
                $query->where('status', 'posted')
                    ->where('fiscal_year_id', $fiscalYearId)
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

