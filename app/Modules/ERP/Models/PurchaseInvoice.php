<?php

namespace App\Modules\ERP\Models;

use App\Modules\ERP\Traits\HasDocumentNumber;
use App\Modules\ERP\Traits\HasFiscalPeriod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends ErpBaseModel
{
    use HasDocumentNumber, HasFiscalPeriod;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'purchase_order_id',
        'fiscal_year_id',
        'fiscal_period_id',
        'currency_id',
        'supplier_name',
        'supplier_email',
        'supplier_phone',
        'supplier_address',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'net_amount',
        'tax_amount',
        'tax_breakdown',
        'total',
        'balance_due',
        'notes',
        'created_by',
        'issued_by',
        'issued_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:4',
            'net_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'tax_breakdown' => 'array',
            'total' => 'decimal:4',
            'balance_due' => 'decimal:4',
            'issued_at' => 'datetime',
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

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $invoice->generateDocumentNumber('purchase_invoice');
            }
            if (empty($invoice->balance_due)) {
                $invoice->balance_due = $invoice->total ?? 0;
            }
            // Set fiscal_year_id from fiscal_period_id if not set
            if (empty($invoice->fiscal_year_id) && $invoice->fiscal_period_id) {
                $fiscalPeriod = \App\Modules\ERP\Models\FiscalPeriod::find($invoice->fiscal_period_id);
                if ($fiscalPeriod) {
                    $invoice->fiscal_year_id = $fiscalPeriod->fiscal_year_id;
                }
            }
        });
    }

    /**
     * Get the tenant that owns the invoice.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the purchase order.
     *
     * @return BelongsTo
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
     * Get the user who created the invoice.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who issued the invoice.
     *
     * @return BelongsTo
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'issued_by');
    }

    /**
     * Get the invoice items.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    /**
     * Get the payment allocations.
     *
     * @return HasMany
     */
    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'invoice_id')
            ->where('invoice_type', self::class);
    }

    /**
     * Update the balance due based on payment allocations.
     *
     * @return void
     */
    public function updateBalanceDue(): void
    {
        $totalPaid = $this->paymentAllocations()->sum('amount');
        $this->balance_due = max(0, $this->total - $totalPaid);
        $this->save();

        // Update status based on balance
        if ($this->balance_due <= 0 && $this->status !== 'cancelled') {
            $this->status = 'paid';
        } elseif ($this->balance_due < $this->total && $this->status === 'issued') {
            $this->status = 'partially_paid';
        }
    }

    /**
     * Check if invoice is draft.
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if invoice is issued.
     *
     * @return bool
     */
    public function isIssued(): bool
    {
        return in_array($this->status, ['issued', 'partially_paid', 'paid']);
    }

    /**
     * Check if invoice is partially paid.
     *
     * @return bool
     */
    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid';
    }

    /**
     * Check if invoice is paid.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if invoice can be edited.
     *
     * @return bool
     */
    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    /**
     * Check if invoice can be cancelled.
     *
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return !$this->isCancelled() && !$this->isPaid();
    }
}

