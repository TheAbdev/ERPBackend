<?php

namespace App\Observers;

use App\Modules\ERP\Models\Product;
use App\Modules\ECommerce\Services\ProductSyncService;
use App\Core\Services\TenantContext;

class ProductObserver
{
    protected ProductSyncService $productSyncService;
    protected TenantContext $tenantContext;

    public function __construct(ProductSyncService $productSyncService, TenantContext $tenantContext)
    {
        $this->productSyncService = $productSyncService;
        $this->tenantContext = $tenantContext;
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Set tenant context
        $this->tenantContext->setTenant($product->tenant);

        // Update product sync status when product is updated
        $this->productSyncService->onProductUpdated($product);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Set tenant context
        $this->tenantContext->setTenant($product->tenant);

        // Hide product from all stores when deleted
        \App\Modules\ECommerce\Models\ProductSync::where('product_id', $product->id)
            ->update(['store_visibility' => false, 'is_synced' => false]);
    }
}







