<?php

namespace App\Modules\ERP\Models;

use App\Modules\ERP\Traits\HasDocumentNumber;
use App\Modules\ERP\Traits\HasFiscalPeriod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends ErpBaseModel
{
    use HasDocumentNumber, HasFiscalPeriod;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'payment_number',
        'type',
        'fiscal_year_id',
        'fiscal_period_id',
        'currency_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
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

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = $payment->generateDocumentNumber('payment');
            }
            // Set fiscal_year_id from fiscal_period_id if not set
            if (empty($payment->fiscal_year_id) && $payment->fiscal_period_id) {
                $fiscalPeriod = \App\Modules\ERP\Models\FiscalPeriod::find($payment->fiscal_period_id);
                if ($fiscalPeriod) {
                    $payment->fiscal_year_id = $fiscalPeriod->fiscal_year_id;
                }
            }
        });
    }

    /**
     * Get the tenant that owns the payment.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the fiscal year.
     *
     * @return BelongsTo
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Get the currency.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user who created the payment.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the reference model (polymorphic).
     *
     * @return MorphTo
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Get the payment allocations.
     *
     * @return HasMany
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Get the payment gateway transactions.
     *
     * @return HasMany
     */
    public function gatewayTransactions(): HasMany
    {
        return $this->hasMany(PaymentGatewayTransaction::class);
    }

    /**
     * Check if payment is incoming.
     *
     * @return bool
     */
    public function isIncoming(): bool
    {
        return $this->type === 'incoming';
    }

    /**
     * Check if payment is outgoing.
     *
     * @return bool
     */
    public function isOutgoing(): bool
    {
        return $this->type === 'outgoing';
    }

    /**
     * Get total allocated amount.
     *
     * @return float
     */
    public function getTotalAllocated(): float
    {
        return (float) $this->allocations()->sum('amount');
    }

    /**
     * Get unallocated amount.
     *
     * @return float
     */
    public function getUnallocatedAmount(): float
    {
        return $this->amount - $this->getTotalAllocated();
    }
}

