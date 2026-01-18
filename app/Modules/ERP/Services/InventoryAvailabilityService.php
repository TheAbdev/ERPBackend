<?php

namespace App\Modules\ERP\Services;

use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\StockItem;
use App\Modules\ERP\Models\Warehouse;
use Illuminate\Support\Collection;

/**
 * Service for checking inventory availability.
 */
class InventoryAvailabilityService extends BaseService
{
    /**
     * Check availability for a product across all warehouses.
     *
     * @param  int  $productId
     * @param  float  $requiredQuantity
     * @param  int|null  $productVariantId
     * @return array
     */
    public function checkAvailability(int $productId, float $requiredQuantity, ?int $productVariantId = null): array
    {
        $stockItems = StockItem::where('tenant_id', $this->getTenantId())
            ->where('product_id', $productId)
            ->when($productVariantId, fn ($q) => $q->where('product_variant_id', $productVariantId))
            ->where('available_quantity', '>', 0)
            ->with('warehouse')
            ->get();

        $totalAvailable = $stockItems->sum('available_quantity');
        $isAvailable = $totalAvailable >= $requiredQuantity;

        $warehouseAvailability = $stockItems->map(function ($item) {
            return [
                'warehouse_id' => $item->warehouse_id,
                'warehouse_name' => $item->warehouse->name,
                'available_quantity' => $item->available_quantity,
                'quantity_on_hand' => $item->quantity_on_hand,
                'reserved_quantity' => $item->reserved_quantity,
            ];
        });

        return [
            'is_available' => $isAvailable,
            'required_quantity' => $requiredQuantity,
            'total_available' => $totalAvailable,
            'shortage' => max(0, $requiredQuantity - $totalAvailable),
            'warehouses' => $warehouseAvailability,
        ];
    }

    /**
     * Check availability in a specific warehouse.
     *
     * @param  int  $warehouseId
     * @param  int  $productId
     * @param  float  $requiredQuantity
     * @param  int|null  $productVariantId
     * @return array
     */
    public function checkWarehouseAvailability(
        int $warehouseId,
        int $productId,
        float $requiredQuantity,
        ?int $productVariantId = null
    ): array {
        $stockItem = StockItem::where('tenant_id', $this->getTenantId())
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->when($productVariantId, fn ($q) => $q->where('product_variant_id', $productVariantId))
            ->first();

        if (! $stockItem) {
            return [
                'is_available' => false,
                'required_quantity' => $requiredQuantity,
                'available_quantity' => 0,
                'shortage' => $requiredQuantity,
            ];
        }

        $isAvailable = $stockItem->available_quantity >= $requiredQuantity;

        return [
            'is_available' => $isAvailable,
            'required_quantity' => $requiredQuantity,
            'available_quantity' => $stockItem->available_quantity,
            'quantity_on_hand' => $stockItem->quantity_on_hand,
            'reserved_quantity' => $stockItem->reserved_quantity,
            'shortage' => max(0, $requiredQuantity - $stockItem->available_quantity),
        ];
    }

    /**
     * Get low stock products.
     *
     * @param  float  $threshold
     * @param  int|null  $warehouseId
     * @return \Illuminate\Support\Collection
     */
    public function getLowStockProducts(float $threshold = 10, ?int $warehouseId = null): Collection
    {
        $query = StockItem::where('tenant_id', $this->getTenantId())
            ->where('quantity_on_hand', '<=', $threshold)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->with(['product', 'warehouse'])
            ->get();

        return $query->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_sku' => $item->product->sku,
                'warehouse_id' => $item->warehouse_id,
                'warehouse_name' => $item->warehouse->name,
                'quantity_on_hand' => $item->quantity_on_hand,
                'available_quantity' => $item->available_quantity,
            ];
        });
    }

    /**
     * Get out of stock products.
     *
     * @param  int|null  $warehouseId
     * @return \Illuminate\Support\Collection
     */
    public function getOutOfStockProducts(?int $warehouseId = null): Collection
    {
        return $this->getLowStockProducts(0, $warehouseId);
    }
}

