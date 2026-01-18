<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\PurchaseInvoice;
use App\Modules\ERP\Models\SalesInvoice;

/**
 * Observer for Invoice audit logging.
 */
class InvoiceObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the SalesInvoice "updated" event.
     *
     * @param  \App\Modules\ERP\Models\SalesInvoice  $invoice
     * @return void
     */
    public function updated(SalesInvoice $invoice): void
    {
        if ($invoice->wasChanged('status')) {
            $oldStatus = $invoice->getOriginal('status');
            $newStatus = $invoice->status;

            if ($newStatus === 'issued' && $oldStatus === 'draft') {
                $this->auditService->log(
                    'issue',
                    $invoice,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Issued sales invoice: {$invoice->invoice_number}",
                        'issued_by' => $invoice->issued_by,
                        'issued_at' => $invoice->issued_at?->toDateTimeString(),
                    ]
                );
            } elseif ($newStatus === 'cancelled') {
                $this->auditService->log(
                    'cancel',
                    $invoice,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Cancelled sales invoice: {$invoice->invoice_number}",
                    ]
                );
            }
        }
    }
}

/**
 * Observer for Purchase Invoice audit logging.
 */
class PurchaseInvoiceObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the PurchaseInvoice "updated" event.
     *
     * @param  \App\Modules\ERP\Models\PurchaseInvoice  $invoice
     * @return void
     */
    public function updated(PurchaseInvoice $invoice): void
    {
        if ($invoice->wasChanged('status')) {
            $oldStatus = $invoice->getOriginal('status');
            $newStatus = $invoice->status;

            if ($newStatus === 'issued' && $oldStatus === 'draft') {
                $this->auditService->log(
                    'issue',
                    $invoice,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Issued purchase invoice: {$invoice->invoice_number}",
                        'issued_by' => $invoice->issued_by,
                        'issued_at' => $invoice->issued_at?->toDateTimeString(),
                    ]
                );
            } elseif ($newStatus === 'cancelled') {
                $this->auditService->log(
                    'cancel',
                    $invoice,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Cancelled purchase invoice: {$invoice->invoice_number}",
                    ]
                );
            }
        }
    }
}

