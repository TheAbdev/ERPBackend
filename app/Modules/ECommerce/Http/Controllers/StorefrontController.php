<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ECommerce\Models\ProductSync;
use App\Modules\ERP\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    /**
     * Get store information (public).
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStore(string $slug): JsonResponse
    {
        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['theme'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'logo' => $store->logo,
                'favicon' => $store->favicon,
                'settings' => $store->settings,
                'theme' => $store->theme,
            ],
        ]);
    }

    /**
     * Get products for storefront (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProducts(Request $request, string $slug): JsonResponse
    {
        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $query = ProductSync::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('store_visibility', true)
            ->where('is_synced', true)
            ->with(['product.category', 'product.stockItems']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $productSyncs = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        $products = $productSyncs->map(function ($sync) {
            $product = $sync->product;
            
            // Get price: prioritize ecommerce_price, fallback to 0
            // Note: In a full implementation, you might want to get price from a pricing table
            $price = $sync->ecommerce_price !== null && $sync->ecommerce_price !== '' 
                ? (float) $sync->ecommerce_price 
                : 0;
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $sync->ecommerce_description ?? $product->description,
                'price' => $price,
                'images' => $sync->ecommerce_images ? [$sync->ecommerce_images] : [],
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'stock' => [
                    'available' => $product->quantity ?? 0,
                    'on_hand' => $product->quantity ?? 0,
                ],
            ];
        });

        return response()->json([
            'data' => $products,
            'meta' => [
                'current_page' => $productSyncs->currentPage(),
                'per_page' => $productSyncs->perPage(),
                'total' => $productSyncs->total(),
                'last_page' => $productSyncs->lastPage(),
            ],
        ]);
    }

    /**
     * Get single product for storefront (public).
     *
     * @param  string  $storeSlug
     * @param  int  $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProduct(string $storeSlug, int $productId): JsonResponse
    {
        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $sync = ProductSync::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('product_id', $productId)
            ->where('store_visibility', true)
            ->where('is_synced', true)
            ->with(['product.category', 'product.variants', 'product.stockItems'])
            ->firstOrFail();

        $product = $sync->product;

        // Get price: prioritize ecommerce_price, fallback to 0
        $price = $sync->ecommerce_price !== null && $sync->ecommerce_price !== '' 
            ? (float) $sync->ecommerce_price 
            : 0;
        
        return response()->json([
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'description' => $sync->ecommerce_description ?? $product->description,
                'price' => $price,
                'images' => $sync->ecommerce_images ? [$sync->ecommerce_images] : [],
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'sku' => $variant->sku,
                    ];
                }),
                'stock' => [
                    'available' => $product->quantity ?? 0,
                    'on_hand' => $product->quantity ?? 0,
                ],
            ],
        ]);
    }
}

