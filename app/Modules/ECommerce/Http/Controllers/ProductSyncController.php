<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\ProductSync;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ECommerce\Services\ProductSyncService;
use App\Modules\ERP\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSyncController extends Controller
{
    protected ProductSyncService $productSyncService;

    public function __construct(ProductSyncService $productSyncService)
    {
        $this->productSyncService = $productSyncService;
    }

    /**
     * Get sync status for products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProductSync::class);

        $validated = $request->validate([
            'store_id' => ['required', 'exists:ecommerce_stores,id'],
            'product_ids' => ['nullable', 'string'], // Comma-separated product IDs
        ]);

        $productIds = $validated['product_ids']
            ? explode(',', $validated['product_ids'])
            : [];

        $query = ProductSync::where('store_id', $validated['store_id'])
            ->where('tenant_id', $request->user()->tenant_id);

        if (!empty($productIds)) {
            $query->whereIn('product_id', $productIds);
        }

        $syncs = $query->get();

        return response()->json([
            'data' => $syncs->map(function ($sync) {
                return [
                    'product_id' => $sync->product_id,
                    'is_synced' => $sync->is_synced,
                    'store_visibility' => $sync->store_visibility,
                    'ecommerce_price' => $sync->ecommerce_price,
                ];
            }),
        ]);
    }

    /**
     * Sync product to store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request): JsonResponse
    {
        $this->authorize('create', ProductSync::class);

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'store_id' => ['required', 'exists:ecommerce_stores,id'],
            'store_visibility' => ['sometimes', 'boolean'],
            'ecommerce_price' => ['nullable', 'numeric'],
            'ecommerce_images' => ['sometimes', 'string', 'nullable'],
            'ecommerce_description' => ['nullable', 'string'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $store = Store::findOrFail($validated['store_id']);

        // Verify tenant access
        if ($product->tenant_id !== $request->user()->tenant_id ||
            $store->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $sync = $this->productSyncService->syncProduct($product, $store, [
            'store_visibility' => $validated['store_visibility'] ?? true,
            'ecommerce_price' => $validated['ecommerce_price'] ?? null,
            'ecommerce_images' => $validated['ecommerce_images'] ?? null,
            'ecommerce_description' => $validated['ecommerce_description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Product synced successfully.',
            'data' => $sync,
        ]);
    }

    /**
     * Unsync product from store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'store_id' => ['required', 'exists:ecommerce_stores,id'],
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $store = Store::findOrFail($validated['store_id']);

        // Verify tenant access
        if ($product->tenant_id !== $request->user()->tenant_id ||
            $store->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Find the ProductSync record to authorize
        $productSync = ProductSync::where('product_id', $product->id)
            ->where('store_id', $store->id)
            ->where('tenant_id', $request->user()->tenant_id)
            ->first();

        if (!$productSync) {
            return response()->json(['message' => 'Product sync not found.'], 404);
        }

        $this->authorize('update', $productSync);

        $this->productSyncService->unsyncProduct($product, $store);

        return response()->json([
            'message' => 'Product unsynced successfully.',
        ]);
    }

    /**
     * Sync all active products to store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncAll(Request $request): JsonResponse
    {
        $this->authorize('create', ProductSync::class);

        $validated = $request->validate([
            'store_id' => ['required', 'exists:ecommerce_stores,id'],
        ]);

        $store = Store::findOrFail($validated['store_id']);

        // Verify tenant access
        if ($store->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $count = $this->productSyncService->syncAllProducts($store);

        return response()->json([
            'message' => "{$count} products synced successfully.",
            'count' => $count,
        ]);
    }
}

