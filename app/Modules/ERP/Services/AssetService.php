<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\FixedAsset;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use App\Modules\ERP\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling fixed asset operations.
 */
class AssetService extends BaseService
{
    protected AccountingService $accountingService;
    protected WorkflowService $workflowService;

    public function __construct(
        TenantContext $tenantContext,
        AccountingService $accountingService,
        WorkflowService $workflowService
    ) {
        parent::__construct($tenantContext);
        $this->accountingService = $accountingService;
        $this->workflowService = $workflowService;
    }

    /**
     * Activate a fixed asset.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $asset
     * @param  int  $userId
     * @param  string|null  $creditAccountCode  Account code to credit (AP, CASH, etc.)
     * @return \App\Modules\ERP\Models\JournalEntry|null
     *
     * @throws \Exception
     */
    public function activateAsset(FixedAsset $asset, int $userId, ?string $creditAccountCode = 'AP'): ?JournalEntry
    {
        if (!$asset->isDraft()) {
            throw new \Exception('Only draft assets can be activated.');
        }

        // Check if workflow approval is required
        if ($this->workflowService->requiresApproval($asset)) {
            // Start workflow instead of activating directly
            $this->workflowService->startWorkflow($asset, $userId);

            // Update asset status to pending_approval
            $asset->update(['status' => 'pending_approval']);

            throw new \Exception('Asset activation requires approval. Workflow has been initiated.');
        }

        // Validate fiscal period
        if ($asset->fiscal_period_id) {
            $this->accountingService->validateFiscalPeriod($asset->fiscal_period_id);
        }

        // Get accounts
        $assetAccount = $asset->assetAccount;
        $creditAccount = $this->getAccountByCode($creditAccountCode);

        if (!$assetAccount || !$creditAccount) {
            throw new \Exception('Required accounts not configured.');
        }

        return DB::transaction(function () use ($asset, $userId, $assetAccount, $creditAccount) {
            // Determine fiscal period from acquisition date
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($asset->acquisition_date);
            $fiscalYear = $fiscalPeriod->fiscalYear;

            // Update asset status
            $asset->update([
                'status' => 'active',
                'activation_date' => $asset->acquisition_date,
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'activated_by' => $userId,
            ]);

            // Create journal entry
            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $asset->acquisition_date,
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'description' => "Asset Activation: {$asset->asset_code} - {$asset->name}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $currency = $asset->currency;

            // Debit: Asset Account
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'currency_id' => $currency->id,
                'debit' => $asset->acquisition_cost,
                'credit' => 0,
                'amount_base' => $asset->acquisition_cost,
                'description' => "Asset: {$asset->asset_code}",
                'line_number' => 1,
            ]);

            // Credit: AP / Cash
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAccount->id,
                'currency_id' => $currency->id,
                'debit' => 0,
                'credit' => $asset->acquisition_cost,
                'amount_base' => $asset->acquisition_cost,
                'description' => "Asset Purchase: {$asset->asset_code}",
                'line_number' => 2,
            ]);

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
        });
    }

    /**
     * Dispose a fixed asset.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $asset
     * @param  float  $disposalAmount
     * @param  \Carbon\Carbon|string  $disposalDate
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\JournalEntry|null
     *
     * @throws \Exception
     */
    public function disposeAsset(FixedAsset $asset, float $disposalAmount, $disposalDate, int $userId): ?JournalEntry
    {
        if (!$asset->isActive()) {
            throw new \Exception('Only active assets can be disposed.');
        }

        $disposalDate = is_string($disposalDate) ? \Carbon\Carbon::parse($disposalDate) : $disposalDate;

        // Validate fiscal period
        $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($disposalDate);
        $this->accountingService->validateFiscalPeriod($fiscalPeriod->id);

        // Get accounts
        $assetAccount = $asset->assetAccount;
        $accumulatedDepreciationAccount = $asset->accumulatedDepreciationAccount;
        $cashAccount = $this->getAccountByCode('CASH') ?? $this->getAccountByCode('BANK');
        $gainLossAccount = $this->getAccountByCode('GAIN_LOSS') ?? $this->getAccountByCode('EXP');

        if (!$assetAccount || !$accumulatedDepreciationAccount || !$cashAccount) {
            throw new \Exception('Required accounts not configured.');
        }

        return DB::transaction(function () use ($asset, $disposalAmount, $disposalDate, $userId, $assetAccount, $accumulatedDepreciationAccount, $cashAccount, $gainLossAccount, $fiscalPeriod) {
            $fiscalYear = $fiscalPeriod->fiscalYear;

            // Calculate accumulated depreciation
            $accumulatedDepreciation = $asset->getAccumulatedDepreciation();
            $netBookValue = $asset->getNetBookValue();
            $gainLoss = $disposalAmount - $netBookValue;

            // Update asset status
            $asset->update([
                'status' => 'disposed',
                'disposal_date' => $disposalDate,
                'disposal_amount' => $disposalAmount,
                'disposed_by' => $userId,
            ]);

            // Create journal entry
            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $disposalDate,
                'reference_type' => FixedAsset::class,
                'reference_id' => $asset->id,
                'description' => "Asset Disposal: {$asset->asset_code} - {$asset->name}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $currency = $asset->currency;
            $lineNumber = 1;

            // Debit: Cash (disposal amount)
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $cashAccount->id,
                'currency_id' => $currency->id,
                'debit' => $disposalAmount,
                'credit' => 0,
                'amount_base' => $disposalAmount,
                'description' => "Asset Disposal: {$asset->asset_code}",
                'line_number' => $lineNumber++,
            ]);

            // Debit: Accumulated Depreciation (reverse)
            if ($accumulatedDepreciation > 0) {
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accumulatedDepreciationAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => $accumulatedDepreciation,
                    'credit' => 0,
                    'amount_base' => $accumulatedDepreciation,
                    'description' => "Accumulated Depreciation: {$asset->asset_code}",
                    'line_number' => $lineNumber++,
                ]);
            }

            // Credit: Asset Account (original cost)
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $assetAccount->id,
                'currency_id' => $currency->id,
                'debit' => 0,
                'credit' => $asset->acquisition_cost,
                'amount_base' => $asset->acquisition_cost,
                'description' => "Asset Disposal: {$asset->asset_code}",
                'line_number' => $lineNumber++,
            ]);

            // Gain or Loss on Disposal
            if (abs($gainLoss) > 0.01) {
                if ($gainLoss > 0) {
                    // Gain: Credit Gain/Loss account
                    JournalEntryLine::create([
                        'tenant_id' => $this->getTenantId(),
                        'journal_entry_id' => $entry->id,
                        'account_id' => $gainLossAccount->id,
                        'currency_id' => $currency->id,
                        'debit' => 0,
                        'credit' => abs($gainLoss),
                        'amount_base' => abs($gainLoss),
                        'description' => "Gain on Disposal: {$asset->asset_code}",
                        'line_number' => $lineNumber++,
                    ]);
                } else {
                    // Loss: Debit Gain/Loss account
                    JournalEntryLine::create([
                        'tenant_id' => $this->getTenantId(),
                        'journal_entry_id' => $entry->id,
                        'account_id' => $gainLossAccount->id,
                        'currency_id' => $currency->id,
                        'debit' => abs($gainLoss),
                        'credit' => 0,
                        'amount_base' => abs($gainLoss),
                        'description' => "Loss on Disposal: {$asset->asset_code}",
                        'line_number' => $lineNumber++,
                    ]);
                }
            }

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
        });
    }

    /**
     * Get account by code.
     *
     * @param  string  $code
     * @return \App\Modules\ERP\Models\Account|null
     */
    protected function getAccountByCode(string $code): ?Account
    {
        return Account::where('tenant_id', $this->getTenantId())
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }
}

