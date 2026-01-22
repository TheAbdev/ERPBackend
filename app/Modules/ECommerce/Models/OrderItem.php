<?php

namespace App\Modules\ECommerce\Models;

use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends ECommerceBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_order_items';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the order.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

