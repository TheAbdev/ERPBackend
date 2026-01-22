<?php

namespace App\Modules\ECommerce\Services;

use App\Core\Services\TenantContext;
use App\Modules\ECommerce\Models\ProductSync;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ERP\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductSyncService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Sync product to store.
     *
     * @param  Product  $product
     * @param  Store  $store
     * @param  array  $options
     * @return ProductSync
     */
    public function syncProduct(Product $product, Store $store, array $options = []): ProductSync
    {
        $sync = ProductSync::updateOrCreate(
            [
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'store_id' => $store->id,
            ],
            [
                'is_synced' => true,
                'store_visibility' => $options['store_visibility'] ?? true,
                'ecommerce_price' => $options['ecommerce_price'] ?? null,
                'ecommerce_description' => $options['ecommerce_description'] ?? null,
                'sort_order' => $options['sort_order'] ?? 0,
            ]
        );

        // Update ecommerce_images separately if provided (to handle null values correctly)
        if (array_key_exists('ecommerce_images', $options)) {
            $sync->ecommerce_images = $options['ecommerce_images'];
            $sync->save();
        }

        return $sync;
    }

    /**
     * Sync all active products to store.
     *
     * @param  Store  $store
     * @return int
     */
    public function syncAllProducts(Store $store): int
    {
        $products = Product::where('tenant_id', $store->tenant_id)
            ->where('is_active', true)
            ->get();

        $synced = 0;

        foreach ($products as $product) {
            ProductSync::updateOrCreate(
                [
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                ],
                [
                    'is_synced' => true,
                    'store_visibility' => true,
                ]
            );
            $synced++;
        }

        return $synced;
    }

    /**
     * Unsync product from store.
     *
     * @param  Product  $product
     * @param  Store  $store
     * @return bool
     */
    public function unsyncProduct(Product $product, Store $store): bool
    {
        return ProductSync::where('product_id', $product->id)
            ->where('store_id', $store->id)
            ->update(['is_synced' => false, 'store_visibility' => false]) > 0;
    }

    /**
     * Update product sync when product is updated.
     *
     * @param  Product  $product
     * @return void
     */
    public function onProductUpdated(Product $product): void
    {
        // If product becomes inactive, hide it from all stores
        if (!$product->is_active) {
            ProductSync::where('product_id', $product->id)
                ->update(['store_visibility' => false]);
        }
    }
}

