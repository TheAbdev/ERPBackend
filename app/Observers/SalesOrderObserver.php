<?php

namespace App\Observers;

use App\Modules\ERP\Models\SalesOrder;
use App\Modules\ECommerce\Services\OrderSyncService;
use App\Core\Services\TenantContext;

class SalesOrderObserver
{
    protected OrderSyncService $orderSyncService;
    protected TenantContext $tenantContext;

    public function __construct(OrderSyncService $orderSyncService, TenantContext $tenantContext)
    {
        $this->orderSyncService = $orderSyncService;
        $this->tenantContext = $tenantContext;
    }

    /**
     * Handle the SalesOrder "updated" event.
     */
    public function updated(SalesOrder $salesOrder): void
    {
        // Set tenant context
        $this->tenantContext->setTenant($salesOrder->tenant);

        // Update ecommerce order status when sales order status changes
        $this->orderSyncService->onSalesOrderUpdated($salesOrder);
    }
}









