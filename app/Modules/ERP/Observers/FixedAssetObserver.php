<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\FixedAsset;

/**
 * Observer for Fixed Asset audit logging.
 */
class FixedAssetObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the FixedAsset "updated" event.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $asset
     * @return void
     */
    public function updated(FixedAsset $asset): void
    {
        if ($asset->wasChanged('status')) {
            $oldStatus = $asset->getOriginal('status');
            $newStatus = $asset->status;

            if ($newStatus === 'active' && $oldStatus === 'draft') {
                $this->auditService->log(
                    'activate',
                    $asset,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Activated asset: {$asset->asset_code}",
                        'activated_by' => $asset->activated_by,
                        'activation_date' => $asset->activation_date?->format('Y-m-d'),
                    ]
                );
            } elseif ($newStatus === 'disposed') {
                $this->auditService->log(
                    'dispose',
                    $asset,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Disposed asset: {$asset->asset_code}",
                        'disposed_by' => $asset->disposed_by,
                        'disposal_date' => $asset->disposal_date?->format('Y-m-d'),
                        'disposal_amount' => $asset->disposal_amount,
                    ]
                );
            }
        }
    }
}

