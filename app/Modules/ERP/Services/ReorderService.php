<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\ReorderRule;
use App\Modules\ERP\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class ReorderService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Check and create purchase orders for products that need reordering.
     */
    public function checkAndReorder(): array
    {
        $reorderRules = ReorderRule::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('is_active', true)
            ->with(['product', 'warehouse', 'supplier'])
            ->get();

        $ordersCreated = [];

        foreach ($reorderRules as $rule) {
            $currentStock = $this->getCurrentStock($rule->product_id, $rule->warehouse_id);

            if ($currentStock <= $rule->reorder_point) {
                $order = $this->createPurchaseOrder($rule);
                if ($order) {
                    $ordersCreated[] = $order;
                }
            }
        }

        return $ordersCreated;
    }

    /**
     * Get current stock for product in warehouse.
     */
    protected function getCurrentStock(int $productId, ?int $warehouseId): float
    {
        $query = DB::table('stock_items')
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->where('product_id', $productId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->sum('quantity_on_hand');
    }

    /**
     * Create purchase order for reorder rule.
     */
    protected function createPurchaseOrder(ReorderRule $rule): ?PurchaseOrder
    {
        if (! $rule->supplier_id) {
            return null;
        }

        $supplier = $rule->supplier;
        $fiscalPeriod = $this->getCurrentFiscalPeriod();

        $order = PurchaseOrder::create([
            'tenant_id' => $this->tenantContext->getTenantId(),
            'order_number' => $this->generateOrderNumber(),
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(7)->toDateString(),
            'warehouse_id' => $rule->warehouse_id,
            'currency_id' => $this->getDefaultCurrencyId(),
            'supplier_name' => $supplier->name,
            'supplier_email' => $supplier->email,
            'supplier_phone' => $supplier->phone,
            'supplier_address' => $supplier->address,
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'created_by' => 1, // System user
        ]);

        // Create order item
        $product = $rule->product;
        $quantity = $rule->reorder_quantity;
        $unitPrice = $product->standard_cost ?? 0;

        $order->items()->create([
            'tenant_id' => $this->tenantContext->getTenantId(),
            'product_id' => $product->id,
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
        ]);

        // Update order totals
        $order->update([
            'subtotal' => $quantity * $unitPrice,
            'total_amount' => $quantity * $unitPrice,
        ]);

        return $order;
    }

    /**
     * Generate purchase order number.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'PO-';
        $year = now()->year;
        $lastOrder = PurchaseOrder::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('order_number', 'like', "{$prefix}{$year}%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get default currency ID.
     */
    protected function getDefaultCurrencyId(): int
    {
        return DB::table('currencies')
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->where('is_default', true)
            ->value('id') ?? 1;
    }

    /**
     * Get current fiscal period.
     */
    protected function getCurrentFiscalPeriod()
    {
        return DB::table('fiscal_periods')
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->first();
    }
}

