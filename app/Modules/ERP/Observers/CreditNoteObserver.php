<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\CreditNote;

class CreditNoteObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function created(CreditNote $creditNote): void
    {
        $this->auditService->log(
            'create',
            $creditNote,
            null,
            $creditNote->toArray(),
            ['description' => "Created credit note: {$creditNote->credit_note_number}"]
        );
    }

    public function updated(CreditNote $creditNote): void
    {
        if ($creditNote->wasChanged('status')) {
            $oldStatus = $creditNote->getOriginal('status');
            $newStatus = $creditNote->status;

            if ($newStatus === 'issued' && $oldStatus === 'draft') {
                $this->auditService->log(
                    'issue',
                    $creditNote,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Issued credit note: {$creditNote->credit_note_number}",
                        'issued_by' => $creditNote->issued_by,
                        'issued_at' => $creditNote->issued_at?->toDateTimeString(),
                    ]
                );
            }
        }
    }

    public function deleted(CreditNote $creditNote): void
    {
        $this->auditService->log(
            'delete',
            $creditNote,
            $creditNote->toArray(),
            null,
            ['description' => "Deleted credit note: {$creditNote->credit_note_number}"]
        );
    }
}

