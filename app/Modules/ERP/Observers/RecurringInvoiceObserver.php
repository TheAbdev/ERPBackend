<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\RecurringInvoice;

class RecurringInvoiceObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function created(RecurringInvoice $recurringInvoice): void
    {
        $this->auditService->log(
            'create',
            $recurringInvoice,
            null,
            $recurringInvoice->toArray(),
            ['description' => "Created recurring invoice: {$recurringInvoice->name}"]
        );
    }

    public function updated(RecurringInvoice $recurringInvoice): void
    {
        if ($recurringInvoice->wasChanged('is_active')) {
            $oldStatus = $recurringInvoice->getOriginal('is_active');
            $newStatus = $recurringInvoice->is_active;

            $this->auditService->log(
                $newStatus ? 'activate' : 'deactivate',
                $recurringInvoice,
                ['is_active' => $oldStatus],
                ['is_active' => $newStatus],
                ['description' => ($newStatus ? 'Activated' : 'Deactivated')." recurring invoice: {$recurringInvoice->name}"]
            );
        }

        if ($recurringInvoice->wasChanged('last_run_date')) {
            $this->auditService->log(
                'generate',
                $recurringInvoice,
                ['last_run_date' => $recurringInvoice->getOriginal('last_run_date')],
                ['last_run_date' => $recurringInvoice->last_run_date, 'generated_count' => $recurringInvoice->generated_count],
                ['description' => "Generated invoice from recurring template: {$recurringInvoice->name}"]
            );
        }
    }

    public function deleted(RecurringInvoice $recurringInvoice): void
    {
        $this->auditService->log(
            'delete',
            $recurringInvoice,
            $recurringInvoice->toArray(),
            null,
            ['description' => "Deleted recurring invoice: {$recurringInvoice->name}"]
        );
    }
}

