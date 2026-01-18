<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\ProductCategoryResource;
use App\Modules\ERP\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::with(['parent', 'children'])
            ->whereNull('parent_id')
            ->latest()
            ->paginate();

        return ProductCategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ProductCategory::class);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:product_categories,id',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        $category = ProductCategory::create($validated);

        return response()->json([
            'message' => 'Product category created successfully.',
            'data' => new ProductCategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\ProductCategory  $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ProductCategory $category): JsonResponse
    {
        $this->authorize('view', $category);

        return response()->json([
            'data' => new ProductCategoryResource($category->load(['parent', 'children', 'products'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\ProductCategory  $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ProductCategory $category): JsonResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:product_categories,id',
            'code' => 'sometimes|required|string|max:255',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Product category updated successfully.',
            'data' => new ProductCategoryResource($category),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\ProductCategory  $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ProductCategory $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->json([
            'message' => 'Product category deleted successfully.',
        ]);
    }
}

