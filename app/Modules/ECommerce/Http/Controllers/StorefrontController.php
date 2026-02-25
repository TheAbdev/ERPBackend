<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Page;
use App\Modules\ECommerce\Models\ProductSync;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ECommerce\Models\StorefrontLayout;
use App\Modules\ECommerce\Models\ThemePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StorefrontController extends Controller
{
    public function getStore(string $slug): JsonResponse
    {
        $store = Store::with(['theme', 'theme.pages'])->where('slug', $slug)->first();

        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $data = $store->toArray();
        if (!empty($data['theme'])) {
            // Expose template slug for storefront renderer (atlas-store, echo-store, etc.)
            $data['theme']['slug'] = $data['theme']['source_template'] ?? $data['theme']['slug'];
        }

        return response()->json([
            'data' => $data,
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

        // Sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw('COALESCE(ecommerce_price, 0) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('COALESCE(ecommerce_price, 0) DESC');
                break;
            case 'name_asc':
                $query->join('products', 'ecommerce_product_sync.product_id', '=', 'products.id')
                    ->orderBy('products.name', 'ASC')
                    ->select('ecommerce_product_sync.*');
                break;
            case 'name_desc':
                $query->join('products', 'ecommerce_product_sync.product_id', '=', 'products.id')
                    ->orderBy('products.name', 'DESC')
                    ->select('ecommerce_product_sync.*');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'DESC');
                break;
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

        // Get all pages that should appear in nav: standard (home, products, cart, etc.) + custom.
        // Do NOT filter by page_type = 'custom' so Home, Products, Cart and custom pages all appear.
        $query = Page::where('store_id', $store->id)
            ->where('is_published', true);

        if (Schema::hasColumn('ecommerce_pages', 'nav_visible')) {
            $query->where('nav_visible', true);
        }

        $dbPages = $query->orderBy('nav_order')->get(['id', 'title', 'slug', 'nav_order', 'page_type']);

        // Ensure standard nav entries exist: if no page with slug home/products/cart/account, prepend defaults.
        $defaultNav = [
            ['id' => null, 'title' => 'Home', 'slug' => 'home', 'nav_order' => 0, 'page_type' => 'home'],
            ['id' => null, 'title' => 'Products', 'slug' => 'products', 'nav_order' => 1, 'page_type' => 'products'],
            ['id' => null, 'title' => 'Cart', 'slug' => 'cart', 'nav_order' => 2, 'page_type' => 'cart'],
            ['id' => null, 'title' => 'Account', 'slug' => 'account', 'nav_order' => 3, 'page_type' => 'account'],
        ];
        $bySlug = $dbPages->keyBy('slug');
        $merged = collect();
        foreach ($defaultNav as $def) {
            if ($bySlug->has($def['slug'])) {
                $merged->push($bySlug->get($def['slug']));
            } else {
                $merged->push((object) $def);
            }
        }
        foreach ($dbPages as $p) {
            if (!in_array($p->slug, ['home', 'products', 'cart', 'account'], true)) {
                $merged->push($p);
            }
        }
        $pages = $merged->sortBy('nav_order')->values()->all();

        return response()->json([
            'data' => $pages,
        ]);
    }

    /**
     * Get a page by type.
     * First checks theme pages, then falls back to store pages.
     */
    public function getPageByType(string $slug, string $pageType): JsonResponse
    {
        $store = Store::with('theme')->where('slug', $slug)->first();
        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        // Standard page types that come from theme pages
        $themePageTypes = ['home', 'products', 'product', 'cart', 'checkout', 'account'];

        // If the store has a theme and the page type is a standard type, get from theme pages
        if ($store->theme_id && in_array($pageType, $themePageTypes)) {
            $themePage = ThemePage::where('theme_id', $store->theme_id)
                ->where('page_type', $pageType)
                ->where('is_published', true)
                ->first();

            if ($themePage) {
                return response()->json([
                    'data' => [
                        'id' => $themePage->id,
                        'title' => $themePage->title,
                        'slug' => $themePage->page_type,
                        'page_type' => $themePage->page_type,
                        'content' => $themePage->content,
                        'is_published' => $themePage->is_published,
                        'published_at' => $themePage->published_at,
                        'source' => 'theme',
                    ],
                ]);
            }
        }

        // Fallback to store pages (for custom pages or if theme page not found)
        $query = Page::where('store_id', $store->id)
            ->where('is_published', true);

        if (Schema::hasColumn('ecommerce_pages', 'page_type')) {
            $query->where('page_type', $pageType);
        } else {
            $query->where('slug', $pageType);
        }

        $page = $query->first();

        if ($page) {
            $pageData = $page->toArray();
            $pageData['source'] = 'store';
            return response()->json([
                'data' => $pageData,
            ]);
        }

        // If no page found anywhere, return null
        return response()->json([
            'data' => null,
        ]);
    }

    /**
     * Get theme configuration for the store.
     */
    public function getThemeConfig(string $slug): JsonResponse
    {
        $store = Store::with('theme')->where('slug', $slug)->first();
        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        if (!$store->theme) {
            return response()->json([
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $store->theme->id,
                'name' => $store->theme->name,
                'slug' => $store->theme->source_template ?? $store->theme->slug,
                'config' => $store->theme->config,
            ],
        ]);
    }
}
