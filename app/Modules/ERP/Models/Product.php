<?php

namespace App\Modules\ERP\Models;

use App\Modules\ERP\Traits\HasStock;
use App\Modules\ERP\Traits\HasInventoryTransactions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends ErpBaseModel
{
    use HasStock, HasInventoryTransactions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'description',
        'barcode',
        'unit_of_measure',
        'is_tracked',
        'is_serialized',
        'is_batch_tracked',
        'type',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_tracked' => 'boolean',
            'is_serialized' => 'boolean',
            'is_batch_tracked' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the product.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the category that owns the product.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }


    /**
     * Get the product variants.
     *
     * @return HasMany
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the bundles that include this product.
     *
     * @return HasMany
     */
    public function bundles(): HasMany
    {
        return $this->hasMany(ProductBundle::class);
    }

    /**
     * Get the bundle items that include this product.
     *
     * @return HasMany
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class);
    }

    /**
     * Get the stock items for the product.
     *
     * @return HasMany
     */
    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    /**
     * Get the inventory batches for the product.
     *
     * @return HasMany
     */
    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    /**
     * Get the serial numbers for the product.
     *
     * @return HasMany
     */
    public function serialNumbers(): HasMany
    {
        return $this->hasMany(InventorySerial::class);
    }

    /**
     * Get the reorder rules for the product.
     *
     * @return HasMany
     */
    public function reorderRules(): HasMany
    {
        return $this->hasMany(ReorderRule::class);
    }
}

