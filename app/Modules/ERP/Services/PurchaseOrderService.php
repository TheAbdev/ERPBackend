<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\InventoryBatch;
use App\Modules\ERP\Models\InventoryTransaction;
use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\PurchaseOrder;
use App\Modules\ERP\Models\PurchaseOrderItem;
use App\Modules\ERP\Services\AutoPostingService;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling purchase order operations.
 */
class PurchaseOrderService extends BaseService
{
    protected StockMovementService $stockMovementService;
    protected AutoPostingService $autoPostingService;

    public function __construct(
        TenantContext $tenantContext,
        StockMovementService $stockMovementService,
        AutoPostingService $autoPostingService
    ) {
        parent::__construct($tenantContext);
        $this->stockMovementService = $stockMovementService;
        $this->autoPostingService = $autoPostingService;
    }

    /**
     * Confirm a purchase order.
     *
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $order
     * @param  int  $userId
     * @return void
     *
     * @throws \Exception
     */
    public function confirmOrder(PurchaseOrder $order, int $userId): void
    {
        if ($order->status !== 'draft') {
            throw new \Exception('Only draft orders can be confirmed.');
        }

        $order->update([
            'status' => 'confirmed',
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Cancel a purchase order.
     *
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $order
     * @return void
     *
     * @throws \Exception
     */
    public function cancelOrder(PurchaseOrder $order): void
    {
        if ($order->status === 'cancelled') {
            throw new \Exception('Order is already cancelled.');
        }

        if ($order->status === 'received') {
            throw new \Exception('Cannot cancel a fully received order.');
        }

        $order->update(['status' => 'cancelled']);
    }

    /**
     * Receive items from a purchase order (receive inventory).
     *
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $order
     * @param  array  $receiveItems  Array of ['item_id' => ['quantity' => qty, 'unit_cost' => cost, 'batch_data' => []]]
     * @param  int  $userId
     * @return void
     *
     * @throws \Exception
     */
    public function receiveOrder(PurchaseOrder $order, array $receiveItems, int $userId): void
    {
        if (! $order->isConfirmed()) {
            throw new \Exception('Only confirmed orders can be received.');
        }

        DB::transaction(function () use ($order, $receiveItems, $userId) {
            $allReceived = true;

            foreach ($order->items as $item) {
                $receiveData = $receiveItems[$item->id] ?? null;

                if (! $receiveData || ($receiveData['quantity'] ?? 0) <= 0) {
                    if ($item->getRemainingQuantity() > 0) {
                        $allReceived = false;
                    }
                    continue;
                }

                $receiveQuantity = $receiveData['quantity'];
                $unitCost = $receiveData['unit_cost'] ?? $item->unit_cost;
                $batchData = $receiveData['batch_data'] ?? null;

                $remaining = $item->getRemainingQuantity();
                if ($receiveQuantity > $remaining) {
                    throw new \Exception("Receive quantity exceeds remaining quantity for item: {$item->product->name}");
                }

                // Create batch if product is batch-tracked
                $batchId = null;
                if ($item->product->is_batch_tracked && $batchData) {
                    $batch = InventoryBatch::create([
                        'tenant_id' => $this->getTenantId(),
                        'product_id' => $item->product_id,
                        'warehouse_id' => $order->warehouse_id,
                        'batch_number' => $batchData['batch_number'] ?? $this->generateBatchNumber($item->product_id),
                        'lot_number' => $batchData['lot_number'] ?? null,
                        'manufacturing_date' => $batchData['manufacturing_date'] ?? null,
                        'expiry_date' => $batchData['expiry_date'] ?? null,
                        'quantity' => $receiveQuantity,
                        'unit_cost' => $unitCost,
                        'received_date' => now(),
                    ]);
                    $batchId = $batch->id;
                }

                // Receive inventory transaction
                if ($item->product->is_tracked) {
                    $this->stockMovementService->recordTransaction([
                        'warehouse_id' => $order->warehouse_id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'batch_id' => $batchId,
                        'transaction_type' => 'receipt',
                        'reference_type' => PurchaseOrder::class,
                        'reference_id' => $order->id,
                        'quantity' => $receiveQuantity,
                        'unit_cost' => $unitCost,
                        'unit_of_measure_id' => $item->unit_of_measure_id,
                        'base_quantity' => $receiveQuantity,
                        'notes' => "Purchase order receipt: {$order->order_number}",
                        'created_by' => $userId,
                    ]);
                }

                // Update received quantity
                $item->increment('received_quantity', $receiveQuantity);

                if ($item->getRemainingQuantity() > 0) {
                    $allReceived = false;
                }
            }

            // Update order status
            if ($allReceived) {
                $order->update(['status' => 'received']);
            } else {
                $order->update(['status' => 'partially_received']);
            }

            // Trigger accounting posting
            try {
                $this->autoPostingService->postOnPurchaseReceipt($order, $receiveItems, $userId);
            } catch (\Exception $e) {
                // Log error but don't fail the receipt
                \Illuminate\Support\Facades\Log::warning('Failed to post accounting entry for purchase receipt', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Calculate order totals.
     *
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $order
     * @return void
     */
    public function calculateTotals(PurchaseOrder $order): void
    {
        $subtotal = $order->items->sum('line_total');
        $taxAmount = $order->items->sum('tax_amount');
        $discountAmount = $order->items->sum('discount_amount');
        $totalAmount = $subtotal;

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Generate a batch number for a product.
     *
     * @param  int  $productId
     * @return string
     */
    protected function generateBatchNumber(int $productId): string
    {
        $product = Product::find($productId);
        $count = InventoryBatch::where('tenant_id', $this->getTenantId())
            ->where('product_id', $productId)
            ->count();

        return strtoupper(substr($product->sku, 0, 3)) . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}

