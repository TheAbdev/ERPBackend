<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\StorefrontLayout;
use App\Modules\ECommerce\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StorefrontLayoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StorefrontLayout::class);

        $query = StorefrontLayout::query()
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->has('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        $layouts = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $layouts->items(),
            'meta' => [
                'current_page' => $layouts->currentPage(),
                'per_page' => $layouts->perPage(),
                'total' => $layouts->total(),
                'last_page' => $layouts->lastPage(),
            ],
        ]);
    }

    public function getByStore(Store $store): JsonResponse
    {
        $this->authorize('view', $store);

        $layout = StorefrontLayout::where('store_id', $store->id)->first();

        return response()->json([
            'data' => $layout,
        ]);
    }

    public function saveByStore(Request $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $validated = $request->validate([
            'layout_json' => ['required', 'array'],
        ]);

        $layout = StorefrontLayout::firstOrCreate(
            ['store_id' => $store->id],
            [
                'tenant_id' => $store->tenant_id,
                'name' => 'Home Layout',
                'slug' => 'home',
                'layout_json' => $validated['layout_json'],
                'is_published' => false,
            ]
        );

        $layout->layout_json = $validated['layout_json'];
        $layout->save();

        return response()->json([
            'message' => 'Layout saved successfully.',
            'data' => $layout,
        ]);
    }

    public function publishByStore(Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $layout = StorefrontLayout::where('store_id', $store->id)->first();

        if (!$layout) {
            return response()->json(['message' => 'Layout not found.'], 404);
        }

        if (Schema::hasColumn('storefront_layouts', 'published_layout')) {
            $layout->published_layout = $layout->layout_json;
        }

        $layout->is_published = true;
        $layout->save();

        return response()->json([
            'message' => 'Layout published successfully.',
            'data' => $layout,
        ]);
    }
}
