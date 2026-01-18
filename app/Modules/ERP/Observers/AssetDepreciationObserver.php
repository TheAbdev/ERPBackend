<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\AssetDepreciation;

/**
 * Observer for Asset Depreciation audit logging.
 */
class AssetDepreciationObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the AssetDepreciation "created" event.
     *
     * @param  \App\Modules\ERP\Models\AssetDepreciation  $depreciation
     * @return void
     */
    public function created(AssetDepreciation $depreciation): void
    {
        $this->auditService->log(
            'create',
            $depreciation,
            null,
            [
                'amount' => $depreciation->amount,
                'depreciation_date' => $depreciation->depreciation_date?->format('Y-m-d'),
                'is_posted' => $depreciation->is_posted,
            ],
            [
                'description' => "Created depreciation for asset: {$depreciation->fixedAsset->asset_code}",
            ]
        );
    }

    /**
     * Handle the AssetDepreciation "updated" event.
     *
     * @param  \App\Modules\ERP\Models\AssetDepreciation  $depreciation
     * @return void
     */
    public function updated(AssetDepreciation $depreciation): void
    {
        if ($depreciation->wasChanged('is_posted') && $depreciation->is_posted) {
            $asset = $depreciation->fixedAsset;
            $this->auditService->log(
                'post',
                $depreciation,
                ['is_posted' => false],
                ['is_posted' => true],
                [
                    'description' => "Posted depreciation for asset: {$asset->asset_code}",
                    'amount' => $depreciation->amount,
                    'depreciation_date' => $depreciation->depreciation_date?->format('Y-m-d'),
                    'fiscal_period' => $depreciation->fiscalPeriod->name,
                    'posted_by' => $depreciation->posted_by,
                    'journal_entry_id' => $depreciation->journal_entry_id,
                ]
            );
        }
    }
}

