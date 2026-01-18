<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\FixedAsset;
use App\Modules\ERP\Models\FiscalPeriod;

/**
 * Service for generating asset reports.
 */
class AssetReportService extends BaseService
{
    /**
     * Generate Fixed Asset Register.
     *
     * @param  int|null  $fiscalPeriodId
     * @return array
     */
    public function generateAssetRegister(?int $fiscalPeriodId = null): array
    {
        $query = FixedAsset::where('tenant_id', $this->getTenantId())
            ->with(['assetAccount', 'currency', 'fiscalYear', 'fiscalPeriod']);

        if ($fiscalPeriodId) {
            $query->where('fiscal_period_id', $fiscalPeriodId);
        }

        $assets = $query->orderBy('asset_code')->get();

        $register = [];
        $totalAcquisitionCost = 0;
        $totalAccumulatedDepreciation = 0;
        $totalNetBookValue = 0;

        foreach ($assets as $asset) {
            $accumulatedDepreciation = $asset->getAccumulatedDepreciation();
            $netBookValue = $asset->getNetBookValue();

            $register[] = [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'description' => $asset->description,
                'acquisition_date' => $asset->acquisition_date->format('Y-m-d'),
                'activation_date' => $asset->activation_date?->format('Y-m-d'),
                'status' => $asset->status,
                'acquisition_cost' => (float) $asset->acquisition_cost,
                'salvage_value' => (float) $asset->salvage_value,
                'useful_life_months' => $asset->useful_life_months,
                'depreciation_method' => $asset->depreciation_method,
                'monthly_depreciation' => (float) $asset->calculateMonthlyDepreciation(),
                'accumulated_depreciation' => (float) $accumulatedDepreciation,
                'net_book_value' => (float) $netBookValue,
                'remaining_useful_life_months' => $asset->getRemainingUsefulLifeMonths(),
            ];

            $totalAcquisitionCost += $asset->acquisition_cost;
            $totalAccumulatedDepreciation += $accumulatedDepreciation;
            $totalNetBookValue += $netBookValue;
        }

        return [
            'assets' => $register,
            'totals' => [
                'total_acquisition_cost' => (float) $totalAcquisitionCost,
                'total_accumulated_depreciation' => (float) $totalAccumulatedDepreciation,
                'total_net_book_value' => (float) $totalNetBookValue,
            ],
            'count' => count($register),
        ];
    }

    /**
     * Generate Depreciation Schedule Report.
     *
     * @param  int  $assetId
     * @return array
     */
    public function generateDepreciationSchedule(int $assetId): array
    {
        $asset = FixedAsset::where('tenant_id', $this->getTenantId())
            ->with(['depreciations.fiscalPeriod', 'depreciations.journalEntry'])
            ->findOrFail($assetId);

        $depreciationService = app(DepreciationService::class);
        return $depreciationService->generateDepreciationSchedule($asset);
    }

    /**
     * Generate Accumulated Depreciation Summary.
     *
     * @param  int|null  $fiscalPeriodId
     * @return array
     */
    public function generateAccumulatedDepreciationSummary(?int $fiscalPeriodId = null): array
    {
        $fiscalPeriod = null;
        $query = FixedAsset::where('tenant_id', $this->getTenantId())
            ->where('status', 'active')
            ->with(['assetAccount', 'accumulatedDepreciationAccount', 'depreciations']);

        if ($fiscalPeriodId) {
            $fiscalPeriod = FiscalPeriod::where('tenant_id', $this->getTenantId())
                ->findOrFail($fiscalPeriodId);
            $query->where('fiscal_period_id', '<=', $fiscalPeriodId);
        }

        $assets = $query->orderBy('asset_code')->get();

        $summary = [];
        $totalAcquisitionCost = 0;
        $totalAccumulatedDepreciation = 0;
        $totalNetBookValue = 0;

        foreach ($assets as $asset) {
            $accumulatedDepreciation = $asset->getAccumulatedDepreciation();
            $netBookValue = $asset->getNetBookValue();

            $summary[] = [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'acquisition_cost' => (float) $asset->acquisition_cost,
                'accumulated_depreciation' => (float) $accumulatedDepreciation,
                'net_book_value' => (float) $netBookValue,
                'depreciation_percentage' => $asset->acquisition_cost > 0
                    ? round(($accumulatedDepreciation / $asset->acquisition_cost) * 100, 2)
                    : 0,
            ];

            $totalAcquisitionCost += $asset->acquisition_cost;
            $totalAccumulatedDepreciation += $accumulatedDepreciation;
            $totalNetBookValue += $netBookValue;
        }

        return [
            'fiscal_period' => $fiscalPeriodId ? [
                'id' => $fiscalPeriod->id,
                'name' => $fiscalPeriod->name,
                'code' => $fiscalPeriod->code,
            ] : null,
            'assets' => $summary,
            'totals' => [
                'total_acquisition_cost' => (float) $totalAcquisitionCost,
                'total_accumulated_depreciation' => (float) $totalAccumulatedDepreciation,
                'total_net_book_value' => (float) $totalNetBookValue,
            ],
            'count' => count($summary),
        ];
    }
}

