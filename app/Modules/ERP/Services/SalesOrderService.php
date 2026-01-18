<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\InventoryTransaction;
use App\Modules\ERP\Models\SalesOrder;
use App\Modules\ERP\Models\SalesOrderItem;
use App\Modules\ERP\Services\AutoPostingService;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling sales order operations.
 */
class SalesOrderService extends BaseService
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
     * Confirm a sales order and reserve stock.
     *
     * @param  \App\Modules\ERP\Models\SalesOrder  $order
     * @param  int  $userId
     * @return void
     *
     * @throws \Exception
     */
    public function confirmOrder(SalesOrder $order, int $userId): void
    {
        if ($order->status !== 'draft') {
            throw new \Exception('Only draft orders can be confirmed.');
        }

        DB::transaction(function () use ($order, $userId) {
            // Check availability and reserve stock for each item
            foreach ($order->items as $item) {
                if ($item->product->is_tracked) {
                    $reserved = $this->stockMovementService->reserveStock(
                        $order->warehouse_id,
                        $item->product_id,
                        $item->base_quantity,
                        $item->product_variant_id
                    );

                    if (! $reserved) {
                        throw new \Exception("Insufficient stock for product: {$item->product->name}");
                    }
                }
            }

            // Update order status
            $order->update([
                'status' => 'confirmed',
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);
        });
    }

    /**
     * Cancel a sales order and release reserved stock.
     *
     * @param  \App\Modules\ERP\Models\SalesOrder  $order
     * @return void
     *
     * @throws \Exception
     */
    public function cancelOrder(SalesOrder $order): void
    {
        if ($order->status === 'cancelled') {
            throw new \Exception('Order is already cancelled.');
        }

        if ($order->status === 'delivered') {
            throw new \Exception('Cannot cancel a delivered order.');
        }

        DB::transaction(function () use ($order) {
            // Release reserved stock for each item
            if ($order->isConfirmed()) {
                foreach ($order->items as $item) {
                    if ($item->product->is_tracked) {
                        $this->stockMovementService->releaseReservedStock(
                            $order->warehouse_id,
                            $item->product_id,
                            $item->base_quantity,
                            $item->product_variant_id
                        );
                    }
                }
            }

            // Update order status
            $order->update(['status' => 'cancelled']);
        });
    }

    /**
     * Deliver items from a sales order (issue inventory).
     *
     * @param  \App\Modules\ERP\Models\SalesOrder  $order
     * @param  array  $deliveryItems  Array of ['item_id' => quantity]
     * @param  int  $userId
     * @return void
     *
     * @throws \Exception
     */
    public function deliverOrder(SalesOrder $order, array $deliveryItems, int $userId): void
    {
        if (! $order->isConfirmed()) {
            throw new \Exception('Only confirmed orders can be delivered.');
        }

        DB::transaction(function () use ($order, $deliveryItems, $userId) {
            $allDelivered = true;

            foreach ($order->items as $item) {
                $deliveryQuantity = $deliveryItems[$item->id] ?? 0;

                if ($deliveryQuantity <= 0) {
                    if ($item->getRemainingQuantity() > 0) {
                        $allDelivered = false;
                    }
                    continue;
                }

                $remaining = $item->getRemainingQuantity();
                if ($deliveryQuantity > $remaining) {
                    throw new \Exception("Delivery quantity exceeds remaining quantity for item: {$item->product->name}");
                }

                // Issue inventory
                if ($item->product->is_tracked) {
                    // Release reserved quantity first
                    $this->stockMovementService->releaseReservedStock(
                        $order->warehouse_id,
                        $item->product_id,
                        $deliveryQuantity,
                        $item->product_variant_id
                    );

                    // Issue inventory transaction
                    $this->stockMovementService->recordTransaction([
                        'warehouse_id' => $order->warehouse_id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'transaction_type' => 'issue',
                        'reference_type' => SalesOrder::class,
                        'reference_id' => $order->id,
                        'quantity' => -$deliveryQuantity, // Negative for issue
                        'unit_cost' => 0, // Cost will be calculated from FIFO
                        'unit_of_measure_id' => $item->unit_of_measure_id,
                        'base_quantity' => -$deliveryQuantity,
                        'notes' => "Sales order delivery: {$order->order_number}",
                        'created_by' => $userId,
                    ]);
                }

                // Update delivered quantity
                if ($item->quantity_delivered !== null) {
                    $item->increment('quantity_delivered', $deliveryQuantity);
                } else {
                    $item->increment('delivered_quantity', $deliveryQuantity);
                }

                if ($item->getRemainingQuantity() > 0) {
                    $allDelivered = false;
                }
            }

            // Update order status
            if ($allDelivered) {
                $order->update(['status' => 'delivered']);
            } else {
                $order->update(['status' => 'partially_delivered']);
            }

            // Trigger accounting posting
            try {
                $this->autoPostingService->postOnSalesDelivery($order, $deliveryItems, $userId);
            } catch (\Exception $e) {
                // Log error but don't fail the delivery
                \Illuminate\Support\Facades\Log::warning('Failed to post accounting entry for sales delivery', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Calculate order totals.
     *
     * @param  \App\Modules\ERP\Models\SalesOrder  $order
     * @return void
     */
    public function calculateTotals(SalesOrder $order): void
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
}

