<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\AssetDepreciation;
use App\Modules\ERP\Models\FixedAsset;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling asset depreciation operations.
 */
class DepreciationService extends BaseService
{
    protected AccountingService $accountingService;

    public function __construct(
        TenantContext $tenantContext,
        AccountingService $accountingService
    ) {
        parent::__construct($tenantContext);
        $this->accountingService = $accountingService;
    }

    /**
     * Generate depreciation schedule for an asset.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $asset
     * @return array
     */
    public function generateDepreciationSchedule(FixedAsset $asset): array
    {
        if (!$asset->isActive()) {
            return [
                'asset' => [
                    'id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'name' => $asset->name,
                ],
                'schedule' => [],
                'message' => 'Asset is not active.',
            ];
        }

        $schedule = [];
        $monthlyDepreciation = $asset->calculateMonthlyDepreciation();
        $startDate = \Carbon\Carbon::parse($asset->activation_date);
        $accumulatedDepreciation = 0;
        $netBookValue = $asset->acquisition_cost;

        for ($month = 0; $month < $asset->useful_life_months; $month++) {
            $depreciationDate = $startDate->copy()->addMonths($month);
            $accumulatedDepreciation += $monthlyDepreciation;
            $netBookValue = $asset->acquisition_cost - $accumulatedDepreciation;

            // Check if depreciation already posted for this period
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($depreciationDate);
            $existingDepreciation = AssetDepreciation::where('tenant_id', $this->getTenantId())
                ->where('fixed_asset_id', $asset->id)
                ->where('fiscal_period_id', $fiscalPeriod->id)
                ->first();

            $schedule[] = [
                'period' => $month + 1,
                'depreciation_date' => $depreciationDate->format('Y-m-d'),
                'fiscal_period' => [
                    'id' => $fiscalPeriod->id,
                    'name' => $fiscalPeriod->name,
                    'code' => $fiscalPeriod->code,
                ],
                'depreciation_amount' => (float) $monthlyDepreciation,
                'accumulated_depreciation' => (float) min($accumulatedDepreciation, $asset->acquisition_cost - $asset->salvage_value),
                'net_book_value' => (float) max($netBookValue, $asset->salvage_value),
                'is_posted' => $existingDepreciation ? $existingDepreciation->is_posted : false,
                'depreciation_id' => $existingDepreciation?->id,
            ];
        }

        return [
            'asset' => [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'acquisition_cost' => (float) $asset->acquisition_cost,
                'salvage_value' => (float) $asset->salvage_value,
                'useful_life_months' => $asset->useful_life_months,
                'monthly_depreciation' => (float) $monthlyDepreciation,
            ],
            'schedule' => $schedule,
        ];
    }

    /**
     * Post depreciation for a fiscal period.
     *
     * @param  int  $fiscalPeriodId
     * @param  int  $userId
     * @return array
     *
     * @throws \Exception
     */
    public function postDepreciationForPeriod(int $fiscalPeriodId, int $userId): array
    {
        $fiscalPeriod = FiscalPeriod::where('tenant_id', $this->getTenantId())
            ->findOrFail($fiscalPeriodId);

        $this->accountingService->validateFiscalPeriod($fiscalPeriodId);

        $fiscalYear = $fiscalPeriod->fiscalYear;

        // Get all active assets
        $assets = FixedAsset::where('tenant_id', $this->getTenantId())
            ->where('status', 'active')
            ->whereNotNull('activation_date')
            ->get();

        $postedDepreciations = [];
        $errors = [];

        foreach ($assets as $asset) {
            try {
                // Check if depreciation already posted for this period
                $existingDepreciation = AssetDepreciation::where('tenant_id', $this->getTenantId())
                    ->where('fixed_asset_id', $asset->id)
                    ->where('fiscal_period_id', $fiscalPeriodId)
                    ->first();

                if ($existingDepreciation && $existingDepreciation->is_posted) {
                    continue; // Skip already posted
                }

                // Check if asset was active during this period
                $activationDate = \Carbon\Carbon::parse($asset->activation_date);
                if ($activationDate->gt($fiscalPeriod->end_date)) {
                    continue; // Asset not yet activated
                }

                // Calculate depreciation for this period
                $monthlyDepreciation = $asset->calculateMonthlyDepreciation();
                if ($monthlyDepreciation <= 0) {
                    continue;
                }

                // Check if we've exceeded useful life
                $postedCount = $asset->depreciations()->where('is_posted', true)->count();
                if ($postedCount >= $asset->useful_life_months) {
                    continue; // Fully depreciated
                }

                // Use period end date for depreciation
                $depreciationDate = $fiscalPeriod->end_date;

                // Create or update depreciation record
                if ($existingDepreciation) {
                    $depreciation = $existingDepreciation;
                    $depreciation->update([
                        'amount' => $monthlyDepreciation,
                        'depreciation_date' => $depreciationDate,
                    ]);
                } else {
                    $depreciation = AssetDepreciation::create([
                        'tenant_id' => $this->getTenantId(),
                        'fixed_asset_id' => $asset->id,
                        'fiscal_year_id' => $fiscalYear->id,
                        'fiscal_period_id' => $fiscalPeriodId,
                        'depreciation_date' => $depreciationDate,
                        'amount' => $monthlyDepreciation,
                        'is_posted' => false,
                    ]);
                }

                // Create journal entry
                $entry = $this->createDepreciationJournalEntry($asset, $depreciation, $fiscalPeriod, $fiscalYear, $userId);

                // Update depreciation with journal entry
                $depreciation->update([
                    'journal_entry_id' => $entry->id,
                    'is_posted' => true,
                    'posted_by' => $userId,
                    'posted_at' => now(),
                ]);

                $postedDepreciations[] = [
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->name,
                    'depreciation_id' => $depreciation->id,
                    'amount' => (float) $monthlyDepreciation,
                    'journal_entry_id' => $entry->id,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'fiscal_period' => [
                'id' => $fiscalPeriod->id,
                'name' => $fiscalPeriod->name,
                'code' => $fiscalPeriod->code,
            ],
            'posted_count' => count($postedDepreciations),
            'posted_depreciations' => $postedDepreciations,
            'errors' => $errors,
        ];
    }

    /**
     * Create journal entry for depreciation.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $asset
     * @param  \App\Modules\ERP\Models\AssetDepreciation  $depreciation
     * @param  \App\Modules\ERP\Models\FiscalPeriod  $fiscalPeriod
     * @param  \App\Modules\ERP\Models\FiscalYear  $fiscalYear
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\JournalEntry
     */
    protected function createDepreciationJournalEntry(
        FixedAsset $asset,
        AssetDepreciation $depreciation,
        FiscalPeriod $fiscalPeriod,
        \App\Modules\ERP\Models\FiscalYear $fiscalYear,
        int $userId
    ): JournalEntry {
        return DB::transaction(function () use ($asset, $depreciation, $fiscalPeriod, $fiscalYear, $userId) {
            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $depreciation->depreciation_date,
                'reference_type' => AssetDepreciation::class,
                'reference_id' => $depreciation->id,
                'description' => "Depreciation: {$asset->asset_code} - {$asset->name}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $currency = $asset->currency;

            // Debit: Depreciation Expense
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $asset->depreciation_expense_account_id,
                'currency_id' => $currency->id,
                'debit' => $depreciation->amount,
                'credit' => 0,
                'amount_base' => $depreciation->amount,
                'description' => "Depreciation Expense: {$asset->asset_code}",
                'line_number' => 1,
            ]);

            // Credit: Accumulated Depreciation
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $asset->accumulated_depreciation_account_id,
                'currency_id' => $currency->id,
                'debit' => 0,
                'credit' => $depreciation->amount,
                'amount_base' => $depreciation->amount,
                'description' => "Accumulated Depreciation: {$asset->asset_code}",
                'line_number' => 2,
            ]);

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
        });
    }
}




