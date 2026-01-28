<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundle extends ErpBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_bundles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'description',
        'bundle_price',
        'discount_percentage',
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
            'bundle_price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the main product for this bundle.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the items in this bundle.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_id')->orderBy('sort_order');
    }

    /**
     * Calculate the total price of the bundle.
     *
     * @return float
     */
    public function calculateTotalPrice(): float
    {
        if ($this->bundle_price !== null) {
            return (float) $this->bundle_price;
        }

        $total = 0;
        foreach ($this->items as $item) {
            $price = $item->unit_price ?? $item->product->price ?? 0;
            $total += $price * $item->quantity;
        }

        if ($this->discount_percentage > 0) {
            $total = $total * (1 - ($this->discount_percentage / 100));
        }

        return round($total, 2);
    }

    /**
     * Calculate the savings amount.
     *
     * @return float
     */
    public function calculateSavings(): float
    {
        $totalWithoutDiscount = 0;
        foreach ($this->items as $item) {
            $price = $item->unit_price ?? $item->product->price ?? 0;
            $totalWithoutDiscount += $price * $item->quantity;
        }

        $bundlePrice = $this->calculateTotalPrice();
        return round($totalWithoutDiscount - $bundlePrice, 2);
    }
}

























