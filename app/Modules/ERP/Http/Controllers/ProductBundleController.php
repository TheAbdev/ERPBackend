<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\ProductBundleResource;
use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\ProductBundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProductBundleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductBundle::class);

        $query = ProductBundle::with(['product', 'items.product']);

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $bundles = $query->latest()->paginate();

        return ProductBundleResource::collection($bundles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ProductBundle::class);

        $validated = $request->validate([
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('product_bundles')->where(function ($query) use ($request) {
                    return $query->where('tenant_id', $request->user()->tenant_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'bundle_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $bundle = ProductBundle::create([
            'tenant_id' => $request->user()->tenant_id,
            'product_id' => $validated['product_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'bundle_price' => $validated['bundle_price'] ?? null,
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Create bundle items
        foreach ($validated['items'] as $index => $item) {
            $bundle->items()->create([
                'tenant_id' => $request->user()->tenant_id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }

        $bundle->load(['product', 'items.product']);

        return response()->json([
            'data' => new ProductBundleResource($bundle),
            'message' => 'Product bundle created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductBundle $productBundle): JsonResponse
    {
        $this->authorize('view', $productBundle);

        $productBundle->load(['product', 'items.product']);

        return response()->json([
            'data' => new ProductBundleResource($productBundle),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductBundle $productBundle): JsonResponse
    {
        $this->authorize('update', $productBundle);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'bundle_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $productBundle->update($validated);

        // Update items if provided
        if ($request->has('items')) {
            $productBundle->items()->delete();
            foreach ($validated['items'] as $index => $item) {
                $productBundle->items()->create([
                    'tenant_id' => $request->user()->tenant_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? null,
                    'sort_order' => $item['sort_order'] ?? $index,
                ]);
            }
        }

        $productBundle->load(['product', 'items.product']);

        return response()->json([
            'data' => new ProductBundleResource($productBundle),
            'message' => 'Product bundle updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductBundle $productBundle): JsonResponse
    {
        $this->authorize('delete', $productBundle);

        $productBundle->delete();

        return response()->json([
            'message' => 'Product bundle deleted successfully.',
        ]);
    }
}




