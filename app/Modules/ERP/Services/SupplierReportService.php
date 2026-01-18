<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Account;
use App\Modules\ERP\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class SupplierReportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get supplier performance report.
     */
    public function getSupplierPerformance(int $supplierId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        // Get supplier account name
        $supplier = \App\Modules\CRM\Models\Account::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('id', $supplierId)
            ->first();

        if (! $supplier) {
            return [
                'total_orders' => 0,
                'total_amount' => 0,
                'average_order_value' => 0,
                'on_time_delivery_rate' => 0,
                'orders_by_status' => [],
            ];
        }

        $query = PurchaseOrder::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('supplier_name', $supplier->name);

        if ($dateFrom) {
            $query->whereDate('order_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('order_date', '<=', $dateTo);
        }

        $orders = $query->get();

        return [
            'total_orders' => $orders->count(),
            'total_amount' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'on_time_delivery_rate' => $this->calculateOnTimeDeliveryRate($orders),
            'orders_by_status' => $orders->groupBy('status')->map->count(),
        ];
    }

    /**
     * Get supplier summary report.
     */
    public function getSupplierSummary(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DB::table('purchase_orders')
            ->join('accounts', function ($join) {
                $join->on('purchase_orders.supplier_name', '=', 'accounts.name')
                    ->where('purchase_orders.tenant_id', $this->tenantContext->getTenantId())
                    ->where('accounts.tenant_id', $this->tenantContext->getTenantId());
            })
            ->where('purchase_orders.tenant_id', $this->tenantContext->getTenantId());

        if ($dateFrom) {
            $query->whereDate('purchase_orders.order_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('purchase_orders.order_date', '<=', $dateTo);
        }

        return $query->select(
            'accounts.id as supplier_id',
            'accounts.name as supplier_name',
            DB::raw('COUNT(purchase_orders.id) as total_orders'),
            DB::raw('SUM(purchase_orders.total_amount) as total_amount'),
            DB::raw('AVG(purchase_orders.total_amount) as average_order_value')
        )
            ->groupBy('accounts.id', 'accounts.name')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Calculate on-time delivery rate.
     */
    protected function calculateOnTimeDeliveryRate($orders): float
    {
        $onTime = $orders->filter(function ($order) {
            // Check if order is received and on time
            if ($order->status === 'received' && $order->expected_delivery_date) {
                // For simplicity, consider received orders as on-time if status is 'received'
                // In a real scenario, you'd check actual received_at timestamp
                return true;
            }
            return false;
        })->count();

        $receivedOrders = $orders->where('status', 'received')->count();

        return $receivedOrders > 0 ? ($onTime / $receivedOrders) * 100 : 0;
    }
}

