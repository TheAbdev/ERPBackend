<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\JournalEntry;

/**
 * Observer for Journal Entry audit logging.
 */
class JournalEntryObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the JournalEntry "created" event.
     *
     * @param  \App\Modules\ERP\Models\JournalEntry  $journalEntry
     * @return void
     */
    public function created(JournalEntry $journalEntry): void
    {
        $this->auditService->log(
            'create',
            $journalEntry,
            null,
            [
                'entry_number' => $journalEntry->entry_number,
                'entry_date' => $journalEntry->entry_date?->format('Y-m-d'),
                'description' => $journalEntry->description,
                'status' => $journalEntry->status,
            ],
            [
                'description' => "Created journal entry: {$journalEntry->entry_number}",
            ]
        );
    }

    /**
     * Handle the JournalEntry "updated" event.
     *
     * @param  \App\Modules\ERP\Models\JournalEntry  $journalEntry
     * @return void
     */
    public function updated(JournalEntry $journalEntry): void
    {
        // Log status changes (post, cancel)
        if ($journalEntry->wasChanged('status')) {
            $oldStatus = $journalEntry->getOriginal('status');
            $newStatus = $journalEntry->status;

            if ($newStatus === 'posted' && $oldStatus !== 'posted') {
                $this->auditService->log(
                    'post',
                    $journalEntry,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Posted journal entry: {$journalEntry->entry_number}",
                        'posted_by' => $journalEntry->posted_by,
                        'posted_at' => $journalEntry->posted_at?->toDateTimeString(),
                    ]
                );
            } elseif ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->auditService->log(
                    'cancel',
                    $journalEntry,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Cancelled journal entry: {$journalEntry->entry_number}",
                    ]
                );
            }
        }
    }
}

