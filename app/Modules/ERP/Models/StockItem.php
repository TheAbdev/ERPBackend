<?php

namespace App\Modules\ERP\Models;

use App\Modules\ERP\Traits\HasInventoryTransactions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItem extends ErpBaseModel
{
    use HasInventoryTransactions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'quantity_on_hand',
        'reserved_quantity',
        'available_quantity',
        'average_cost',
        'last_cost',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'available_quantity' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'last_cost' => 'decimal:4',
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

        static::saving(function ($stockItem) {
            // Calculate available quantity
            $stockItem->available_quantity = $stockItem->quantity_on_hand - $stockItem->reserved_quantity;
        });
    }

    /**
     * Get the tenant that owns the stock item.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the warehouse.
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
}

