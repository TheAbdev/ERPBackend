<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\InventoryBatch;
use App\Modules\ERP\Models\InventoryTransaction;
use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\StockItem;
use App\Modules\ERP\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling stock movements and inventory transactions.
 */
class StockMovementService extends BaseService
{
    /**
     * Record an inventory transaction and update stock.
     *
     * @param  array  $data
     * @return \App\Modules\ERP\Models\InventoryTransaction
     */
    public function recordTransaction(array $data): InventoryTransaction
    {
        return DB::transaction(function () use ($data) {
            // Create transaction
            $transaction = InventoryTransaction::create([
                'tenant_id' => $this->getTenantId(),
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'batch_id' => $data['batch_id'] ?? null,
                'transaction_type' => $data['transaction_type'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'total_cost' => $data['total_cost'] ?? 0,
                'unit_of_measure_id' => $data['unit_of_measure_id'] ?? null,
                'unit_of_measure' => $data['unit_of_measure'] ?? null,
                'base_quantity' => $data['base_quantity'] ?? $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null),
                'transaction_date' => $data['transaction_date'] ?? now(),
            ]);

            // Update stock item
            $this->updateStockItem($transaction);

            // Update batch if applicable
            if ($transaction->batch_id) {
                $this->updateBatch($transaction);
            }

            return $transaction;
        });
    }

    /**
     * Update stock item based on transaction.
     *
     * @param  \App\Modules\ERP\Models\InventoryTransaction  $transaction
     * @return void
     */
    protected function updateStockItem(InventoryTransaction $transaction): void
    {
        $stockItem = StockItem::firstOrCreate(
            [
                'tenant_id' => $this->getTenantId(),
                'warehouse_id' => $transaction->warehouse_id,
                'product_id' => $transaction->product_id,
                'product_variant_id' => $transaction->product_variant_id,
            ],
            [
                'quantity_on_hand' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
                'average_cost' => 0,
                'last_cost' => 0,
            ]
        );

        // Update quantity
        $stockItem->quantity_on_hand += $transaction->base_quantity;
        $stockItem->available_quantity = $stockItem->quantity_on_hand - $stockItem->reserved_quantity;

        // Update costs (FIFO logic)
        if ($transaction->isReceipt() && $transaction->unit_cost > 0) {
            $this->updateFIFOCost($stockItem, $transaction);
            $stockItem->last_cost = $transaction->unit_cost;
        }

        $stockItem->save();
    }

    /**
     * Update FIFO average cost.
     *
     * @param  \App\Modules\ERP\Models\StockItem  $stockItem
     * @param  \App\Modules\ERP\Models\InventoryTransaction  $transaction
     * @return void
     */
    protected function updateFIFOCost(StockItem $stockItem, InventoryTransaction $transaction): void
    {
        $currentQty = $stockItem->quantity_on_hand - $transaction->base_quantity;
        $currentCost = $stockItem->average_cost;
        $newQty = abs($transaction->base_quantity);
        $newCost = $transaction->unit_cost;

        if ($currentQty <= 0) {
            // First receipt
            $stockItem->average_cost = $newCost;
        } else {
            // Weighted average
            $totalValue = ($currentQty * $currentCost) + ($newQty * $newCost);
            $totalQty = $currentQty + $newQty;
            $stockItem->average_cost = $totalQty > 0 ? $totalValue / $totalQty : 0;
        }
    }

    /**
     * Update batch quantity.
     *
     * @param  \App\Modules\ERP\Models\InventoryTransaction  $transaction
     * @return void
     */
    protected function updateBatch(InventoryTransaction $transaction): void
    {
        if (! $transaction->batch_id) {
            return;
        }

        $batch = InventoryBatch::find($transaction->batch_id);
        if (! $batch) {
            return;
        }

        $batch->quantity += $transaction->base_quantity;
        $batch->save();
    }

    /**
     * Reserve stock.
     *
     * @param  int  $warehouseId
     * @param  int  $productId
     * @param  float  $quantity
     * @param  int|null  $productVariantId
     * @return bool
     */
    public function reserveStock(int $warehouseId, int $productId, float $quantity, ?int $productVariantId = null): bool
    {
        $stockItem = StockItem::where('tenant_id', $this->getTenantId())
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $productVariantId)
            ->first();

        if (! $stockItem) {
            return false;
        }

        if ($stockItem->available_quantity < $quantity) {
            return false;
        }

        $stockItem->reserved_quantity += $quantity;
        $stockItem->available_quantity = $stockItem->quantity_on_hand - $stockItem->reserved_quantity;
        $stockItem->save();

        return true;
    }

    /**
     * Release reserved stock.
     *
     * @param  int  $warehouseId
     * @param  int  $productId
     * @param  float  $quantity
     * @param  int|null  $productVariantId
     * @return bool
     */
    public function releaseReservedStock(int $warehouseId, int $productId, float $quantity, ?int $productVariantId = null): bool
    {
        $stockItem = StockItem::where('tenant_id', $this->getTenantId())
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $productVariantId)
            ->first();

        if (! $stockItem || $stockItem->reserved_quantity < $quantity) {
            return false;
        }

        $stockItem->reserved_quantity -= $quantity;
        $stockItem->available_quantity = $stockItem->quantity_on_hand - $stockItem->reserved_quantity;
        $stockItem->save();

        return true;
    }
}

