<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRate extends ErpBaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'rate',
        'type',
        'account_id',
        'is_active',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the tax rate.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the tax account.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get sales invoice items using this tax rate.
     *
     * @return HasMany
     */
    public function salesInvoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    /**
     * Get purchase invoice items using this tax rate.
     *
     * @return HasMany
     */
    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    /**
     * Check if tax rate is for sales.
     *
     * @return bool
     */
    public function isForSales(): bool
    {
        return in_array($this->type, ['sales', 'both']);
    }

    /**
     * Check if tax rate is for purchases.
     *
     * @return bool
     */
    public function isForPurchases(): bool
    {
        return in_array($this->type, ['purchase', 'both']);
    }

    /**
     * Calculate tax amount from net amount.
     *
     * @param  float  $netAmount
     * @return float
     */
    public function calculateTax(float $netAmount): float
    {
        return round($netAmount * ($this->rate / 100), 2);
    }

    /**
     * Calculate gross amount from net amount.
     *
     * @param  float  $netAmount
     * @return float
     */
    public function calculateGross(float $netAmount): float
    {
        return round($netAmount + $this->calculateTax($netAmount), 2);
    }
}

