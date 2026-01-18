<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentAllocation extends ErpBaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'payment_id',
        'invoice_type',
        'invoice_id',
        'amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($allocation) {
            // Update invoice balance when allocation is saved
            $invoice = $allocation->invoice;
            if ($invoice) {
                $invoice->updateBalanceDue();
            }
        });

        static::deleted(function ($allocation) {
            // Update invoice balance when allocation is deleted
            $invoice = $allocation->invoice;
            if ($invoice) {
                $invoice->updateBalanceDue();
            }
        });
    }

    /**
     * Get the tenant that owns the allocation.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the payment.
     *
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the invoice (polymorphic).
     *
     * @return MorphTo
     */
    public function invoice(): MorphTo
    {
        return $this->morphTo('invoice', 'invoice_type', 'invoice_id');
    }
}

