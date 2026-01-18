<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends ErpBaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'product_id',
        'product_variant_id',
        'unit_of_measure',
        'quantity',
        'base_quantity',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'line_total',
        'delivered_quantity',
        'quantity_delivered',
        'quantity_invoiced',
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
            'unit_price' => 'decimal:4',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:4',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'delivered_quantity' => 'decimal:4',
            'quantity_delivered' => 'decimal:4',
            'quantity_invoiced' => 'decimal:4',
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
            $subtotal = $item->quantity * $item->unit_price;
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
     * Get the sales order.
     *
     * @return BelongsTo
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
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
     * Get remaining quantity to deliver.
     *
     * @return float
     */
    public function getRemainingQuantity(): float
    {
        $delivered = $this->quantity_delivered ?? $this->delivered_quantity ?? 0;
        return max(0, $this->base_quantity - $delivered);
    }

    /**
     * Check if item is fully delivered.
     *
     * @return bool
     */
    public function isFullyDelivered(): bool
    {
        $delivered = $this->quantity_delivered ?? $this->delivered_quantity ?? 0;
        return $delivered >= $this->base_quantity;
    }

    /**
     * Get remaining quantity to invoice.
     *
     * @return float
     */
    public function getRemainingQuantityToInvoice(): float
    {
        $delivered = $this->quantity_delivered ?? $this->delivered_quantity ?? 0;
        $invoiced = $this->quantity_invoiced ?? 0;
        return max(0, $delivered - $invoiced);
    }

    /**
     * Check if item is fully invoiced.
     *
     * @return bool
     */
    public function isFullyInvoiced(): bool
    {
        $delivered = $this->quantity_delivered ?? $this->delivered_quantity ?? 0;
        $invoiced = $this->quantity_invoiced ?? 0;
        return $invoiced >= $delivered;
    }
}

