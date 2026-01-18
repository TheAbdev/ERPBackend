<?php

namespace App\Modules\ERP\Traits;

use App\Modules\ERP\Models\InventoryTransaction;

/**
 * Trait for models that have inventory transactions.
 */
trait HasInventoryTransactions
{
    /**
     * Get inventory transactions for the model.
     * Note: This should be overridden in the model with a proper relationship.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllInventoryTransactions()
    {
        $productId = $this instanceof \App\Modules\ERP\Models\Product ? $this->id : $this->product_id;

        return InventoryTransaction::where('tenant_id', $this->tenant_id)
            ->where('product_id', $productId)
            ->when($this instanceof \App\Modules\ERP\Models\ProductVariant, function ($query) {
                $query->where('product_variant_id', $this->id);
            })
            ->get();
    }

    /**
     * Get transactions by type.
     *
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTransactionsByType(string $type)
    {
        $productId = $this instanceof \App\Modules\ERP\Models\Product ? $this->id : $this->product_id;

        return InventoryTransaction::where('tenant_id', $this->tenant_id)
            ->where('product_id', $productId)
            ->when($this instanceof \App\Modules\ERP\Models\ProductVariant, function ($query) {
                $query->where('product_variant_id', $this->id);
            })
            ->where('transaction_type', $type)
            ->get();
    }

    /**
     * Get transactions for a specific warehouse.
     *
     * @param  int  $warehouseId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTransactionsForWarehouse(int $warehouseId)
    {
        $productId = $this instanceof \App\Modules\ERP\Models\Product ? $this->id : $this->product_id;

        return InventoryTransaction::where('tenant_id', $this->tenant_id)
            ->where('product_id', $productId)
            ->when($this instanceof \App\Modules\ERP\Models\ProductVariant, function ($query) {
                $query->where('product_variant_id', $this->id);
            })
            ->where('warehouse_id', $warehouseId)
            ->get();
    }
}

