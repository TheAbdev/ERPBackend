<?php

namespace App\Modules\ERP\Traits;

use App\Modules\ERP\Models\StockItem;
use App\Modules\ERP\Models\Warehouse;

/**
 * Trait for models that have stock tracking.
 */
trait HasStock
{
    /**
     * Get stock items for a specific warehouse.
     *
     * @param  int  $warehouseId
     * @return \App\Modules\ERP\Models\StockItem|null
     */
    public function getStockItem(int $warehouseId): ?StockItem
    {
        $variantId = $this instanceof \App\Modules\ERP\Models\ProductVariant ? $this->id : null;

        return StockItem::where('tenant_id', $this->tenant_id)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $this instanceof \App\Modules\ERP\Models\Product ? $this->id : $this->product_id)
            ->where('product_variant_id', $variantId)
            ->first();
    }

    /**
     * Get all stock items across all warehouses.
     * Note: This should be overridden in the model with a proper relationship.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllStockItems()
    {
        $productId = $this instanceof \App\Modules\ERP\Models\Product ? $this->id : $this->product_id;

        return StockItem::where('tenant_id', $this->tenant_id)
            ->where('product_id', $productId)
            ->when($this instanceof \App\Modules\ERP\Models\ProductVariant, function ($query) {
                $query->where('product_variant_id', $this->id);
            })
            ->get();
    }

    /**
     * Get total quantity on hand across all warehouses.
     *
     * @return float
     */
    public function getTotalQuantityOnHand(): float
    {
        $productId = $this instanceof \App\Modules\ERP\Models\Product ? $this->id : $this->product_id;

        return (float) StockItem::where('tenant_id', $this->tenant_id)
            ->where('product_id', $productId)
            ->when($this instanceof \App\Modules\ERP\Models\ProductVariant, function ($query) {
                $query->where('product_variant_id', $this->id);
            })
            ->sum('quantity_on_hand');
    }

    /**
     * Get total reserved quantity across all warehouses.
     *
     * @return float
     */
    public function getTotalReservedQuantity(): float
    {
        $productId = $this instanceof \App\Modules\ERP\Models\Product ? $this->id : $this->product_id;

        return (float) StockItem::where('tenant_id', $this->tenant_id)
            ->where('product_id', $productId)
            ->when($this instanceof \App\Modules\ERP\Models\ProductVariant, function ($query) {
                $query->where('product_variant_id', $this->id);
            })
            ->sum('reserved_quantity');
    }

    /**
     * Get total available quantity across all warehouses.
     *
     * @return float
     */
    public function getTotalAvailableQuantity(): float
    {
        $productId = $this instanceof \App\Modules\ERP\Models\Product ? $this->id : $this->product_id;

        return (float) StockItem::where('tenant_id', $this->tenant_id)
            ->where('product_id', $productId)
            ->when($this instanceof \App\Modules\ERP\Models\ProductVariant, function ($query) {
                $query->where('product_variant_id', $this->id);
            })
            ->sum('available_quantity');
    }

    /**
     * Check if product has stock in any warehouse.
     *
     * @return bool
     */
    public function hasStock(): bool
    {
        return $this->getTotalQuantityOnHand() > 0;
    }

    /**
     * Check if product has available stock in a specific warehouse.
     *
     * @param  int  $warehouseId
     * @param  float  $requiredQuantity
     * @return bool
     */
    public function hasAvailableStock(int $warehouseId, float $requiredQuantity = 0): bool
    {
        $stockItem = $this->getStockItem($warehouseId);

        if (! $stockItem) {
            return false;
        }

        return $stockItem->available_quantity >= $requiredQuantity;
    }
}

