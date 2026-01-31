<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Display a listing of stores.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Store::class);

        $query = Store::with(['theme'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $stores = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $stores->items(),
            'meta' => [
                'current_page' => $stores->currentPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
                'last_page' => $stores->lastPage(),
            ],
        ]);
    }

    /**
     * Get the single store for the current tenant.
     * Each tenant can only have one store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myStore(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Store::class);

        $store = Store::with(['theme'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->first();

        return response()->json([
            'data' => $store,
            'has_store' => $store !== null,
        ]);
    }

    /**
     * Store a newly created store.
     * Each tenant can only have one store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Store::class);

        // Check if tenant already has a store
        $existingStore = Store::where('tenant_id', $request->user()->tenant_id)->first();
        if ($existingStore) {
            return response()->json([
                'message' => 'Your tenant already has a store. Each tenant can only have one store.',
                'data' => $existingStore->load('theme'),
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:ecommerce_stores,slug'],
            'theme_id' => ['nullable', 'exists:ecommerce_themes,id'],
            'is_active' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string'],
            'favicon' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->input('is_active', true);

        $store = Store::create($validated);

        return response()->json([
            'message' => 'Store created successfully.',
            'data' => $store->load('theme'),
        ], 201);
    }

    /**
     * Display the specified store.
     *
     * @param  \App\Modules\ECommerce\Models\Store  $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Store $store): JsonResponse
    {
        $this->authorize('view', $store);

        return response()->json([
            'data' => $store->load(['theme', 'productSyncs.product']),
        ]);
    }

    /**
     * Update the specified store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ECommerce\Models\Store  $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:ecommerce_stores,slug,' . $store->id],
            'theme_id' => ['nullable', 'exists:ecommerce_themes,id'],
            'is_active' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string'],
            'favicon' => ['nullable', 'string'],
        ]);

        $store->update($validated);

        return response()->json([
            'message' => 'Store updated successfully.',
            'data' => $store->load('theme'),
        ]);
    }

    /**
     * Remove the specified store.
     *
     * @param  \App\Modules\ECommerce\Models\Store  $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Store $store): JsonResponse
    {
        $this->authorize('delete', $store);

        $store->delete();

        return response()->json([
            'message' => 'Store deleted successfully.',
        ]);
    }
}



















