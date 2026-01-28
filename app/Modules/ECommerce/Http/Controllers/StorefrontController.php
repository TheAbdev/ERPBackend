<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Page;
use App\Modules\ECommerce\Models\ProductSync;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ECommerce\Models\StorefrontLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StorefrontController extends Controller
{
    public function getStore(string $slug): JsonResponse
    {
        $store = Store::with('theme')->where('slug', $slug)->first();

        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        return response()->json([
            'data' => $store,
        ]);
    }

    public function getProducts(Request $request, string $slug): JsonResponse
    {
        $store = Store::where('slug', $slug)->first();
        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $query = ProductSync::with('product')
            ->where('store_id', $store->id)
            ->where('is_synced', true);

        if (Schema::hasColumn('ecommerce_product_sync', 'store_visibility')) {
            $query->where('store_visibility', true);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        $products = $query->paginate($request->input('per_page', 15));

        $data = $products->getCollection()->map(function (ProductSync $sync) {
            $product = $sync->product;
            $image = $sync->ecommerce_images;
            $images = $image ? [$image] : [];
            
            return [
                'id' => $product?->id ?? $sync->product_id,
                'name' => $product?->name ?? 'Product',
                'description' => $sync->ecommerce_description ?? $product?->description,
                'price' => $sync->ecommerce_price ?? $product?->price ?? 0,
                'images' => $images,
                'stock' => [
                    'available' => $product?->quantity ?? 0,
                ],
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function getProduct(string $slug, string $productId): JsonResponse
    {
        $store = Store::where('slug', $slug)->first();
        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $sync = ProductSync::with('product')
            ->where('store_id', $store->id)
            ->where('product_id', $productId)
            ->first();

        if (!$sync || !$sync->product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $image = $sync->ecommerce_images;
        $images = $image ? [$image] : [];
        
        return response()->json([
            'data' => [
                'id' => $sync->product->id,
                'name' => $sync->product->name,
                'description' => $sync->ecommerce_description ?? $sync->product->description,
                'price' => $sync->ecommerce_price ?? $sync->product->price ?? 0,
                'images' => $images,
                'stock' => [
                    'available' => $sync->product->quantity ?? 0,
                ],
            ],
        ]);
    }

    public function getLayout(string $slug): JsonResponse
    {
        $store = Store::where('slug', $slug)->first();
        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $layout = StorefrontLayout::where('store_id', $store->id)
            ->where('is_published', true)
            ->first();

        return response()->json([
            'data' => $layout,
        ]);
    }

    public function getNavPages(string $slug): JsonResponse
    {
        $store = Store::where('slug', $slug)->first();
        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $query = Page::where('store_id', $store->id)
            ->where('is_published', true);

        if (Schema::hasColumn('ecommerce_pages', 'nav_visible')) {
            $query->where('nav_visible', true);
        }

        if (Schema::hasColumn('ecommerce_pages', 'page_type')) {
            $query->where('page_type', 'custom');
        }

        $pages = $query->orderBy('nav_order')->get(['id', 'title', 'slug', 'nav_order']);

        return response()->json([
            'data' => $pages,
        ]);
    }

    public function getPageByType(string $slug, string $pageType): JsonResponse
    {
        $store = Store::where('slug', $slug)->first();
        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $query = Page::where('store_id', $store->id)
            ->where('is_published', true);

        if (Schema::hasColumn('ecommerce_pages', 'page_type')) {
            $query->where('page_type', $pageType);
        } else {
            $query->where('slug', $pageType);
        }

        $page = $query->first();

        return response()->json([
            'data' => $page,
        ]);
    }
}
