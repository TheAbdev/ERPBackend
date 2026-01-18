<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundleItem extends ErpBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_bundle_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'bundle_id',
        'product_id',
        'quantity',
        'unit_price',
        'sort_order',
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
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the bundle that owns this item.
     *
     * @return BelongsTo
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id');
    }

    /**
     * Get the product in this bundle item.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}




