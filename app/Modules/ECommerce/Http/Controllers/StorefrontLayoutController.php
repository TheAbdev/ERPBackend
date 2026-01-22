<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ECommerce\Models\StorefrontLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontLayoutController extends Controller
{
    /**
     * Get store layout for editing (admin).
     *
     * @param  \App\Modules\ECommerce\Models\Store  $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLayout(Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $layout = StorefrontLayout::where('store_id', $store->id)
            ->where('slug', 'home')
            ->first();

        return response()->json([
            'data' => $layout,
        ]);
    }

    /**
     * Update store layout (admin).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ECommerce\Models\Store  $store
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLayout(Request $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'layout' => ['required', 'array'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $slug = $validated['slug'] ?? 'home';
        $name = $validated['name'] ?? 'Home Layout';
        $isPublished = $validated['is_published'] ?? true;

        $layout = StorefrontLayout::updateOrCreate(
            [
                'store_id' => $store->id,
                'slug' => $slug,
            ],
            [
                'tenant_id' => $store->tenant_id,
                'name' => $name,
                'layout_json' => $validated['layout'],
                'is_published' => $isPublished,
            ]
        );

        return response()->json([
            'message' => 'Storefront layout saved successfully.',
            'data' => $layout,
        ]);
    }

    /**
     * Get published layout for storefront (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicLayout(Request $request, string $storeSlug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $slug = $request->input('layout_slug', 'home');

        $layout = StorefrontLayout::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        return response()->json([
            'data' => $layout,
        ]);
    }
}






