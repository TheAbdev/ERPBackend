<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends ErpBaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'product_id',
        'product_variant_id',
        'unit_of_measure',
        'quantity',
        'base_quantity',
        'unit_cost',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'line_total',
        'received_quantity',
        'notes',
        'line_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'base_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:4',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'received_quantity' => 'decimal:4',
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

        static::saving(function ($item) {
            // Calculate line total
            $subtotal = $item->quantity * $item->unit_cost;
            $discount = $item->discount_amount ?: ($subtotal * $item->discount_percentage / 100);
            $taxable = $subtotal - $discount;
            $tax = $item->tax_amount ?: ($taxable * $item->tax_percentage / 100);
            $item->line_total = $taxable + $tax;
        });
    }

    /**
     * Get the tenant that owns the order item.
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
     * Get the product.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant.
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }


    /**
     * Get remaining quantity to receive.
     *
     * @return float
     */
    public function getRemainingQuantity(): float
    {
        return max(0, $this->base_quantity - $this->received_quantity);
    }

    /**
     * Check if item is fully received.
     *
     * @return bool
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->base_quantity;
    }
}

