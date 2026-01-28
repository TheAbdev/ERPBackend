<?php

namespace App\Modules\ECommerce\Models;

use App\Modules\ERP\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSync extends ECommerceBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecommerce_product_sync';

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
        'product_id',
        'store_id',
        'is_synced',
        'store_visibility',
        'ecommerce_price',
        'ecommerce_images',
        'ecommerce_description',
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
            'is_synced' => 'boolean',
            'store_visibility' => 'boolean',
            'ecommerce_price' => 'decimal:2',
            'ecommerce_images' => 'string',
        ];
    }

    /**
     * Get the tenant that owns the product sync.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
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
     * Get the store.
     *
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

