<?php

namespace App\Modules\CRM\Observers;

use App\Core\Services\AuditService;
use App\Modules\CRM\Models\Deal;

/**
 * Observer for Deal audit logging (stage changes and status transitions).
 */
class DealObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Deal "updated" event.
     *
     * @param  \App\Modules\CRM\Models\Deal  $deal
     * @return void
     */
    public function updated(Deal $deal): void
    {
        // Log stage changes
        if ($deal->wasChanged('stage_id')) {
            $oldStageId = $deal->getOriginal('stage_id');
            $newStageId = $deal->stage_id;

            $this->auditService->log(
                'update',
                $deal,
                ['stage_id' => $oldStageId],
                ['stage_id' => $newStageId],
                [
                    'description' => "Deal stage changed: {$deal->title}",
                    'from_stage_id' => $oldStageId,
                    'to_stage_id' => $newStageId,
                    'from_stage_name' => $deal->getOriginal('stage')?->name,
                    'to_stage_name' => $deal->stage?->name,
                ]
            );
        }

        // Log status transitions
        if ($deal->wasChanged('status')) {
            $oldStatus = $deal->getOriginal('status');
            $newStatus = $deal->status;

            $this->auditService->log(
                'update',
                $deal,
                ['status' => $oldStatus],
                ['status' => $newStatus],
                [
                    'description' => "Deal status changed: {$deal->title} ({$oldStatus} → {$newStatus})",
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                ]
            );
        }
    }
}

