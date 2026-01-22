<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * Display a listing of pages.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $query = Page::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        $pages = $query->orderBy('sort_order')
            ->latest()
            ->paginate($request->input('per_page', 15));

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

    /**
     * Store a newly created page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Page::class);

        $validated = $request->validate([
            'store_id' => ['required', 'exists:ecommerce_stores,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'meta' => ['sometimes', 'array'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['is_published'] = $request->input('is_published', false);
        $validated['sort_order'] = $request->input('sort_order', 0);

        $page = Page::create($validated);

        return response()->json([
            'message' => 'Page created successfully.',
            'data' => $page,
        ], 201);
    }

    /**
     * Display the specified page.
     *
     * @param  \App\Modules\ECommerce\Models\Page  $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Page $page): JsonResponse
    {
        $this->authorize('view', $page);

        return response()->json([
            'data' => $page->load('store'),
        ]);
    }

    /**
     * Get page by slug (public).
     *
     * @param  string  $storeSlug
     * @param  string  $pageSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySlug(string $storeSlug, string $pageSlug): JsonResponse
    {
        // Remove tenant scope for public storefront access
        $store = \App\Modules\ECommerce\Models\Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $page = Page::where('store_id', $store->id)
            ->where('slug', $pageSlug)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json([
            'data' => $page,
        ]);
    }

    /**
     * Update the specified page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ECommerce\Models\Page  $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
            'meta' => ['sometimes', 'array'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $page->update($validated);

        return response()->json([
            'message' => 'Page updated successfully.',
            'data' => $page,
        ]);
    }

    /**
     * Remove the specified page.
     *
     * @param  \App\Modules\ECommerce\Models\Page  $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Page $page): JsonResponse
    {
        $this->authorize('delete', $page);

        $page->delete();

        return response()->json([
            'message' => 'Page deleted successfully.',
        ]);
    }
}

