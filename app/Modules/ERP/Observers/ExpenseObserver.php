<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\Expense;

class ExpenseObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function created(Expense $expense): void
    {
        $this->auditService->log(
            'create',
            $expense,
            null,
            $expense->toArray(),
            ['description' => "Created expense: {$expense->expense_number}"]
        );
    }

    public function updated(Expense $expense): void
    {
        if ($expense->wasChanged('status')) {
            $oldStatus = $expense->getOriginal('status');
            $newStatus = $expense->status;

            if ($newStatus === 'approved' && $oldStatus === 'pending') {
                $this->auditService->log(
                    'approve',
                    $expense,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Approved expense: {$expense->expense_number}",
                        'approved_by' => $expense->approved_by,
                        'approved_at' => $expense->approved_at?->toDateTimeString(),
                    ]
                );
            } elseif ($newStatus === 'rejected' && $oldStatus === 'pending') {
                $this->auditService->log(
                    'reject',
                    $expense,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Rejected expense: {$expense->expense_number}",
                        'rejection_reason' => $expense->rejection_reason,
                    ]
                );
            }
        }
    }

    public function deleted(Expense $expense): void
    {
        $this->auditService->log(
            'delete',
            $expense,
            $expense->toArray(),
            null,
            ['description' => "Deleted expense: {$expense->expense_number}"]
        );
    }
}

