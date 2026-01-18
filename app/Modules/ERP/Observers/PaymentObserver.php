<?php

namespace App\Modules\ERP\Observers;

use App\Core\Services\AuditService;
use App\Modules\ERP\Models\Payment;

/**
 * Observer for Payment audit logging.
 */
class PaymentObserver
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Payment "created" event.
     *
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @return void
     */
    public function created(Payment $payment): void
    {
        $this->auditService->log(
            'create',
            $payment,
            null,
            [
                'payment_number' => $payment->payment_number,
                'type' => $payment->type,
                'amount' => $payment->amount,
                'payment_date' => $payment->payment_date?->format('Y-m-d'),
            ],
            [
                'description' => "Created payment: {$payment->payment_number}",
            ]
        );
    }

    /**
     * Handle the Payment "updated" event.
     *
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @return void
     */
    public function updated(Payment $payment): void
    {
        // Log payment application/reversal if status changed
        if ($payment->wasChanged('status')) {
            $oldStatus = $payment->getOriginal('status');
            $newStatus = $payment->status;

            if ($newStatus === 'applied') {
                $this->auditService->log(
                    'apply',
                    $payment,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Applied payment: {$payment->payment_number}",
                    ]
                );
            } elseif ($newStatus === 'reversed') {
                $this->auditService->log(
                    'reverse',
                    $payment,
                    ['status' => $oldStatus],
                    ['status' => $newStatus],
                    [
                        'description' => "Reversed payment: {$payment->payment_number}",
                    ]
                );
            }
        }
    }
}

