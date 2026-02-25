<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Page;
use App\Modules\ECommerce\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $query = Page::query()
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->has('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('page_type') && Schema::hasColumn('ecommerce_pages', 'page_type')) {
            $query->where('page_type', $request->input('page_type'));
        }

        $pages = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $pages->items(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
                'last_page' => $pages->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Page::class);

        $validated = $request->validate([
            'store_id' => ['required', 'exists:ecommerce_stores,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'page_type' => ['sometimes', 'string'],
            'nav_visible' => ['sometimes', 'boolean'],
            'nav_order' => ['sometimes', 'integer'],
            'draft_content' => ['nullable', 'array'],
            'published_content' => ['nullable', 'array'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_published'] = $request->input('is_published', false);

        $data = $this->filterByExistingColumns($validated);
        $page = Page::create($data);

        return response()->json([
            'message' => 'Page created successfully.',
            'data' => $page,
        ], 201);
    }

    public function show(Page $ecommerce_page): JsonResponse
    {
        $this->authorize('view', $ecommerce_page);

        return response()->json([
            'data' => $ecommerce_page,
        ]);
    }

    public function update(Request $request, Page $ecommerce_page): JsonResponse
    {
        $this->authorize('update', $ecommerce_page);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'content' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'page_type' => ['sometimes', 'string'],
            'nav_visible' => ['sometimes', 'boolean'],
            'nav_order' => ['sometimes', 'integer'],
            'draft_content' => ['nullable', 'array'],
            'published_content' => ['nullable', 'array'],
        ]);

        $data = $this->filterByExistingColumns($validated);
        $ecommerce_page->update($data);

        return response()->json([
            'message' => 'Page updated successfully.',
            'data' => $ecommerce_page,
        ]);
    }

    public function destroy(Page $ecommerce_page): JsonResponse
    {
        $this->authorize('delete', $ecommerce_page);

        $ecommerce_page->delete();

        return response()->json([
            'message' => 'Page deleted successfully.',
        ]);
    }

    public function getBySlug(Request $request, string $slug, string $pageSlug): JsonResponse
    {
        $store = Store::where('slug', $slug)->first();
        if (!$store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }

        $query = Page::where('store_id', $store->id)
            ->where('slug', $pageSlug)
            ->where('is_published', true);

        $page = $query->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        return response()->json([
            'data' => $page,
        ]);
    }

    public function getByTypeAdmin(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $request->validate([
            'store_id' => ['required', 'exists:ecommerce_stores,id'],
            'page_type' => ['required', 'string'],
        ]);

        $query = Page::where('store_id', $request->input('store_id'));

        if (Schema::hasColumn('ecommerce_pages', 'page_type')) {
            $query->where('page_type', $request->input('page_type'));
        } else {
            $query->where('slug', $request->input('page_type'));
        }

        $page = $query->first();

        return response()->json([
            'data' => $page,
        ]);
    }

    public function templates(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'template_slug' => 'home-default',
                    'name' => 'Home - Default',
                    'page_type' => 'home',
                    'content' => [
                        'blocks' => [
                            ['type' => 'header', 'content' => [], 'settings' => []],
                            [
                                'type' => 'hero',
                                'content' => [
                                    'title' => 'Welcome to our store',
                                    'subtitle' => 'Discover our best products.',
                                    'buttonText' => 'Shop now',
                                    'buttonLink' => '#',
                                ],
                                'settings' => ['padding' => '48px'],
                            ],
                            ['type' => 'products_grid', 'content' => ['title' => 'Featured Products'], 'settings' => []],
                            ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
                        ],
                    ],
                ],
                [
                    'template_slug' => 'products-default',
                    'name' => 'Products List - Default',
                    'page_type' => 'products',
                    'content' => [
                        'blocks' => [
                            ['type' => 'header', 'content' => [], 'settings' => []],
                            ['type' => 'products_grid', 'content' => ['title' => 'Products'], 'settings' => []],
                            ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
                        ],
                    ],
                ],
                [
                    'template_slug' => 'product-default',
                    'name' => 'Product Details - Default',
                    'page_type' => 'product',
                    'content' => [
                        'blocks' => [
                            ['type' => 'header', 'content' => [], 'settings' => []],
                            ['type' => 'text', 'content' => ['text' => '<p>Product details go here.</p>'], 'settings' => []],
                            ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
                        ],
                    ],
                ],
                [
                    'template_slug' => 'cart-default',
                    'name' => 'Cart - Default',
                    'page_type' => 'cart',
                    'content' => [
                        'blocks' => [
                            ['type' => 'header', 'content' => [], 'settings' => []],
                            ['type' => 'text', 'content' => ['text' => '<p>Your cart items appear here.</p>'], 'settings' => []],
                            ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
                        ],
                    ],
                ],
                [
                    'template_slug' => 'checkout-default',
                    'name' => 'Checkout - Default',
                    'page_type' => 'checkout',
                    'content' => [
                        'blocks' => [
                            ['type' => 'header', 'content' => [], 'settings' => []],
                            ['type' => 'text', 'content' => ['text' => '<p>Checkout details.</p>'], 'settings' => []],
                            ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
                        ],
                    ],
                ],
                [
                    'template_slug' => 'account-default',
                    'name' => 'Account - Default',
                    'page_type' => 'account',
                    'content' => [
                        'blocks' => [
                            ['type' => 'header', 'content' => [], 'settings' => []],
                            ['type' => 'text', 'content' => ['text' => '<p>Account page.</p>'], 'settings' => []],
                            ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function filterByExistingColumns(array $data): array
    {
        $columns = Schema::getColumnListing('ecommerce_pages');
        return array_filter(
            $data,
            fn ($value, $key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
